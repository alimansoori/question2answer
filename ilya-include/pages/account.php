<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for user account page


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
	header('Location: ../../');
	exit;
}

require_once ILYA_INCLUDE_DIR . 'db/users.php';
require_once ILYA_INCLUDE_DIR . 'app/format.php';
require_once ILYA_INCLUDE_DIR . 'app/users.php';
require_once ILYA_INCLUDE_DIR . 'db/selects.php';
require_once ILYA_INCLUDE_DIR . 'util/image.php';


// Check we're not using single-sign on integration, that we're logged in

if (ILYA_FINAL_EXTERNAL_USERS)
	ilya_fatal_error('User accounts are handled by external code');

$userid = ilya_get_logged_in_userid();

if (!isset($userid))
	ilya_redirect('login');


// Get current information on user

list($useraccount, $userprofile, $userpoints, $userfields) = ilya_db_select_with_pending(
	ilya_db_user_account_selectspec($userid, true),
	ilya_db_user_profile_selectspec($userid, true),
	ilya_db_user_points_selectspec($userid, true),
	ilya_db_userfields_selectspec()
);

$changehandle = ilya_opt('allow_change_usernames') || (!$userpoints['qposts'] && !$userpoints['aposts'] && !$userpoints['cposts']);
$doconfirms = ilya_opt('confirm_user_emails') && $useraccount['level'] < ILYA_USER_LEVEL_EXPERT;
$isconfirmed = ($useraccount['flags'] & ILYA_USER_FLAGS_EMAIL_CONFIRMED) ? true : false;

$haspasswordold = isset($useraccount['passsalt']) && isset($useraccount['passcheck']);
if (ILYA_PASSWORD_HASH) {
	$haspassword = isset($useraccount['passhash']);
} else {
	$haspassword = $haspasswordold;
}
$permit_error = ilya_user_permit_error();
$isblocked = $permit_error !== false;
$pending_confirmation = $doconfirms && !$isconfirmed;

// Process profile if saved

// If the post_max_size is exceeded then the $_POST array is empty so no field processing can be done
if (ilya_post_limit_exceeded())
	$errors['avatar'] = ilya_lang('main/file_upload_limit_exceeded');
