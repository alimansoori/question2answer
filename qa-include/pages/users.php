<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for top scoring users page


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'db/users.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'app/format.php';


// Get list of all users

$start = ilya_get_start();
$users = ilya_db_select_with_pending(ilya_db_top_users_selectspec($start, ilya_opt_if_loaded('page_size_users')));

$usercount = ilya_opt('cache_userpointscount');
$pagesize = ilya_opt('page_size_users');
$users = array_slice($users, 0, $pagesize);
$usershtml = ilya_userids_handles_html($users);


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('main/highest_users');

$ilya_content['ranking'] = array(
	'items' => array(),
	'rows' => ceil($pagesize / ilya_opt('columns_users')),
	'type' => 'users',
	'sort' => 'points',
);

if (count($users)) {
	foreach ($users as $userid => $user) {
		if (QA_FINAL_EXTERNAL_USERS)
			$avatarhtml = ilya_get_external_avatar_html($user['userid'], ilya_opt('avatar_users_size'), true);
		else {
			$avatarhtml = ilya_get_user_avatar_html($user['flags'], $user['email'], $user['handle'],
				$user['avatarblobid'], $user['avatarwidth'], $user['avatarheight'], ilya_opt('avatar_users_size'), true);
		}

		// avatar and handle now listed separately for use in themes
		$ilya_content['ranking']['items'][] = array(
			'avatar' => $avatarhtml,
			'label' => $usershtml[$user['userid']],
			'score' => ilya_html(ilya_format_number($user['points'], 0, true)),
			'raw' => $user,
		);
	}
} else {
	$ilya_content['title'] = ilya_lang_html('main/no_active_users');
}

$ilya_content['canonical'] = ilya_get_canonical();

$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $usercount, ilya_opt('pages_prev_next'));

$ilya_content['navigation']['sub'] = ilya_users_sub_navigation();


return $ilya_content;
