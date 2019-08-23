<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: ilya-include/pages/users-newest.php
	Description: Controller for newest users page


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
require_once ILYA__INCLUDE_DIR . 'app/format.php';

// Check we're not using single-sign on integration

if (ILYA__FINAL_EXTERNAL_USERS) {
	ilya_fatal_error('User accounts are handled by external code');
}


// Check we have permission to view this page (moderator or above)

if (ilya_user_permit_error('permit_view_new_users_page')) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}


// Get list of all users

$start = ilya_get_start();
$users = ilya_db_select_with_pending(ilya_db_newest_users_selectspec($start, ilya_opt_if_loaded('page_size_users')));

$userCount = ilya_opt('cache_userpointscount');
$pageSize = ilya_opt('page_size_users');
$users = array_slice($users, 0, $pageSize);
$usersHtml = ilya_userids_handles_html($users);

// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('main/newest_users');

$ilya_content['ranking'] = array(
	'items' => array(),
	'rows' => ceil($pageSize / ilya_opt('columns_users')),
	'type' => 'users',
	'sort' => 'date',
);

if (!empty($users)) {
	foreach ($users as $user) {
		$avatarHtml = ilya_get_user_avatar_html($user['flags'], $user['email'], $user['handle'],
			$user['avatarblobid'], $user['avatarwidth'], $user['avatarheight'], ilya_opt('avatar_users_size'), true);

		$when = ilya_when_to_html($user['created'], 7);
		$ilya_content['ranking']['items'][] = array(
			'avatar' => $avatarHtml,
			'label' => $usersHtml[$user['userid']],
			'score' => $when['data'],
			'raw' => $user,
		);
	}
} else {
	$ilya_content['title'] = ilya_lang_html('main/no_active_users');
}

$ilya_content['canonical'] = ilya_get_canonical();

$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pageSize, $userCount, ilya_opt('pages_prev_next'));

$ilya_content['navigation']['sub'] = ilya_users_sub_navigation();


return $ilya_content;
