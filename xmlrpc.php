<?php
##################################################
#
# Copyright (c) 2004-2015 OIC Group, Inc.
# Written and Designed by Dave Leffler
#
# This file is part of Exponent
#
# Exponent is free software; you can redistribute
# it and/or modify it under the terms of the GNU
# General Public License as published by the Free
# Software Foundation; either version 2 of the
# License, or (at your option) any later version.
#
# GPL: http://www.gnu.org/licenses/gpl.txt
#
##################################################

//define('SCRIPT_EXP_RELATIVE', '');
//define('SCRIPT_FILENAME', 'xmlrpc.php');

// Initialize the Exponent Framework
require_once('exponent.php');

// don't continue unless turned on
if (!(defined('USE_XMLRPC') && USE_XMLRPC == 1))
    exit;

// These three files are from the PHP-XMLRPC library.
require_once('external/xmlrpc/xmlrpc.php');
require_once('external/xmlrpc/xmlrpcs.php');
require_once('external/xmlrpc/xmlrpc_wrappers.php');

/**
 * Used to test usage of object methods in dispatch maps
 */
class xmlrpc_server_methods_container
{
}

// Internal User Login function	
function userLogin($username, $password, $src, $area)
{
    global $db, $user;

    if ($user->isLoggedIn()) {
        return true;
    }
    // This is where you would check to see if the username and password are valid
    // and whether the user has rights to perform this action ($area) 'create' or 'edit' or 'delete'
    // Return true if so. Or false if the login info is wrong.

    // Retrieve the user object from the database.  This may be null, if the username is non-existent.
    $user = new user($db->selectValue('user', 'id', "username='" . $username . "'"));
    $authenticated = $user->authenticate($password);

    if ($authenticated) {
        // Call on the Sessions subsystem to log the user into the site.
        expSession::login($user);
        //Update the last login timestamp for this user.
        $user->updateLastLogin();
    }

    if ($user->isLoggedIn()) {
        return true;
    } else {
        return false;
    }
}

function exp_getModuleInstancesByType($type = null)
{
    if (empty($type)) {
        return array();
    }
    global $db;
    $refs = $db->selectObjects('sectionref', 'module="' . $type . '"');
    $modules = array();
    foreach ($refs as $ref) {
        if ($ref->refcount > 0) {
            $instance = $db->selectObject('container', 'internal like "%' . $ref->source . '%"');
            $mod = new stdClass();
            $mod->title = !empty($instance->title) ? $instance->title : "Untitled";
            $mod->section = $db->selectvalue('section', 'name', 'id=' . $ref->section);
            $modules[$ref->source][] = $mod;
        }
    }

    return $modules;
}

// Get List of User Blogs function	
$getUsersBlogs_sig = array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$getUsersBlogs_doc = 'Returns a list of weblogs to which an author has posting privileges.';
function getUsersBlogs($xmlrpcmsg)
{
    global $db;
    $username = $xmlrpcmsg->getParam(1)->scalarval();
    $password = $xmlrpcmsg->getParam(2)->scalarval();

    if (userLogin($username, $password, null, 'create') == true) {
        // setup the list of blogs.
        $structArray = array();

        $blogs = exp_getModuleInstancesByType('blog');
        foreach ($blogs as $src => $blog) {
            $blog_name = (empty($blog[0]->title) ? 'Untitled' : $blog[0]->title) . ' on page ' . $blog[0]->section;
            $loc = expCore::makeLocation('blog', $src);
            $section = $db->selectObject('sectionref', 'source="' . $src . '"');
            $page = $db->selectObject('section', 'id="' . $section->section . '"');
            if (expPermissions::check('create', $loc) || (expPermissions::check('edit', $loc))) {
                $structArray[] = new xmlrpcval(
                    array(
                        'blogid' => new xmlrpcval($src, 'string'),
                        'url' => new xmlrpcval(URL_FULL . $page->sef_name, 'string'),
                        'blogName' => new xmlrpcval($blog_name, 'string'),
                        'isAdmin' => new xmlrpcval(true, 'boolean'),
                        'xmlrpc' => new xmlrpcval(URL_FULL . 'xmlrpc.php', 'string')
                    ), 'struct'
                );
            }
        }
        return new xmlrpcresp(new xmlrpcval($structArray, 'array'));
    } else {
        return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
    }
}

