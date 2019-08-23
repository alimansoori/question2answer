<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for admin page showing users with non-standard privileges


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

require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';


// Check we're not using single-sign on integration

if (ILYA__FINAL_EXTERNAL_USERS) {
	ilya_fatal_error('User accounts are handled by external code');
}


// Get list of special users

$users = ilya_db_select_with_pending(ilya_db_users_from_level_selectspec(ILYA__USER_LEVEL_EXPERT));


// Check we have permission to view this page (moderator or above)

if (ilya_user_permit_error('permit_view_special_users_page')) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}


// Get userids and handles of retrieved users

$usershtml = ilya_userids_handles_html($users);


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('users/special_users');

$ilya_content['ranking'] = array(
	'items' => array(),
	'rows' => ceil(ilya_opt('page_size_users') / ilya_opt('columns_users')),
	'type' => 'users',
	'sort' => 'level',
);

foreach ($users as $user) {
	$ilya_content['ranking']['items'][] = array(
		'label' => $usershtml[$user['userid']],
		'score' => ilya_html(ilya_user_level_string($user['level'])),
		'raw' => $user,
	);
}

$ilya_content['navigation']['sub'] = ilya_users_sub_navigation();


return $ilya_content;
