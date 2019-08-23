<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for register page


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
require_once ILYA__INCLUDE_DIR . 'db/users.php';


if (ilya_is_logged_in()) {
	ilya_redirect('');
}

// Check we're not using single-sign on integration, that we're not logged in, and we're not blocked
if (ILYA__FINAL_EXTERNAL_USERS) {
	$request = ilya_request();
	$topath = ilya_get('to'); // lets user switch between login and register without losing destination page
	$userlinks = ilya_get_login_links(ilya_path_to_root(), isset($topath) ? $topath : ilya_path($request, $_GET, ''));

	if (!empty($userlinks['register'])) {
		ilya_redirect_raw($userlinks['register']);
	}
	ilya_fatal_error('User registration should be handled by external code');
}


// Get information about possible additional fields

$show_terms = ilya_opt('show_register_terms');

$userfields = ilya_db_select_with_pending(
	ilya_db_userfields_selectspec()
);

foreach ($userfields as $index => $userfield) {
	if (!($userfield['flags'] & ILYA__FIELD_FLAGS_ON_REGISTER))
		unset($userfields[$index]);
}


// Check we haven't suspended registration, and this IP isn't blocked

if (ilya_opt('suspend_register_users')) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/register_suspended');
	return $ilya_content;
}

if (ilya_user_permit_error()) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}


// Process submitted form

if (ilya_clicked('doregister')) {
	require_once ILYA__INCLUDE_DIR . 'app/limits.php';

	if (ilya_user_limits_remaining(ILYA__LIMIT_REGISTRATIONS)) {
		require_once ILYA__INCLUDE_DIR . 'app/users-edit.php';

		$inemail = ilya_post_text('email');
		$inpassword = ilya_post_text('password');
		$inhandle = ilya_post_text('handle');
		$interms = (int)ilya_post_text('terms');

		$inprofile = array();
		foreach ($userfields as $userfield)
			$inprofile[$userfield['fieldid']] = ilya_post_text('field_' . $userfield['fieldid']);

		if (!ilya_check_form_security_code('register', ilya_post_text('code'))) {
			$pageerror = ilya_lang_html('misc/form_security_again');
		} else {
			// core validation
			$errors = array_merge(
				ilya_handle_email_filter($inhandle, $inemail),
				ilya_password_validate($inpassword)
			);

			// T&Cs validation
			if ($show_terms && !$interms)
				$errors['terms'] = ilya_lang_html('users/terms_not_accepted');

			// filter module validation
			if (count($inprofile)) {
				$filtermodules = ilya_load_modules_with('filter', 'filter_profile');
				foreach ($filtermodules as $filtermodule)
					$filtermodule->filter_profile($inprofile, $errors, null, null);
			}

			if (ilya_opt('captcha_on_register'))
				ilya_captcha_validate_post($errors);

			if (empty($errors)) {
				// register and redirect
				ilya_limits_increment(null, ILYA__LIMIT_REGISTRATIONS);

				$userid = ilya_create_new_user($inemail, $inpassword, $inhandle);

				foreach ($userfields as $userfield)
					ilya_db_user_profile_set($userid, $userfield['title'], $inprofile[$userfield['fieldid']]);

				ilya_set_logged_in_user($userid, $inhandle);

				$topath = ilya_get('to');

				if (isset($topath))
					ilya_redirect_raw(ilya_path_to_root() . $topath); // path already provided as URL fragment
				else
					ilya_redirect('');
			}
		}

	} else
		$pageerror = ilya_lang('users/register_limit');
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('users/register_title');

$ilya_content['error'] = @$pageerror;

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '"',

	'style' => 'tall',

	'fields' => array(
		'handle' => array(
			'label' => ilya_lang_html('users/handle_label'),
			'tags' => 'name="handle" id="handle" dir="auto"',
			'value' => ilya_html(@$inhandle),
			'error' => ilya_html(@$errors['handle']),
		),

		'password' => array(
			'type' => 'password',
			'label' => ilya_lang_html('users/password_label'),
			'tags' => 'name="password" id="password" dir="auto"',
			'value' => ilya_html(@$inpassword),
			'error' => ilya_html(@$errors['password']),
		),

		'email' => array(
			'label' => ilya_lang_html('users/email_label'),
			'tags' => 'name="email" id="email" dir="auto"',
			'value' => ilya_html(@$inemail),
			'note' => ilya_opt('email_privacy'),
			'error' => ilya_html(@$errors['email']),
		),
	),

	'buttons' => array(
		'register' => array(
			'tags' => 'onclick="ilya_show_waiting_after(this, false);"',
			'label' => ilya_lang_html('users/register_button'),
		),
	),

	'hidden' => array(
		'doregister' => '1',
		'code' => ilya_get_form_security_code('register'),
	),
);

// prepend custom message
$custom = ilya_opt('show_custom_register') ? trim(ilya_opt('custom_register')) : '';
if (strlen($custom)) {
	array_unshift($ilya_content['form']['fields'], array(
		'type' => 'custom',
		'note' => $custom,
	));
}

foreach ($userfields as $userfield) {
	$value = @$inprofile[$userfield['fieldid']];

	$label = trim(ilya_user_userfield_label($userfield), ':');
	if (strlen($label))
		$label .= ':';

	$ilya_content['form']['fields'][$userfield['title']] = array(
		'label' => ilya_html($label),
		'tags' => 'name="field_' . $userfield['fieldid'] . '"',
		'value' => ilya_html($value),
		'error' => ilya_html(@$errors[$userfield['fieldid']]),
		'rows' => ($userfield['flags'] & ILYA__FIELD_FLAGS_MULTI_LINE) ? 8 : null,
	);
}

if (ilya_opt('captcha_on_register'))
	ilya_set_up_captcha_field($ilya_content, $ilya_content['form']['fields'], @$errors);

// show T&Cs checkbox
if ($show_terms) {
	$ilya_content['form']['fields']['terms'] = array(
		'type' => 'checkbox',
		'label' => trim(ilya_opt('register_terms')),
		'tags' => 'name="terms" id="terms"',
		'value' => ilya_html(@$interms),
		'error' => ilya_html(@$errors['terms']),
	);
}

$loginmodules = ilya_load_modules_with('login', 'login_html');

foreach ($loginmodules as $module) {
	ob_start();
	$module->login_html(ilya_opt('site_url') . ilya_get('to'), 'register');
	$html = ob_get_clean();

	if (strlen($html))
		@$ilya_content['custom'] .= '<br>' . $html . '<br>';
}

// prioritize 'handle' for keyboard focus
$ilya_content['focusid'] = isset($errors['handle']) ? 'handle'
	: (isset($errors['password']) ? 'password'
		: (isset($errors['email']) ? 'email' : 'handle'));


return $ilya_content;