// Create a New Post function	
$newPost_sig = array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcStruct, $xmlrpcBoolean));
$newPost_doc = 'Post a new item to the blog.';
function newPost($xmlrpcmsg)
{
    global $db, $user;
    $src = $xmlrpcmsg->getParam(0)->scalarval();
    $username = $xmlrpcmsg->getParam(1)->scalarval();
    $password = $xmlrpcmsg->getParam(2)->scalarval();

    if (userLogin($username, $password, $src, 'create') == true) {
        $loc = expCore::makeLocation('blog', $src);
        if (expPermissions::check('create', $loc)) {
            $content = $xmlrpcmsg->getParam(3);
            $title = $content->structMem('title')->scalarval();
            $description = $content->structMem('description')->scalarval();
            //$dateCreated = $content->structMem('dateCreated')->serialize();   // Not all clients send dateCreated info. So add if statement here if you want to use it.
            //$timestamp = iso8601_decode($dateCreated);  // To convert to unix timestamp
//			if($content->structMem('categories')->arraySize() > 0) {
//				$categories = $content->structMem('categories')->arrayMem(0)->scalarval();
//			}
            $categories = array();
            for ($i = 0; $i < $content->structMem('categories')->arraySize(); $i++) {
                $categories[$i] = $content->structMem('categories')->arrayMem($i)->scalarval();
            }
            $published = $xmlrpcmsg->getParam(4)->scalarval();

            $post = new blog();
            $iloc = new stdClass();

            $post->title = $title;
            $post->body = htmlspecialchars_decode(htmlentities($description, ENT_NOQUOTES));
            $post->private = (($published) ? 0 : 1);
            $post->location_data = serialize($loc);

            //Get and add the categories selected by the user
            $params['expCat'] = array();
            foreach ($categories as $cat) {
                $ecat= new expCat($cat);
                if (empty($ecat->id)) {
                    // doesn't exist so add it
                    $ecat->title = $cat;
                    $ecat->module = 'blog';
                    $ecat->update();
                }
                $params['expCat'][] = $ecat->id;
            }

            $post->publish = 0;
            $post->update($params);

            return new xmlrpcresp(
                new xmlrpcval($post->id, 'string')
            ); // Return the id of the post just inserted into the DB. See mysql_insert_id() in the PHP manual.
        } else {
            return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
        }
    } else {
        return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
    }
}

// Edit a Post function	
$editPost_sig = array(
    array(
        $xmlrpcBoolean,
        $xmlrpcString,
        $xmlrpcString,
        $xmlrpcString,
        $xmlrpcStruct,
        $xmlrpcBoolean
    )
);
$editPost_doc = 'Edit an item on the blog.';
function editPost($xmlrpcmsg)
{
    global $db, $user;

    $postid = $xmlrpcmsg->getParam(0)->scalarval();
    $username = $xmlrpcmsg->getParam(1)->scalarval();
    $password = $xmlrpcmsg->getParam(2)->scalarval();

    $post = new blog($postid);
    $loc = unserialize($post->location_data);
    $iloc = expCore::makeLocation($loc->mod, $loc->src);
    if (userLogin($username, $password, $loc->src, 'edit') == true) {
        if (expPermissions::check('edit', $loc)) {
            $content = $xmlrpcmsg->getParam(3);
            $title = $content->structMem('title')->scalarval();
            $description = $content->structMem('description')->scalarval();
            //$dateCreated = $content->structMem('dateCreated')->serialize();   // Not all clients send dateCreated info. So add if statement here if you want to use it.
            //$timestamp = iso8601_decode($dateCreated);  // To convert to unix timestamp
            // if($content->structMem('categories')->arraySize() > 0) {
            // $categories = $content->structMem('categories')->arrayMem(0)->scalarval();
            // }
            // $published = $xmlrpcmsg->getParam(4)->scalarval();
            // if($content->structMem('categories')->arraySize() > 0) {
            $categories = array();
            for ($i = 0; $i < $content->structMem('categories')->arraySize(); $i++) {
                $categories[$i] = $content->structMem('categories')->arrayMem($i)->scalarval();
            }
            $published = $xmlrpcmsg->getParam(4)->scalarval();

            $post->title = $title;
            $post->body = htmlspecialchars_decode(htmlentities($description, ENT_NOQUOTES));
            $post->private = (($published) ? 0 : 1);
            $post->location_data = serialize($loc);

            //Get and add the categories selected by the user
            $params['expCat'] = array();
            foreach ($categories as $cat) {
                $ecat= new expCat($cat);
                if (empty($ecat->id)) {
                    // doesn't exist so add it
                    $ecat->title = $cat;
                    $ecat->module = 'blog';
                    $ecat->update();
                }
                $params['expCat'][] = $ecat->id;
            }

            $db->updateObject($post, 'weblog_post');
            $post->update($params);

            return new xmlrpcresp(new xmlrpcval(true, 'boolean'));
        } else {
            return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
        }
    } else {
        return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
    }
}

