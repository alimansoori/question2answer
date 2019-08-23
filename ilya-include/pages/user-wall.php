<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for user page showing all user wall posts


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
require_once ILYA__INCLUDE_DIR . 'app/messages.php';


// Check we're not using single-sign on integration, which doesn't allow walls

if (ILYA__FINAL_EXTERNAL_USERS)
	ilya_fatal_error('User accounts are handled by external code');


// $handle, $userhtml are already set by /ilya-include/page/user.php

$start = ilya_get_start();


// Find the questions for this user

list($useraccount, $usermessages) = ilya_db_select_with_pending(
	ilya_db_user_account_selectspec($handle, false),
	ilya_db_recent_messages_selectspec(null, null, $handle, false, ilya_opt_if_loaded('page_size_wall'), $start)
);

if (!is_array($useraccount)) // check the user exists
	return include ILYA__INCLUDE_DIR . 'ilya-page-not-found.php';


// Perform pagination

$pagesize = ilya_opt('page_size_wall');
$count = $useraccount['wallposts'];
$loginuserid = ilya_get_logged_in_userid();

$usermessages = array_slice($usermessages, 0, $pagesize);
$usermessages = ilya_wall_posts_add_rules($usermessages, $start);


// Process deleting or adding a wall post (similar but not identical code to qq-page-user-profile.php)

$errors = array();

$wallposterrorhtml = ilya_wall_error_html($loginuserid, $useraccount['userid'], $useraccount['flags']);

foreach ($usermessages as $message) {
	if ($message['deleteable'] && ilya_clicked('m' . $message['messageid'] . '_dodelete')) {
		if (!ilya_check_form_security_code('wall-' . $useraccount['handle'], ilya_post_text('code'))) {
			$errors['page'] = ilya_lang_html('misc/form_security_again');
		} else {
			ilya_wall_delete_post($loginuserid, ilya_get_logged_in_handle(), ilya_cookie_get(), $message);
			ilya_redirect(ilya_request(), $_GET);
		}
	}
}

if (ilya_clicked('dowallpost')) {
	$inmessage = ilya_post_text('message');

	if (!strlen($inmessage)) {
		$errors['message'] = ilya_lang('profile/post_wall_empty');
	} elseif (!ilya_check_form_security_code('wall-' . $useraccount['handle'], ilya_post_text('code'))) {
		$errors['message'] = ilya_lang_html('misc/form_security_again');
	} elseif (!$wallposterrorhtml) {
		ilya_wall_add_post($loginuserid, ilya_get_logged_in_handle(), ilya_cookie_get(), $useraccount['userid'], $useraccount['handle'], $inmessage, '');
		ilya_redirect(ilya_request());
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html_sub('profile/wall_for_x', $userhtml);
$ilya_content['error'] = @$errors['page'];

$ilya_content['message_list'] = array(
	'tags' => 'id="wallmessages"',

	'form' => array(
		'tags' => 'name="wallpost" method="post" action="' . ilya_self_html() . '"',
		'style' => 'tall',
		'hidden' => array(
			'ilya_click' => '', // for simulating clicks in Javascript
			'handle' => ilya_html($useraccount['handle']),
			'start' => ilya_html($start),
			'code' => ilya_get_form_security_code('wall-' . $useraccount['handle']),
		),
	),

	'messages' => array(),
);

if ($start == 0) { // only allow posting on first page
	if ($wallposterrorhtml) {
		$ilya_content['message_list']['error'] = $wallposterrorhtml; // an error that means we are not allowed to post
	} else {
		$ilya_content['message_list']['form']['fields'] = array(
			'message' => array(
				'tags' => 'name="message" id="message"',
				'value' => ilya_html(@$inmessage, false),
				'rows' => 2,
				'error' => ilya_html(@$errors['message']),
			),
		);

		$ilya_content['message_list']['form']['buttons'] = array(
			'post' => array(
				'tags' => 'name="dowallpost" onclick="return ilya_submit_wall_post(this, false);"',
				'label' => ilya_lang_html('profile/post_wall_button'),
			),
		);
	}
}

foreach ($usermessages as $message) {
	$ilya_content['message_list']['messages'][] = ilya_wall_post_view($message);
}

$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $count, ilya_opt('pages_prev_next'));


// Sub menu for navigation in user pages

$ismyuser = isset($loginuserid) && $loginuserid == (ILYA__FINAL_EXTERNAL_USERS ? $userid : $useraccount['userid']);
$ilya_content['navigation']['sub'] = ilya_user_sub_navigation($handle, 'wall', $ismyuser);


return $ilya_content;
