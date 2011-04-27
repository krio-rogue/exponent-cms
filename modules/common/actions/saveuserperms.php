<?php

##################################################
#
# Copyright (c) 2004-2006 OIC Group, Inc.
# Written and Designed by James Hunt
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

if (!defined('EXPONENT')) exit('');

if (exponent_permissions_check('administrate',$loc)) {
	if (!defined('SYS_USERS')) include_once(BASE.'subsystems/users.php');
	foreach ($_POST['permdata'] as $k => $user_str) {
		$perms = array_keys($user_str);
		$u = exponent_users_getUserById($k);
		$locarray = array();
		if ($loc->mod == 'navigationmodule' && !empty($perms[1]) && $perms[1] == 'manage') {
			$sections = navigationmodule::levelTemplate($loc->int);
			$locarray[] = $loc;
			foreach ($sections as $section) {
				$locarray[] = exponent_core_makeLocation('navigationmodule', null, $section->id);
			}
		} else {
			$locarray[] = $loc;
		}

		foreach ($locarray as $location) {
			exponent_permissions_revokeAll($u,$location);
			for ($i = 0; $i < count($perms); $i++) {
				exponent_permissions_grant($u,$perms[$i],$location);
			}
		}

		if ($k == $user->id) {
			exponent_permissions_load($user);
		}
	}
	
	exponent_permissions_triggerRefresh();
    exponent_flow_redirect();
} else {
	echo SITE_403_HTML;
}

?>