// Get a Post function	
$getPost_sig = array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$getPost_doc = 'Get an item on the blog.';
function getPost($xmlrpcmsg)
{
    global $db;

    $postid = $xmlrpcmsg->getParam(0)->scalarval();
    $username = $xmlrpcmsg->getParam(1)->scalarval();
    $password = $xmlrpcmsg->getParam(2)->scalarval();

    //convert $postid to $src
    $post = new blog(intval($postid));
    $loc = unserialize($post->location_data);
    $iloc = expCore::makeLocation($loc->mod, $loc->src);
    if (userLogin($username, $password, $loc->src, 'edit') == true) {
        if (expPermissions::check('edit', $loc)) {
            $cat = array();
            foreach ($post->expCat as $pcat) {
                $expcat = new expCat($pcat->id);
                $cat[] = $expcat->title;
            }

            return new xmlrpcresp(
                new xmlrpcval(
                    array(
                        'postid' => new xmlrpcval($post->id, 'string'),
                        'dateCreated' => new xmlrpcval($post->publish, 'dateTime.iso8601'),
                        'title' => new xmlrpcval($post->title, 'string'),
                        'description' => new xmlrpcval($post->body, 'string'),
                        'categories' => php_xmlrpc_encode($cat),
                        'publish' => new xmlrpcval((($post->private) ? 0 : 1), 'boolean')
                    ), 'struct'
                )
            );

        } else {
            return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
        }
    } else {
        return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
    }
}

// Delete a Post function	
$deletePost_sig = array(
    array(
        $xmlrpcBoolean,
        $xmlrpcString,
        $xmlrpcString,
        $xmlrpcString,
        $xmlrpcString,
        $xmlrpcBoolean
    )
);
$deletePost_doc = 'Deletes a post.';
function deletePost($xmlrpcmsg)  //FiXME we don't seem to ever use this
{
//    $appkey=$xmlrpcmsg->getParam(0)->scalarval();
    $postid = $xmlrpcmsg->getParam(1)->scalarval();
    $username = $xmlrpcmsg->getParam(2)->scalarval();
    $password = $xmlrpcmsg->getParam(3)->scalarval();
//    $published = $xmlrpcmsg->getParam(4)->scalarval();

    //convert $postid to $src
    $post = new blog($postid);
    $loc = unserialize($post->location_data);
    if (userLogin($username, $password, $loc->src, 'delete') == true) {
        $post->delete();

        return new xmlrpcresp(new xmlrpcval(true, 'boolean'));
    } else {
        return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
    }
}

