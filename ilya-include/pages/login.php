<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for login page


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


if (ilya_is_logged_in()) {
	ilya_redirect('');
}

// Check we're not using Q2A's single-sign on integration and that we're not logged in
if (ILYA__FINAL_EXTERNAL_USERS) {
	$request = ilya_request();
	$topath = ilya_get('to'); // lets user switch between login and register without losing destination page
	$userlinks = ilya_get_login_links(ilya_path_to_root(), isset($topath) ? $topath : ilya_path($request, $_GET, ''));

	if (!empty($userlinks['login'])) {
		ilya_redirect_raw($userlinks['login']);
	}
	ilya_fatal_error('User login should be handled by external code');
}


// Process submitted form after checking we haven't reached rate limit

$passwordsent = ilya_get('ps');
$emailexists = ilya_get('ee');

$inemailhandle = ilya_post_text('emailhandle');
$inpassword = ilya_post_text('password');
$inremember = ilya_post_text('remember');

if (ilya_clicked('dologin') && (strlen($inemailhandle) || strlen($inpassword))) {
	require_once ILYA__INCLUDE_DIR . 'app/limits.php';

	if (ilya_user_limits_remaining(ILYA__LIMIT_LOGINS)) {
		require_once ILYA__INCLUDE_DIR . 'db/users.php';
		require_once ILYA__INCLUDE_DIR . 'db/selects.php';

		if (!ilya_check_form_security_code('login', ilya_post_text('code'))) {
			$pageerror = ilya_lang_html('misc/form_security_again');
		}
		else {
			ilya_limits_increment(null, ILYA__LIMIT_LOGINS);

			$errors = array();

			if (ilya_opt('allow_login_email_only') || strpos($inemailhandle, '@') !== false) { // handles can't contain @ symbols
				$matchusers = ilya_db_user_find_by_email($inemailhandle);
			} else {
				$matchusers = ilya_db_user_find_by_handle($inemailhandle);
			}

			if (count($matchusers) == 1) { // if matches more than one (should be impossible), don't log in
				$inuserid = $matchusers[0];
				$userinfo = ilya_db_select_with_pending(ilya_db_user_account_selectspec($inuserid, true));

				$legacyPassOk = hash_equals(strtolower($userinfo['passcheck']), strtolower(ilya_db_calc_passcheck($inpassword, $userinfo['passsalt'])));

				if (ILYA__PASSWORD_HASH) {
					$haspassword = isset($userinfo['passhash']);
					$haspasswordold = isset($userinfo['passsalt']) && isset($userinfo['passcheck']);
					$passOk = password_verify($inpassword, $userinfo['passhash']);

					if (($haspasswordold && $legacyPassOk) || ($haspassword && $passOk)) {
						// upgrade password or rehash, when options like the cost parameter changed
						if ($haspasswordold || password_needs_rehash($userinfo['passhash'], PASSWORD_BCRYPT)) {
							ilya_db_user_set_password($inuserid, $inpassword);
						}
					} else {
						$errors['password'] = ilya_lang('users/password_wrong');
					}
				} else {
					if (!$legacyPassOk) {
						$errors['password'] = ilya_lang('users/password_wrong');
					}
				}

				if (!isset($errors['password'])) {
					// login and redirect
					require_once ILYA__INCLUDE_DIR . 'app/users.php';
					ilya_set_logged_in_user($inuserid, $userinfo['handle'], !empty($inremember));

					$topath = ilya_get('to');

					if (isset($topath))
						ilya_redirect_raw(ilya_path_to_root() . $topath); // path already provided as URL fragment
					elseif ($passwordsent)
						ilya_redirect('account');
					else
						ilya_redirect('');
				}

			} else {
				$errors['emailhandle'] = ilya_lang('users/user_not_found');
			}
		}

	} else {
		$pageerror = ilya_lang('users/login_limit');
	}

} else {
	$inemailhandle = ilya_get('e');
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('users/login_title');

$ilya_content['error'] = @$pageerror;

if (empty($inemailhandle) || isset($errors['emailhandle']))
	$forgotpath = ilya_path('forgot');
else
	$forgotpath = ilya_path('forgot', array('e' => $inemailhandle));

$forgothtml = '<a href="' . ilya_html($forgotpath) . '">' . ilya_lang_html('users/forgot_link') . '</a>';

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '"',

	'style' => 'tall',

	'ok' => $passwordsent ? ilya_lang_html('users/password_sent') : ($emailexists ? ilya_lang_html('users/email_exists') : null),

	'fields' => array(
		'email_handle' => array(
			'label' => ilya_opt('allow_login_email_only') ? ilya_lang_html('users/email_label') : ilya_lang_html('users/email_handle_label'),
			'tags' => 'name="emailhandle" id="emailhandle" dir="auto"',
			'value' => ilya_html(@$inemailhandle),
			'error' => ilya_html(@$errors['emailhandle']),
		),

		'password' => array(
			'type' => 'password',
			'label' => ilya_lang_html('users/password_label'),
			'tags' => 'name="password" id="password" dir="auto"',
			'value' => ilya_html(@$inpassword),
			'error' => empty($errors['password']) ? '' : (ilya_html(@$errors['password']) . ' - ' . $forgothtml),
			'note' => $passwordsent ? ilya_lang_html('users/password_sent') : $forgothtml,
		),

		'remember' => array(
			'type' => 'checkbox',
			'label' => ilya_lang_html('users/remember_label'),
			'tags' => 'name="remember"',
			'value' => !empty($inremember),
		),
	),

	'buttons' => array(
		'login' => array(
			'label' => ilya_lang_html('users/login_button'),
		),
	),

	'hidden' => array(
		'dologin' => '1',
		'code' => ilya_get_form_security_code('login'),
	),
);

$loginmodules = ilya_load_modules_with('login', 'login_html');

foreach ($loginmodules as $module) {
	ob_start();
	$module->login_html(ilya_opt('site_url') . ilya_get('to'), 'login');
	$html = ob_get_clean();

	if (strlen($html))
		@$ilya_content['custom'] .= '<br>' . $html . '<br>';
}

$ilya_content['focusid'] = (isset($inemailhandle) && !isset($errors['emailhandle'])) ? 'password' : 'emailhandle';


return $ilya_content;
