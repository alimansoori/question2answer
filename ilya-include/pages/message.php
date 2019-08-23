<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for private messaging page


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
require_once ILYA__INCLUDE_DIR . 'app/limits.php';

$handle = ilya_request_part(1);
$loginuserid = ilya_get_logged_in_userid();
$fromhandle = ilya_get_logged_in_handle();

$ilya_content = ilya_content_prepare();


// Check we have a handle, we're not using ILYA's single-sign on integration and that we're logged in

if (ILYA__FINAL_EXTERNAL_USERS)
	ilya_fatal_error('User accounts are handled by external code');

if (!strlen($handle))
	ilya_redirect('users');

if (!isset($loginuserid)) {
	$ilya_content['error'] = ilya_insert_login_links(ilya_lang_html('misc/message_must_login'), ilya_request());
	return $ilya_content;
}

if ($handle === $fromhandle) {
	// prevent users sending messages to themselves
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}


// Find the user profile and their recent private messages

list($toaccount, $torecent, $fromrecent) = ilya_db_select_with_pending(
	ilya_db_user_account_selectspec($handle, false),
	ilya_db_recent_messages_selectspec($loginuserid, true, $handle, false),
	ilya_db_recent_messages_selectspec($handle, false, $loginuserid, true)
);


// Check the user exists and work out what can and can't be set (if not using single sign-on)

if (!ilya_opt('allow_private_messages') || !is_array($toaccount))
	return include ILYA__INCLUDE_DIR . 'ilya-page-not-found.php';

//  Check the target user has enabled private messages and inform the current user in case they haven't

if ($toaccount['flags'] & ILYA__USER_FLAGS_NO_MESSAGES) {
	$ilya_content['error'] = ilya_lang_html_sub(
		'profile/user_x_disabled_pms',
		sprintf('<a href="%s">%s</a>', ilya_path_html('user/' . $handle), ilya_html($handle))
	);
	return $ilya_content;
}

// Check that we have permission and haven't reached the limit, but don't quit just yet

switch (ilya_user_permit_error(null, ILYA__LIMIT_MESSAGES)) {
	case 'limit':
		$pageerror = ilya_lang_html('misc/message_limit');
		break;

	case false:
		break;

	default:
		$pageerror = ilya_lang_html('users/no_permission');
		break;
}


// Process sending a message to user

// check for messages or errors
$state = ilya_get_state();
$messagesent = $state == 'message-sent';
if ($state == 'email-error')
	$pageerror = ilya_lang_html('main/email_error');

if (ilya_post_text('domessage')) {
	$inmessage = ilya_post_text('message');

	if (isset($pageerror)) {
		// not permitted to post, so quit here
		$ilya_content['error'] = $pageerror;
		return $ilya_content;
	}

	if (!ilya_check_form_security_code('message-' . $handle, ilya_post_text('code')))
		$pageerror = ilya_lang_html('misc/form_security_again');

	else {
		if (empty($inmessage))
			$errors['message'] = ilya_lang('misc/message_empty');

		if (empty($errors)) {
			require_once ILYA__INCLUDE_DIR . 'db/messages.php';
			require_once ILYA__INCLUDE_DIR . 'app/emails.php';

			if (ilya_opt('show_message_history'))
				$messageid = ilya_db_message_create($loginuserid, $toaccount['userid'], $inmessage, '', false);
			else
				$messageid = null;

			$canreply = !(ilya_get_logged_in_flags() & ILYA__USER_FLAGS_NO_MESSAGES);

			$more = strtr(ilya_lang($canreply ? 'emails/private_message_reply' : 'emails/private_message_info'), array(
				'^f_handle' => $fromhandle,
				'^url' => ilya_path_absolute($canreply ? ('message/' . $fromhandle) : ('user/' . $fromhandle)),
			));

			$subs = array(
				'^message' => $inmessage,
				'^f_handle' => $fromhandle,
				'^f_url' => ilya_path_absolute('user/' . $fromhandle),
				'^more' => $more,
				'^a_url' => ilya_path_absolute('account'),
			);

			if (ilya_send_notification($toaccount['userid'], $toaccount['email'], $toaccount['handle'],
				ilya_lang('emails/private_message_subject'), ilya_lang('emails/private_message_body'), $subs))
				$messagesent = true;

			ilya_report_event('u_message', $loginuserid, ilya_get_logged_in_handle(), ilya_cookie_get(), array(
				'userid' => $toaccount['userid'],
				'handle' => $toaccount['handle'],
				'messageid' => $messageid,
				'message' => $inmessage,
			));

			// show message as part of general history
			if (ilya_opt('show_message_history'))
				ilya_redirect(ilya_request(), array('state' => ($messagesent ? 'message-sent' : 'email-error')));
		}
	}
}


// Prepare content for theme

$hideForm = !empty($pageerror) || $messagesent;

$ilya_content['title'] = ilya_lang_html('misc/private_message_title');

$ilya_content['error'] = @$pageerror;

$ilya_content['form_message'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '"',

	'style' => 'tall',

	'ok' => $messagesent ? ilya_lang_html('misc/message_sent') : null,

	'fields' => array(
		'message' => array(
			'type' => $hideForm ? 'static' : '',
			'label' => ilya_lang_html_sub('misc/message_for_x', ilya_get_one_user_html($handle, false)),
			'tags' => 'name="message" id="message"',
			'value' => ilya_html(@$inmessage, $messagesent),
			'rows' => 8,
			'note' => ilya_lang_html_sub('misc/message_explanation', ilya_html(ilya_opt('site_title'))),
			'error' => ilya_html(@$errors['message']),
		),
	),

	'buttons' => array(
		'send' => array(
			'tags' => 'onclick="ilya_show_waiting_after(this, false);"',
			'label' => ilya_lang_html('main/send_button'),
		),
	),

	'hidden' => array(
		'domessage' => '1',
		'code' => ilya_get_form_security_code('message-' . $handle),
	),
);

$ilya_content['focusid'] = 'message';

if ($hideForm) {
	unset($ilya_content['form_message']['buttons']);

	if (ilya_opt('show_message_history'))
		unset($ilya_content['form_message']['fields']['message']);
	else {
		unset($ilya_content['form_message']['fields']['message']['note']);
		unset($ilya_content['form_message']['fields']['message']['label']);
	}
}


// If relevant, show recent message history

if (ilya_opt('show_message_history')) {
	$recent = array_merge($torecent, $fromrecent);

	ilya_sort_by($recent, 'created');

	$showmessages = array_slice(array_reverse($recent, true), 0, ILYA__DB_RETRIEVE_MESSAGES);

	if (count($showmessages)) {
		$ilya_content['message_list'] = array(
			'title' => ilya_lang_html_sub('misc/message_recent_history', ilya_html($toaccount['handle'])),
		);

		$options = ilya_message_html_defaults();

		foreach ($showmessages as $message)
			$ilya_content['message_list']['messages'][] = ilya_message_html_fields($message, $options);
	}

	$ilya_content['navigation']['sub'] = ilya_user_sub_navigation($fromhandle, 'messages', true);
}


$ilya_content['raw']['account'] = $toaccount; // for plugin layers to access

return $ilya_content;
