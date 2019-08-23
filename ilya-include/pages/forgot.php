<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for 'forgot my password' page


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

require_once ILYA__INCLUDE_DIR . 'db/users.php';
require_once ILYA__INCLUDE_DIR . 'app/captcha.php';


// Check we're not using single-sign on integration and that we're not logged in

if (ILYA__FINAL_EXTERNAL_USERS)
	ilya_fatal_error('User login is handled by external code');

if (ilya_is_logged_in())
	ilya_redirect('');


// Start the 'I forgot my password' process, sending email if appropriate

if (ilya_clicked('doforgot')) {
	require_once ILYA__INCLUDE_DIR . 'app/users-edit.php';

	$inemailhandle = ilya_post_text('emailhandle');

	$errors = array();

	if (!ilya_check_form_security_code('forgot', ilya_post_text('code')))
		$errors['page'] = ilya_lang_html('misc/form_security_again');

	else {
		if (strpos($inemailhandle, '@') === false) { // handles can't contain @ symbols
			$matchusers = ilya_db_user_find_by_handle($inemailhandle);
			$passemailhandle = !ilya_opt('allow_login_email_only');
		} else {
			$matchusers = ilya_db_user_find_by_email($inemailhandle);
			$passemailhandle = true;
		}

		if (count($matchusers) != 1 || !$passemailhandle) // if we get more than one match (should be impossible) also give an error
			$errors['emailhandle'] = ilya_lang('users/user_not_found');

		if (ilya_opt('captcha_on_reset_password'))
			ilya_captcha_validate_post($errors);

		if (empty($errors)) {
			$inuserid = $matchusers[0];
			ilya_start_reset_user($inuserid);
			ilya_redirect('reset', $passemailhandle ? array('e' => $inemailhandle, 's' => '1') : null); // redirect to page where code is entered
		}
	}

} else
	$inemailhandle = ilya_get('e');


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('users/reset_title');
$ilya_content['error'] = @$errors['page'];

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '"',

	'style' => 'tall',

	'fields' => array(
		'email_handle' => array(
			'label' => ilya_opt('allow_login_email_only') ? ilya_lang_html('users/email_label') : ilya_lang_html('users/email_handle_label'),
			'tags' => 'name="emailhandle" id="emailhandle"',
			'value' => ilya_html(@$inemailhandle),
			'error' => ilya_html(@$errors['emailhandle']),
			'note' => ilya_lang_html('users/send_reset_note'),
		),
	),

	'buttons' => array(
		'send' => array(
			'label' => ilya_lang_html('users/send_reset_button'),
		),
	),

	'hidden' => array(
		'doforgot' => '1',
		'code' => ilya_get_form_security_code('forgot'),
	),
);

if (ilya_opt('captcha_on_reset_password'))
	ilya_set_up_captcha_field($ilya_content, $ilya_content['form']['fields'], @$errors);

$ilya_content['focusid'] = 'emailhandle';


return $ilya_content;