else {
	require_once ILYA_INCLUDE_DIR . 'app/users-edit.php';

	if (ilya_clicked('dosaveprofile') && !$isblocked) {
		$inhandle = $changehandle ? ilya_post_text('handle') : $useraccount['handle'];
		$inemail = ilya_post_text('email');
		$inmessages = ilya_post_text('messages');
		$inwallposts = ilya_post_text('wall');
		$inmailings = ilya_post_text('mailings');
		$inavatar = ilya_post_text('avatar');

		$inprofile = array();
		foreach ($userfields as $userfield)
			$inprofile[$userfield['fieldid']] = ilya_post_text('field_' . $userfield['fieldid']);

		if (!ilya_check_form_security_code('account', ilya_post_text('code')))
			$errors['page'] = ilya_lang_html('misc/form_security_again');
		else {
			$errors = ilya_handle_email_filter($inhandle, $inemail, $useraccount);

			if (!isset($errors['handle']))
				ilya_db_user_set($userid, 'handle', $inhandle);

			if (!isset($errors['email']) && $inemail !== $useraccount['email']) {
				ilya_db_user_set($userid, 'email', $inemail);
				ilya_db_user_set_flag($userid, ILYA_USER_FLAGS_EMAIL_CONFIRMED, false);
				$isconfirmed = false;

				if ($doconfirms)
					ilya_send_new_confirm($userid);
			}

			if (ilya_opt('allow_private_messages'))
				ilya_db_user_set_flag($userid, ILYA_USER_FLAGS_NO_MESSAGES, !$inmessages);

			if (ilya_opt('allow_user_walls'))
				ilya_db_user_set_flag($userid, ILYA_USER_FLAGS_NO_WALL_POSTS, !$inwallposts);

			if (ilya_opt('mailing_enabled'))
				ilya_db_user_set_flag($userid, ILYA_USER_FLAGS_NO_MAILINGS, !$inmailings);

			ilya_db_user_set_flag($userid, ILYA_USER_FLAGS_SHOW_AVATAR, ($inavatar == 'uploaded'));
			ilya_db_user_set_flag($userid, ILYA_USER_FLAGS_SHOW_GRAVATAR, ($inavatar == 'gravatar'));

			if (is_array(@$_FILES['file'])) {
				$avatarfileerror = $_FILES['file']['error'];

				// Note if $_FILES['file']['error'] === 1 then upload_max_filesize has been exceeded
				if ($avatarfileerror === 1)
					$errors['avatar'] = ilya_lang('main/file_upload_limit_exceeded');
				elseif ($avatarfileerror === 0 && $_FILES['file']['size'] > 0) {
					require_once ILYA_INCLUDE_DIR . 'app/limits.php';

					switch (ilya_user_permit_error(null, ILYA_LIMIT_UPLOADS)) {
						case 'limit':
							$errors['avatar'] = ilya_lang('main/upload_limit');
							break;

						default:
							$errors['avatar'] = ilya_lang('users/no_permission');
							break;

						case false:
							ilya_limits_increment($userid, ILYA_LIMIT_UPLOADS);
							$toobig = ilya_image_file_too_big($_FILES['file']['tmp_name'], ilya_opt('avatar_store_size'));

							if ($toobig)
								$errors['avatar'] = ilya_lang_sub('main/image_too_big_x_pc', (int)($toobig * 100));
							elseif (!ilya_set_user_avatar($userid, file_get_contents($_FILES['file']['tmp_name']), $useraccount['avatarblobid']))
								$errors['avatar'] = ilya_lang_sub('main/image_not_read', implode(', ', ilya_gd_image_formats()));
							break;
					}
				}  // There shouldn't be any need to catch any other error
			}

			if (count($inprofile)) {
				$filtermodules = ilya_load_modules_with('filter', 'filter_profile');
				foreach ($filtermodules as $filtermodule)
					$filtermodule->filter_profile($inprofile, $errors, $useraccount, $userprofile);
			}

			foreach ($userfields as $userfield) {
				if (!isset($errors[$userfield['fieldid']]))
					ilya_db_user_profile_set($userid, $userfield['title'], $inprofile[$userfield['fieldid']]);
			}

			list($useraccount, $userprofile) = ilya_db_select_with_pending(
				ilya_db_user_account_selectspec($userid, true), ilya_db_user_profile_selectspec($userid, true)
			);

			ilya_report_event('u_save', $userid, $useraccount['handle'], ilya_cookie_get());

			if (empty($errors))
				ilya_redirect('account', array('state' => 'profile-saved'));

			ilya_logged_in_user_flush();
		}
	} elseif (ilya_clicked('dosaveprofile') && $pending_confirmation) {
		// only allow user to update email if they are not confirmed yet
		$inemail = ilya_post_text('email');

		if (!ilya_check_form_security_code('account', ilya_post_text('code')))
			$errors['page'] = ilya_lang_html('misc/form_security_again');

		else {
			$errors = ilya_handle_email_filter($useraccount['handle'], $inemail, $useraccount);

			if (!isset($errors['email']) && $inemail !== $useraccount['email']) {
				ilya_db_user_set($userid, 'email', $inemail);
				ilya_db_user_set_flag($userid, ILYA_USER_FLAGS_EMAIL_CONFIRMED, false);
				$isconfirmed = false;

				if ($doconfirms)
					ilya_send_new_confirm($userid);
			}

			ilya_report_event('u_save', $userid, $useraccount['handle'], ilya_cookie_get());

			if (empty($errors))
				ilya_redirect('account', array('state' => 'profile-saved'));

			ilya_logged_in_user_flush();
		}
	}


	// Process change password if clicked

	if (ilya_clicked('dochangepassword')) {
		$inoldpassword = ilya_post_text('oldpassword');
		$innewpassword1 = ilya_post_text('newpassword1');
		$innewpassword2 = ilya_post_text('newpassword2');

		if (!ilya_check_form_security_code('password', ilya_post_text('code')))
			$errors['page'] = ilya_lang_html('misc/form_security_again');
		else {
			$errors = array();
			$legacyPassError = !hash_equals(strtolower($useraccount['passcheck']), strtolower(ilya_db_calc_passcheck($inoldpassword, $useraccount['passsalt'])));

			if (ILYA_PASSWORD_HASH) {
				$passError = !password_verify($inoldpassword, $useraccount['passhash']);
				if (($haspasswordold && $legacyPassError) || (!$haspasswordold && $haspassword && $passError)) {
					$errors['oldpassword'] = ilya_lang('users/password_wrong');
				}
			} else {
				if ($haspassword && $legacyPassError) {
					$errors['oldpassword'] = ilya_lang('users/password_wrong');
				}
			}

			$useraccount['password'] = $inoldpassword;
			$errors = $errors + ilya_password_validate($innewpassword1, $useraccount); // array union

			if ($innewpassword1 != $innewpassword2)
				$errors['newpassword2'] = ilya_lang('users/password_mismatch');

			if (empty($errors)) {
				ilya_db_user_set_password($userid, $innewpassword1);
				ilya_db_user_set($userid, 'sessioncode', ''); // stop old 'Remember me' style logins from still working
				ilya_set_logged_in_user($userid, $useraccount['handle'], false, $useraccount['sessionsource']); // reinstate this specific session

				ilya_report_event('u_password', $userid, $useraccount['handle'], ilya_cookie_get());

				ilya_redirect('account', array('state' => 'password-changed'));
			}
		}
	}
}

// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('profile/my_account_title');
$ilya_content['error'] = @$errors['page'];

$ilya_content['form_profile'] = array(
	'tags' => 'enctype="multipart/form-data" method="post" action="' . ilya_self_html() . '"',

	'style' => 'wide',

	'fields' => array(
		'duration' => array(
			'type' => 'static',
			'label' => ilya_lang_html('users/member_for'),
			'value' => ilya_time_to_string(ilya_opt('db_time') - $useraccount['created']),
		),

		'type' => array(
			'type' => 'static',
			'label' => ilya_lang_html('users/member_type'),
			'value' => ilya_html(ilya_user_level_string($useraccount['level'])),
			'note' => $isblocked ? ilya_lang_html('users/user_blocked') : null,
		),

		'handle' => array(
			'label' => ilya_lang_html('users/handle_label'),
			'tags' => 'name="handle"',
			'value' => ilya_html(isset($inhandle) ? $inhandle : $useraccount['handle']),
			'error' => ilya_html(@$errors['handle']),
			'type' => ($changehandle && !$isblocked) ? 'text' : 'static',
		),

		'email' => array(
			'label' => ilya_lang_html('users/email_label'),
			'tags' => 'name="email"',
			'value' => ilya_html(isset($inemail) ? $inemail : $useraccount['email']),
			'error' => isset($errors['email']) ? ilya_html($errors['email']) :
				($pending_confirmation ? ilya_insert_login_links(ilya_lang_html('users/email_please_confirm')) : null),
			'type' => $pending_confirmation ? 'text' : ($isblocked ? 'static' : 'text'),
		),

		'messages' => array(
			'label' => ilya_lang_html('users/private_messages'),
			'tags' => 'name="messages"' . ($pending_confirmation ? ' disabled' : ''),
			'type' => 'checkbox',
			'value' => !($useraccount['flags'] & ILYA_USER_FLAGS_NO_MESSAGES),
			'note' => ilya_lang_html('users/private_messages_explanation'),
		),

		'wall' => array(
			'label' => ilya_lang_html('users/wall_posts'),
			'tags' => 'name="wall"' . ($pending_confirmation ? ' disabled' : ''),
			'type' => 'checkbox',
			'value' => !($useraccount['flags'] & ILYA_USER_FLAGS_NO_WALL_POSTS),
			'note' => ilya_lang_html('users/wall_posts_explanation'),
		),

		'mailings' => array(
			'label' => ilya_lang_html('users/mass_mailings'),
			'tags' => 'name="mailings"',
			'type' => 'checkbox',
			'value' => !($useraccount['flags'] & ILYA_USER_FLAGS_NO_MAILINGS),
			'note' => ilya_lang_html('users/mass_mailings_explanation'),
		),

		'avatar' => null, // for positioning
	),

	'buttons' => array(
		'save' => array(
			'tags' => 'onclick="ilya_show_waiting_after(this, false);"',
			'label' => ilya_lang_html('users/save_profile'),
		),
	),

	'hidden' => array(
		'dosaveprofile' => array(
			'tags' => 'name="dosaveprofile"',
			'value' => '1',
		),
		'code' => array(
			'tags' => 'name="code"',
			'value' => ilya_get_form_security_code('account'),
		),
	),
);

if (ilya_get_state() == 'profile-saved')
	$ilya_content['form_profile']['ok'] = ilya_lang_html('users/profile_saved');

if (!ilya_opt('allow_private_messages'))
	unset($ilya_content['form_profile']['fields']['messages']);

if (!ilya_opt('allow_user_walls'))
	unset($ilya_content['form_profile']['fields']['wall']);

if (!ilya_opt('mailing_enabled'))
	unset($ilya_content['form_profile']['fields']['mailings']);

if ($isblocked && !$pending_confirmation) {
	unset($ilya_content['form_profile']['buttons']['save']);
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
}

// Avatar upload stuff

