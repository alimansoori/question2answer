<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for logout page (not much to do)


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


if (ILYA__FINAL_EXTERNAL_USERS) {
	$request = ilya_request();
	$topath = ilya_get('to'); // lets user switch between login and register without losing destination page
	$userlinks = ilya_get_login_links(ilya_path_to_root(), isset($topath) ? $topath : ilya_path($request, $_GET, ''));

	if (!empty($userlinks['logout'])) {
		ilya_redirect_raw($userlinks['logout']);
	}
	ilya_fatal_error('User logout should be handled by external code');
}

if (ilya_is_logged_in()) {
	ilya_set_logged_in_user(null);
}

ilya_redirect(''); // back to home page