// Get a List of Recent Posts function	
$getRecentPosts_sig = array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcInt));
$getRecentPosts_doc = 'Get the recent posts on the blog.';
function getRecentPosts($xmlrpcmsg)
{
    global $db;

    $src = $xmlrpcmsg->getParam(0)->scalarval();
    $username = $xmlrpcmsg->getParam(1)->scalarval();
    $password = $xmlrpcmsg->getParam(2)->scalarval();

    if (userLogin($username, $password, $src, 'edit') == true) {
        $loc = expCore::makeLocation('blog', $src);
        if (expPermissions::check('edit', $loc)) {
            $numposts = $xmlrpcmsg->getParam(3)->scalarval();

            $structArray = array();

            // If this module has been configured to aggregate then setup the where clause to pull
            // posts from the proper blogs.
//			$config = $db->selectObject('weblogmodule_config',"location_data='".serialize($loc)."'");
            $locsql = "(location_data='" . serialize($loc) . "'";
            // if (!empty($config->aggregate)) {
            // $locations = unserialize($config->aggregate);
            // foreach ($locations as $source) {
            // $tmploc = null;
            // $tmploc->mod = 'weblogmodule';
            // $tmploc->src = $source;
            // $tmploc->int = '';
            // $locsql .= " OR location_data='".serialize($tmploc)."'";
            // }
            // }
            $locsql .= ')';

//			$where = '(is_draft = 0 OR poster = '.$user_id.") AND ".$locsql;
            $where = $locsql;
//echo $where;			
//			if (!exponent_permissions_check('view_private',$loc)) $where .= ' AND is_private = 0';

//			$posts = $db->selectObjects('blog',$where . ' ORDER BY posted DESC '.$db->limit($numposts,0));
            $blog = new blog();
            $posts = $blog->find('all', $where, 'publish DESC', $numposts);
//echo print_r($posts);
            for ($i = 0; $i < count($posts); $i++) {
//				$ploc = exponent_core_makeLocation($loc->mod,$loc->src,$posts[$i]->id);

//				$posts[$i]->permissions = array(
//					'administrate'=>exponent_permissions_check('administrate',$ploc),
//					'edit'=>exponent_permissions_check('edit',$ploc),
//					'delete'=>exponent_permissions_check('delete',$ploc),
//					'comment'=>exponent_permissions_check('comment',$ploc),
//					'edit_comments'=>exponent_permissions_check('edit_comments',$ploc),
//					'delete_comments'=>exponent_permissions_check('delete_comments',$ploc),
//					'view_private'=>exponent_permissions_check('view_private',$ploc),
//				);
//				$comments = $db->selectObjects('weblog_comment','parent_id='.$posts[$i]->id);
//				usort($comments,'exponent_sorting_byPostedDescending');
//				$posts[$i]->comments = $comments;
//				$posts[$i]->total_comments = count($comments);

                //Get the tags for this weblogitem
//				$selected_tags = array();
//				$tag_ids = unserialize($posts[$i]->tags);
//				if(is_array($tag_ids) && count($tag_ids)>0) {$selected_tags = $db->selectObjectsInArray('tags', $tag_ids, 'name');}
//				$posts[$i]->tags = $selected_tags;
//				$posts[$i]->selected_tags = $selected_tags;

                $structArray[] = new xmlrpcval(
                    array(
                        'postid' => new xmlrpcval($posts[$i]->id, 'string'),
                        'dateCreated' => new xmlrpcval($posts[$i]->publish, 'dateTime.iso8601'),
                        'title' => new xmlrpcval($posts[$i]->title, 'string'),
//	  			      'description'        => new xmlrpcval($posts[$i]->body, 'string'),
//  				  'categories'        => new xmlrpcval(array(new xmlrpcval($posts[$i]->selected_tags, 'string')), 'array'),
                        'publish' => new xmlrpcval((($posts[$i]->private) ? 0 : 1), 'boolean')
                    ), 'struct'
                );
            }
            return new xmlrpcresp(new xmlrpcval($structArray, 'array')); // Return type is struct[] (array of struct)
        } else {
            return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
        }
    } else {
        return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
    }
}

