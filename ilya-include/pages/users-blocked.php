<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for page showing users who have been blocked


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: https://projekt.ir/license.php
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


// Get list of blocked users

$start = ilya_get_start();
$pagesize = ilya_opt('page_size_users');

$userSpecCount = ilya_db_selectspec_count(ilya_db_users_with_flag_selectspec(ILYA__USER_FLAGS_USER_BLOCKED));
$userSpec = ilya_db_users_with_flag_selectspec(ILYA__USER_FLAGS_USER_BLOCKED, $start, $pagesize);

list($numUsers, $users) = ilya_db_select_with_pending($userSpecCount, $userSpec);
$count = $numUsers['count'];


// Check we have permission to view this page (moderator or above)

if (ilya_get_logged_in_level() < ILYA__USER_LEVEL_MODERATOR) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}


// Get userids and handles of retrieved users

$usershtml = ilya_userids_handles_html($users);


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = $count > 0 ? ilya_lang_html('users/blocked_users') : ilya_lang_html('users/no_blocked_users');

$ilya_content['ranking'] = array(
	'items' => array(),
	'rows' => ceil(count($users) / ilya_opt('columns_users')),
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

$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $count, ilya_opt('pages_prev_next'));

$ilya_content['navigation']['sub'] = ilya_users_sub_navigation();


return $ilya_content;
