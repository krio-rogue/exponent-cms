<?php

##################################################
#
# Copyright (c) 2004-2012 OIC Group, Inc.
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

/**
 * Smarty plugin
 * @package Smarty-Plugins
 * @subpackage Block
 */

/**
 * Smarty {toggle} block plugin
 *
 * Type:     block<br>
 * Name:     toggle<br>
 * Purpose:  Set up a toggle block
 *
 * @param $params
 * @param $content
 * @param \Smarty $smarty
 * @param $repeat
 */
function smarty_block_toggle($params,$content,&$smarty, &$repeat) {
    if (empty($params['unique'])) die("<strong style='color:red'>".gt("The 'unique' parameter is required for the {toggle} plugin.")."</strong>");
    if (empty($params['title']) && empty($params['link'])) die("<strong style='color:red'>".gt("The 'title' parameter is required for the {toggle} plugin.")."</strong>");

	if(empty($content)) {
        if (!empty($params['link'])) $params['title'] = $params['link'];
        echo '<div id="'.$params['unique'].'" class="yui3-module">
            <div id="head" class="yui3-hd">
                <h2 title="'.gt('Click to Collapse/Expand').'">'.$params['title'].'</h2>
                <a title="'.gt('Collapse/Expand').'" class="yui3-toggle"></a>
            </div>
            <div class="yui3-bd">
        ';
	} else {
		echo $content;	
		echo '</div></div>';

        $script = "
    YUI(EXPONENT.YUI3_CONFIG).use('anim', function(Y) {
        var module = Y.one('#".$params['unique']."');

        // add fx plugin to module body
        var content = module.one('.yui3-bd').plug(Y.Plugin.NodeFX, {
            from: { height: 0 },
            to: {
                height: function(node) { // dynamic in case of change
                    return node.get('scrollHeight'); // get expanded height (offsetHeight may be zero)
                }
            },

            easing: Y.Easing.easeOut,
            duration: 0.5
        });

        var onClick = function(e) {
            e.preventDefault();
            module.toggleClass('yui3-closed');
            content.fx.set('reverse', !content.fx.get('reverse')); // toggle reverse
            content.fx.run();
        };

        module.one('#head').on('click', onClick);
        ";

        if (!empty($params['collapsed']))$script .= "
        // start w/ item collapsed
        module.toggleClass('yui3-closed');
        content.fx.set('reverse', !content.fx.get('reverse')); // toggle reverse
        content.fx.run();
        ";

        $script .= "
    });
            ";

        expJavascript::pushToFoot(array(
            "unique"  => 'toggle-' . $params['unique'],
            "yui3mods"=> 1,
            "content" => $script,
        ));
        expCSS::pushToHead(array(
            "unique"=>'toggle',
            "corecss"=>"toggle",
            )
        );
    }

}

?>

