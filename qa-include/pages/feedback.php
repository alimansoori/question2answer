<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for feedback page


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

require_once ILYA__INCLUDE_DIR . 'app/captcha.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';


// Get useful information on the logged in user

$userid = ilya_get_logged_in_userid();

if (isset($userid) && !ILYA__FINAL_EXTERNAL_USERS) {
	list($useraccount, $userprofile) = ilya_db_select_with_pending(
		ilya_db_user_account_selectspec($userid, true),
		ilya_db_user_profile_selectspec($userid, true)
	);
}

$usecaptcha = ilya_opt('captcha_on_feedback') && ilya_user_use_captcha();


// Check feedback is enabled and the person isn't blocked

if (!ilya_opt('feedback_enabled'))
	return include ILYA__INCLUDE_DIR . 'ilya-page-not-found.php';

if (ilya_user_permit_error()) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}


// Send the feedback form


$feedbacksent = false;

if (ilya_clicked('dofeedback')) {
	require_once ILYA__INCLUDE_DIR . 'app/emails.php';
	require_once ILYA__INCLUDE_DIR . 'util/string.php';

	$inmessage = ilya_post_text('message');
	$inname = ilya_post_text('name');
	$inemail = ilya_post_text('email');
	$inreferer = ilya_post_text('referer');

	if (!ilya_check_form_security_code('feedback', ilya_post_text('code')))
		$pageerror = ilya_lang_html('misc/form_security_again');

	else {
		if (empty($inmessage))
			$errors['message'] = ilya_lang('misc/feedback_empty');

		if ($usecaptcha)
			ilya_captcha_validate_post($errors);

		if (empty($errors)) {
			$subs = array(
				'^message' => $inmessage,
				'^name' => empty($inname) ? '-' : $inname,
				'^email' => empty($inemail) ? '-' : $inemail,
				'^previous' => empty($inreferer) ? '-' : $inreferer,
				'^url' => isset($userid) ? ilya_path_absolute('user/' . ilya_get_logged_in_handle()) : '-',
				'^ip' => ilya_remote_ip_address(),
				'^browser' => @$_SERVER['HTTP_USER_AGENT'],
			);

			if (ilya_send_email(array(
				'fromemail' => ilya_opt('from_email'),
				'fromname' => $inname,
				'replytoemail' => ilya_email_validate(@$inemail) ? $inemail : null,
				'replytoname' => $inname,
				'toemail' => ilya_opt('feedback_email'),
				'toname' => ilya_opt('site_title'),
				'subject' => ilya_lang_sub('emails/feedback_subject', ilya_opt('site_title')),
				'body' => strtr(ilya_lang('emails/feedback_body'), $subs),
				'html' => false,
			))) {
				$feedbacksent = true;
			} else {
				$pageerror = ilya_lang_html('main/general_error');
			}

			ilya_report_event('feedback', $userid, ilya_get_logged_in_handle(), ilya_cookie_get(), array(
				'email' => $inemail,
				'name' => $inname,
				'message' => $inmessage,
				'previous' => $inreferer,
				'browser' => @$_SERVER['HTTP_USER_AGENT'],
			));
		}
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('misc/feedback_title');

$ilya_content['error'] = @$pageerror;

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '"',

	'style' => 'tall',

	'fields' => array(
		'message' => array(
			'type' => $feedbacksent ? 'static' : '',
			'label' => ilya_lang_html_sub('misc/feedback_message', ilya_opt('site_title')),
			'tags' => 'name="message" id="message"',
			'value' => ilya_html(@$inmessage),
			'rows' => 8,
			'error' => ilya_html(@$errors['message']),
		),

		'name' => array(
			'type' => $feedbacksent ? 'static' : '',
			'label' => ilya_lang_html('misc/feedback_name'),
			'tags' => 'name="name"',
			'value' => ilya_html(isset($inname) ? $inname : @$userprofile['name']),
		),

		'email' => array(
			'type' => $feedbacksent ? 'static' : '',
			'label' => ilya_lang_html('misc/feedback_email'),
			'tags' => 'name="email"',
			'value' => ilya_html(isset($inemail) ? $inemail : ilya_get_logged_in_email()),
			'note' => $feedbacksent ? null : ilya_opt('email_privacy'),
		),
	),

	'buttons' => array(
		'send' => array(
			'label' => ilya_lang_html('main/send_button'),
		),
	),

	'hidden' => array(
		'dofeedback' => '1',
		'code' => ilya_get_form_security_code('feedback'),
		'referer' => ilya_html(isset($inreferer) ? $inreferer : @$_SERVER['HTTP_REFERER']),
	),
);

if ($usecaptcha && !$feedbacksent)
	ilya_set_up_captcha_field($ilya_content, $ilya_content['form']['fields'], @$errors);


$ilya_content['focusid'] = 'message';

if ($feedbacksent) {
	$ilya_content['form']['ok'] = ilya_lang_html('misc/feedback_sent');
	unset($ilya_content['form']['buttons']);
}


return $ilya_content;
