<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for admin page showing new users waiting for approval


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

if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

require_once ILYA_INCLUDE_DIR . 'app/admin.php';
require_once ILYA_INCLUDE_DIR . 'db/admin.php';


// Check we're not using single-sign on integration

if (ILYA_FINAL_EXTERNAL_USERS)
	ilya_fatal_error('User accounts are handled by external code');


// Find most flagged questions, answers, comments

$userid = ilya_get_logged_in_userid();

$users = ilya_db_get_unapproved_users(ilya_opt('page_size_users'));
$userfields = ilya_db_select_with_pending(ilya_db_userfields_selectspec());


// Check admin privileges (do late to allow one DB query)

if (ilya_get_logged_in_level() < ILYA_USER_LEVEL_MODERATOR) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}


// Check to see if any were approved or blocked here

$pageerror = ilya_admin_check_clicks();


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/approve_users_title');
$ilya_content['error'] = isset($pageerror) ? $pageerror : ilya_admin_page_error();

$ilya_content['message_list'] = array(
	'form' => array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'hidden' => array(
			'code' => ilya_get_form_security_code('admin/click'),
		),
	),

	'messages' => array(),
);


if (count($users)) {
	foreach ($users as $user) {
		$message = array();

		$message['tags'] = 'id="p' . ilya_html($user['userid']) . '"'; // use p prefix for ilya_admin_click() in ilya-admin.js

		$message['content'] = ilya_lang_html('users/registered_label') . ' ' .
			strtr(ilya_lang_html('users/x_ago_from_y'), array(
				'^1' => ilya_time_to_string(ilya_opt('db_time') - $user['created']),
				'^2' => ilya_ip_anchor_html(@inet_ntop($user['createip'])),
			)) . '<br/>';

		$htmlemail = ilya_html($user['email']);

		$message['content'] .= ilya_lang_html('users/email_label') . ' <a href="mailto:' . $htmlemail . '">' . $htmlemail . '</a>';

		if (ilya_opt('confirm_user_emails')) {
			$message['content'] .= '<small> - ' . ilya_lang_html(($user['flags'] & ILYA_USER_FLAGS_EMAIL_CONFIRMED) ? 'users/email_confirmed' : 'users/email_not_confirmed') . '</small>';
		}

		foreach ($userfields as $userfield) {
			if (strlen(@$user['profile'][$userfield['title']]))
				$message['content'] .= '<br/>' . ilya_html($userfield['content'] . ': ' . $user['profile'][$userfield['title']]);
		}

		$message['meta_order'] = ilya_lang_html('main/meta_order');
		$message['who']['data'] = ilya_get_one_user_html($user['handle']);

		$message['form'] = array(
			'style' => 'light',

			'buttons' => array(
				'approve' => array(
					'tags' => 'name="admin_' . $user['userid'] . '_userapprove" onclick="return ilya_admin_click(this);"',
					'label' => ilya_lang_html('question/approve_button'),
					'popup' => ilya_lang_html('admin/approve_user_popup'),
				),

				'block' => array(
					'tags' => 'name="admin_' . $user['userid'] . '_userblock" onclick="return ilya_admin_click(this);"',
					'label' => ilya_lang_html('admin/block_button'),
					'popup' => ilya_lang_html('admin/block_user_popup'),
				),
			),
		);

		$ilya_content['message_list']['messages'][] = $message;
	}

} else
	$ilya_content['title'] = ilya_lang_html('admin/no_unapproved_found');


$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();
$ilya_content['script_rel'][] = 'ilya-content/ilya-admin.js?' . ILYA_VERSION;


return $ilya_content;