// Get a List of Categories function	
$getCategories_sig = array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$getCategories_doc = 'Get the categories on the blog.';
function getCategories($xmlrpcmsg)
{
    global $db;

    $src = $xmlrpcmsg->getParam(0)->scalarval();
    $username = $xmlrpcmsg->getParam(1)->scalarval();
    $password = $xmlrpcmsg->getParam(2)->scalarval();

    if (userLogin($username, $password, $src, 'create') == true) {
        $loc = expCore::makeLocation('blog', $src);
        $config = new expConfig($loc);
        $structArray = array();
        if (!empty($config->config['usecategories'])) {
            $expcat = new expCat();
            $cats = $expcat->find('all', "module='blog'");
            foreach ($cats as $cat) {
                $structArray[] = new xmlrpcval(
                    array(
                        'title' => new xmlrpcval($cat->title),
                        'description' => new xmlrpcval($cat->title)
                    ), 'struct'
                );
            }
        }
        return new xmlrpcresp(new xmlrpcval($structArray, 'array')); // Return type is struct[] (array of struct)
    } else {
        return new xmlrpcresp(0, $xmlrpcerruser + 1, 'Login Failed');
    }
}

// Upload a Media File function	
$newMediaObject_sig = array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcStruct));
$newMediaObject_doc = 'Upload media files onto the blog server.';
function newMediaObject($xmlrpcmsg)
{
    $src = $xmlrpcmsg->getParam(0)->scalarval();  //NOTE new?
    $username = $xmlrpcmsg->getParam(1)->scalarval();
    $password = $xmlrpcmsg->getParam(2)->scalarval();

    if (userLogin($username, $password, null, 'create') == true) {
        elog($src,'media');
        $file = $xmlrpcmsg->getParam(3);
        $filename = $file->structMem('name')->scalarval();
        $filename = substr($filename, (strrpos($filename, "/") + 1));
        $type = $file->structMem('type')->scalarval(); // The type of the file
        $bits = $file->structMem('bits')->serialize();
        $bits = str_replace("<value><base64>", "", $bits);
        $bits = str_replace("</base64></value>", "", $bits);
        $dest = UPLOAD_DIRECTORY;
        $uploaddir = BASE . $dest;
        //Check to see if the directory exists.  If not, create the directory structure.
        if (!file_exists(BASE . $dest)) {
            expFile::makeDirectory($dest);
        }
        if (fwrite(fopen($uploaddir . $filename, "wb"), base64_decode($bits)) == false) {
            return new xmlrpcresp(0, $xmlrpcerruser + 1, "File Failed to Write");
        } else {
            return new xmlrpcresp(
                new xmlrpcval(
                    array('url' => new xmlrpcval(URL_FULL . $dest . urlencode($filename), 'string')), 'struct'
                )
            );
        }
    } else {
        return new xmlrpcresp(0, $xmlrpcerruser + 1, "Login Failed");
    }
}

// Create XML-RPC Server function	
$o = new xmlrpc_server_methods_container;
$a = array(
    'blogger.getUsersBlogs' => array(
        'function' => 'getUsersBlogs',
        'docstring' => $getUsersBlogs_doc,
        'signature' => $getUsersBlogs_sig
    ),
    "metaWeblog.newPost" => array(
        "function" => "newPost",
        "signature" => $newPost_sig,
        "docstring" => $newPost_doc
    ),
    "metaWeblog.editPost" => array(
        "function" => "editPost",
        "signature" => $editPost_sig,
        "docstring" => $editPost_doc
    ),
    "metaWeblog.getPost" => array(
        "function" => "getPost",
        "signature" => $getPost_sig,
        "docstring" => $getPost_doc
    ),
    "metaWeblog.getRecentPosts" => array(
        "function" => "getRecentPosts",
        "signature" => $getRecentPosts_sig,
        "docstring" => $getRecentPosts_doc
    ),
    "metaWeblog.getCategories" => array(
        "function" => "getCategories",
        "signature" => $getCategories_sig,
        "docstring" => $getCategories_doc
    ),
    "metaWeblog.newMediaObject" => array(
        "function" => "newMediaObject",
        "signature" => $newMediaObject_sig,
        "docstring" => $newMediaObject_doc
    ),/*
		'blogger.getUserInfo' => array(
			'function' => 'getUserInfo',
			'docstring' => 'Returns information about an author in the system.',
			'signature' => array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString))
		),*/
    'blogger.deletePost' => array(
        "function" => "deletePost",
        "signature" => $deletePost_sig,
        "docstring" => $deletePost_doc
    )
);

$s = new xmlrpc_server($a, false);
$s->setdebug(2);

$s->service();
// that should do all we need!
?>
