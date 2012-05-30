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
 * @subpackage Upgrade
 * @package Installation
 */

/**
 * This is the class update_profile_paths
 */
class fix_faq_filedownload_modules extends upgradescript {
	protected $from_version = '1.99.0';  // version number lower than first released version, 2.0.0
	protected $to_version = '2.0.6';  // faq & filedownload names were changed in 2.0.6

	/**
	 * name/title of upgrade script
	 * @return string
	 */
	static function name() { return "Update faq and filedownloads modules with correct spelling"; }

	/**
	 * generic description of upgrade script
	 * @return string
	 */
	function description() { return "Prior to v2.0.6, the faq and filedownload modules were plural in some cases and singular in others which prevented full integration.  This script updates existing tables."; }

	/**
	 * additional test(s) to see if upgrade script should be run
	 * @return bool
	 */
	function needed() {
		return true;  // we'll just do it ine very instance instead of testing if user profile extensions are active
	}

	/**
	 * coverts all headline modules/items into text modules/items and deletes headline controller files
	 * @return bool
	 */
	function upgrade() {
	    global $db;

        if ($db->tableExists('faqs')) {
            if ($db->tableExists('faq') && !$db->countObjects('faq')) {
                $db->dropTable('faq');
            }
            if (!$db->tableExists('faq')) {
                $db->sql('RENAME TABLE '.DB_TABLE_PREFIX.'_faqs TO '.DB_TABLE_PREFIX.'_faq');
            }
            if ($db->tableExists('faqs') && !$db->countObjects('faqs')) {
                $db->dropTable('faqs');
            }
        }
        if ($db->tableExists('filedownloads')) {
            if ($db->tableExists('filedownload') && !$db->countObjects('filedownload')) {
                $db->dropTable('filedownload');
            }
            if (!$db->tableExists('filedownload')) {
                $db->sql('RENAME TABLE '.DB_TABLE_PREFIX.'_filedownloads TO '.DB_TABLE_PREFIX.'_filedownload');
            }
            if ($db->tableExists('filedownloads') && !$db->countObjects('filedownloads')) {
                $db->dropTable('filedownloads');
            }
        }
        return gt('faq & filedownload tables are now correctly named.');
	}
}

?>