if (ilya_opt('avatar_allow_gravatar') || ilya_opt('avatar_allow_upload')) {
	$avataroptions = array();

	if (ilya_opt('avatar_default_show') && strlen(ilya_opt('avatar_default_blobid'))) {
		$avataroptions[''] = '<span style="margin:2px 0; display:inline-block;">' .
			ilya_get_avatar_blob_html(ilya_opt('avatar_default_blobid'), ilya_opt('avatar_default_width'), ilya_opt('avatar_default_height'), 32) .
			'</span> ' . ilya_lang_html('users/avatar_default');
	} else
		$avataroptions[''] = ilya_lang_html('users/avatar_none');

	$avatarvalue = $avataroptions[''];

	if (ilya_opt('avatar_allow_gravatar') && !$pending_confirmation) {
		$avataroptions['gravatar'] = '<span style="margin:2px 0; display:inline-block;">' .
			ilya_get_gravatar_html($useraccount['email'], 32) . ' ' . strtr(ilya_lang_html('users/avatar_gravatar'), array(
				'^1' => '<a href="http://www.gravatar.com/" target="_blank">',
				'^2' => '</a>',
			)) . '</span>';

		if ($useraccount['flags'] & ILYA_USER_FLAGS_SHOW_GRAVATAR)
			$avatarvalue = $avataroptions['gravatar'];
	}

	if (ilya_has_gd_image() && ilya_opt('avatar_allow_upload') && !$pending_confirmation) {
		$avataroptions['uploaded'] = '<input name="file" type="file">';

		if (isset($useraccount['avatarblobid']))
			$avataroptions['uploaded'] = '<span style="margin:2px 0; display:inline-block;">' .
				ilya_get_avatar_blob_html($useraccount['avatarblobid'], $useraccount['avatarwidth'], $useraccount['avatarheight'], 32) .
				'</span>' . $avataroptions['uploaded'];

		if ($useraccount['flags'] & ILYA_USER_FLAGS_SHOW_AVATAR)
			$avatarvalue = $avataroptions['uploaded'];
	}

	$ilya_content['form_profile']['fields']['avatar'] = array(
		'type' => 'select-radio',
		'label' => ilya_lang_html('users/avatar_label'),
		'tags' => 'name="avatar"',
		'options' => $avataroptions,
		'value' => $avatarvalue,
		'error' => ilya_html(@$errors['avatar']),
	);

} else {
	unset($ilya_content['form_profile']['fields']['avatar']);
}


// Other profile fields

foreach ($userfields as $userfield) {
	$value = @$inprofile[$userfield['fieldid']];
	if (!isset($value))
		$value = @$userprofile[$userfield['title']];

	$label = trim(ilya_user_userfield_label($userfield), ':');
	if (strlen($label))
		$label .= ':';

	$ilya_content['form_profile']['fields'][$userfield['title']] = array(
		'label' => ilya_html($label),
		'tags' => 'name="field_' . $userfield['fieldid'] . '"',
		'value' => ilya_html($value),
		'error' => ilya_html(@$errors[$userfield['fieldid']]),
		'rows' => ($userfield['flags'] & ILYA_FIELD_FLAGS_MULTI_LINE) ? 8 : null,
		'type' => $isblocked ? 'static' : 'text',
	);
}


// Raw information for plugin layers to access

$ilya_content['raw']['account'] = $useraccount;
$ilya_content['raw']['profile'] = $userprofile;
$ilya_content['raw']['points'] = $userpoints;


// Change password form

$ilya_content['form_password'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '"',

	'style' => 'wide',

	'title' => ilya_lang_html('users/change_password'),

	'fields' => array(
		'old' => array(
			'label' => ilya_lang_html('users/old_password'),
			'tags' => 'name="oldpassword"',
			'value' => ilya_html(@$inoldpassword),
			'type' => 'password',
			'error' => ilya_html(@$errors['oldpassword']),
		),

		'new_1' => array(
			'label' => ilya_lang_html('users/new_password_1'),
			'tags' => 'name="newpassword1"',
			'type' => 'password',
			'error' => ilya_html(@$errors['password']),
		),

		'new_2' => array(
			'label' => ilya_lang_html('users/new_password_2'),
			'tags' => 'name="newpassword2"',
			'type' => 'password',
			'error' => ilya_html(@$errors['newpassword2']),
		),
	),

	'buttons' => array(
		'change' => array(
			'label' => ilya_lang_html('users/change_password'),
		),
	),

	'hidden' => array(
		'dochangepassword' => array(
			'tags' => 'name="dochangepassword"',
			'value' => '1',
		),
		'code' => array(
			'tags' => 'name="code"',
			'value' => ilya_get_form_security_code('password'),
		),
	),
);

if (!$haspassword && !$haspasswordold) {
	$ilya_content['form_password']['fields']['old']['type'] = 'static';
	$ilya_content['form_password']['fields']['old']['value'] = ilya_lang_html('users/password_none');
}

if (ilya_get_state() == 'password-changed')
	$ilya_content['form_profile']['ok'] = ilya_lang_html('users/password_changed');


$ilya_content['navigation']['sub'] = ilya_user_sub_navigation($useraccount['handle'], 'account', true);


return $ilya_content;
