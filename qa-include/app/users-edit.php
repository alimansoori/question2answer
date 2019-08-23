<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: User management (application level) for creating/modifying users


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

if (!defined('ILYA__MIN_PASSWORD_LEN')) {
	define('ILYA__MIN_PASSWORD_LEN', 8);
}

if (!defined('ILYA__NEW_PASSWORD_LEN')){
	/**
	 * @deprecated This was the length of the reset password generated by Q2A. No longer used.
	 */
	define('ILYA__NEW_PASSWORD_LEN', 8);
}


/**
 * Return $errors fields for any invalid aspect of user-entered $handle (username) and $email. Works by calling through
 * to all filter modules and also rejects existing values in database unless they belongs to $olduser (if set).
 * @param $handle
 * @param $email
 * @param $olduser
 * @return array
 */
function ilya_handle_email_filter(&$handle, &$email, $olduser = null)
{
	require_once ILYA__INCLUDE_DIR . 'db/users.php';
	require_once ILYA__INCLUDE_DIR . 'util/string.php';

	$errors = array();

	// sanitise 4-byte Unicode
	$handle = ilya_remove_utf8mb4($handle);

	$filtermodules = ilya_load_modules_with('filter', 'filter_handle');

	foreach ($filtermodules as $filtermodule) {
		$error = $filtermodule->filter_handle($handle, $olduser);
		if (isset($error)) {
			$errors['handle'] = $error;
			break;
		}
	}

	if (!isset($errors['handle'])) { // first test through filters, then check for duplicates here
		$handleusers = ilya_db_user_find_by_handle($handle);
		if (count($handleusers) && ((!isset($olduser['userid'])) || (array_search($olduser['userid'], $handleusers) === false)))
			$errors['handle'] = ilya_lang('users/handle_exists');
	}

	$filtermodules = ilya_load_modules_with('filter', 'filter_email');

	$error = null;
	foreach ($filtermodules as $filtermodule) {
		$error = $filtermodule->filter_email($email, $olduser);
		if (isset($error)) {
			$errors['email'] = $error;
			break;
		}
	}

	if (!isset($errors['email'])) {
		$emailusers = ilya_db_user_find_by_email($email);
		if (count($emailusers) && ((!isset($olduser['userid'])) || (array_search($olduser['userid'], $emailusers) === false)))
			$errors['email'] = ilya_lang('users/email_exists');
	}

	return $errors;
}


/**
 * Make $handle valid and unique in the database - if $allowuserid is set, allow it to match that user only
 * @param $handle
 * @return string
 */
function ilya_handle_make_valid($handle)
{
	require_once ILYA__INCLUDE_DIR . 'util/string.php';
	require_once ILYA__INCLUDE_DIR . 'db/maxima.php';
	require_once ILYA__INCLUDE_DIR . 'db/users.php';

	if (!strlen($handle))
		$handle = ilya_lang('users/registered_user');

	$handle = preg_replace('/[\\@\\+\\/]/', ' ', $handle);

	for ($attempt = 0; $attempt <= 99; $attempt++) {
		$suffix = $attempt ? (' ' . $attempt) : '';
		$tryhandle = ilya_substr($handle, 0, ILYA__DB_MAX_HANDLE_LENGTH - strlen($suffix)) . $suffix;

		$filtermodules = ilya_load_modules_with('filter', 'filter_handle');
		foreach ($filtermodules as $filtermodule) {
			// filter first without worrying about errors, since our goal is to get a valid one
			$filtermodule->filter_handle($tryhandle, null);
		}

		$haderror = false;

		foreach ($filtermodules as $filtermodule) {
			$error = $filtermodule->filter_handle($tryhandle, null); // now check for errors after we've filtered
			if (isset($error))
				$haderror = true;
		}

		if (!$haderror) {
			$handleusers = ilya_db_user_find_by_handle($tryhandle);
			if (!count($handleusers))
				return $tryhandle;
		}
	}

	ilya_fatal_error('Could not create a valid and unique handle from: ' . $handle);
}


/**
 * Return an array with a single element (key 'password') if user-entered $password is valid, otherwise an empty array.
 * Works by calling through to all filter modules.
 * @param $password
 * @param $olduser
 * @return array
 */
function ilya_password_validate($password, $olduser = null)
{
	$error = null;
	$filtermodules = ilya_load_modules_with('filter', 'validate_password');

	foreach ($filtermodules as $filtermodule) {
		$error = $filtermodule->validate_password($password, $olduser);
		if (isset($error))
			break;
	}

	if (!isset($error)) {
		$minpasslen = max(ILYA__MIN_PASSWORD_LEN, 1);
		if (ilya_strlen($password) < $minpasslen)
			$error = ilya_lang_sub('users/password_min', $minpasslen);
	}

	if (isset($error))
		return array('password' => $error);

	return array();
}


/**
 * Create a new user (application level) with $email, $password, $handle and $level.
 * Set $confirmed to true if the email address has been confirmed elsewhere.
 * Handles user points, notification and optional email confirmation.
 * @param $email
 * @param $password
 * @param $handle
 * @param int $level
 * @param bool $confirmed
 * @return mixed
 */
function ilya_create_new_user($email, $password, $handle, $level = ILYA__USER_LEVEL_BASIC, $confirmed = false)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/users.php';
	require_once ILYA__INCLUDE_DIR . 'db/points.php';
	require_once ILYA__INCLUDE_DIR . 'app/options.php';
	require_once ILYA__INCLUDE_DIR . 'app/emails.php';
	require_once ILYA__INCLUDE_DIR . 'app/cookies.php';

	$userid = ilya_db_user_create($email, $password, $handle, $level, ilya_remote_ip_address());
	ilya_db_points_update_ifuser($userid, null);
	ilya_db_uapprovecount_update();

	if ($confirmed)
		ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_EMAIL_CONFIRMED, true);

	if (ilya_opt('show_notice_welcome'))
		ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_WELCOME_NOTICE, true);

	$custom = ilya_opt('show_custom_welcome') ? trim(ilya_opt('custom_welcome')) : '';

	if (ilya_opt('confirm_user_emails') && $level < ILYA__USER_LEVEL_EXPERT && !$confirmed) {
		$confirm = strtr(ilya_lang('emails/welcome_confirm'), array(
			'^url' => ilya_get_new_confirm_url($userid, $handle),
		));

		if (ilya_opt('confirm_user_required'))
			ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_MUST_CONFIRM, true);

	} else
		$confirm = '';

	// we no longer use the 'approve_user_required' option to set ILYA__USER_FLAGS_MUST_APPROVE; this can be handled by the Permissions settings

	ilya_send_notification($userid, $email, $handle, ilya_lang('emails/welcome_subject'), ilya_lang('emails/welcome_body'), array(
		'^password' => isset($password) ? ilya_lang('main/hidden') : ilya_lang('users/password_to_set'), // v 1.6.3: no longer email out passwords
		'^url' => ilya_opt('site_url'),
		'^custom' => strlen($custom) ? ($custom . "\n\n") : '',
		'^confirm' => $confirm,
	));

	ilya_report_event('u_register', $userid, $handle, ilya_cookie_get(), array(
		'email' => $email,
		'level' => $level,
	));

	return $userid;
}


/**
 * Delete $userid and all their votes and flags. Their posts will become anonymous.
 * Handles recalculations of votes and flags for posts this user has affected.
 * @param $userid
 * @return mixed
 */
function ilya_delete_user($userid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/votes.php';
	require_once ILYA__INCLUDE_DIR . 'db/users.php';
	require_once ILYA__INCLUDE_DIR . 'db/post-update.php';
	require_once ILYA__INCLUDE_DIR . 'db/points.php';

	$postids = ilya_db_uservoteflag_user_get($userid); // posts this user has flagged or voted on, whose counts need updating

	ilya_db_user_delete($userid);
	ilya_db_uapprovecount_update();
	ilya_db_userpointscount_update();

	foreach ($postids as $postid) { // hoping there aren't many of these - saves a lot of new SQL code...
		ilya_db_post_recount_votes($postid);
		ilya_db_post_recount_flags($postid);
	}

	$postuserids = ilya_db_posts_get_userids($postids);

	foreach ($postuserids as $postuserid) {
		ilya_db_points_update_ifuser($postuserid, array('avoteds', 'qvoteds', 'upvoteds', 'downvoteds'));
	}
}


/**
 * Set a new email confirmation code for the user and send it out
 * @param $userid
 * @return mixed
 */
function ilya_send_new_confirm($userid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/users.php';
	require_once ILYA__INCLUDE_DIR . 'db/selects.php';
	require_once ILYA__INCLUDE_DIR . 'app/emails.php';

	$userinfo = ilya_db_select_with_pending(ilya_db_user_account_selectspec($userid, true));

	$emailcode = ilya_db_user_rand_emailcode();

	if (!ilya_send_notification($userid, $userinfo['email'], $userinfo['handle'], ilya_lang('emails/confirm_subject'), ilya_lang('emails/confirm_body'), array(
			'^url' => ilya_get_new_confirm_url($userid, $userinfo['handle'], $emailcode),
			'^code' => $emailcode,
	))) {
		ilya_fatal_error('Could not send email confirmation');
	}
}


/**
 * Set a new email confirmation code for the user and return the corresponding link. If the email code is also sent then that value
 * is used. Otherwise, a new email code is generated
 * @param $userid
 * @param $handle
 * @param $emailcode
 * @return mixed|string
 */
function ilya_get_new_confirm_url($userid, $handle, $emailcode = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/users.php';

	if (!isset($emailcode)) {
		$emailcode = ilya_db_user_rand_emailcode();
	}
	ilya_db_user_set($userid, 'emailcode', $emailcode);

	return ilya_path_absolute('confirm', array('c' => $emailcode, 'u' => $handle));
}


/**
 * Complete the email confirmation process for the user
 * @param $userid
 * @param $email
 * @param $handle
 * @return mixed
 */
function ilya_complete_confirm($userid, $email, $handle)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/users.php';
	require_once ILYA__INCLUDE_DIR . 'app/cookies.php';

	ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_EMAIL_CONFIRMED, true);
	ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_MUST_CONFIRM, false);
	ilya_db_user_set($userid, 'emailcode', ''); // to prevent re-use of the code

	ilya_report_event('u_confirmed', $userid, $handle, ilya_cookie_get(), array(
		'email' => $email,
	));
}


/**
 * Set the user level of user $userid with $handle to $level (one of the ILYA__USER_LEVEL_* constraints in /ilya-include/app/users.php)
 * Pass the previous user level in $oldlevel. Reports the appropriate event, assumes change performed by the logged in user.
 * @param $userid
 * @param $handle
 * @param $level
 * @param $oldlevel
 */
function ilya_set_user_level($userid, $handle, $level, $oldlevel)
{
	require_once ILYA__INCLUDE_DIR . 'db/users.php';

	ilya_db_user_set($userid, 'level', $level);
	ilya_db_uapprovecount_update();

	if ($level >= ILYA__USER_LEVEL_APPROVED) {
		// no longer necessary as ILYA__USER_FLAGS_MUST_APPROVE is deprecated, but kept for posterity
		ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_MUST_APPROVE, false);
	}

	ilya_report_event('u_level', ilya_get_logged_in_userid(), ilya_get_logged_in_handle(), ilya_cookie_get(), array(
		'userid' => $userid,
		'handle' => $handle,
		'level' => $level,
		'oldlevel' => $oldlevel,
	));
}


/**
 * Set the status of user $userid with $handle to blocked if $blocked is true, otherwise to unblocked. Reports the appropriate
 * event, assumes change performed by the logged in user.
 * @param $userid
 * @param $handle
 * @param $blocked
 */
function ilya_set_user_blocked($userid, $handle, $blocked)
{
	require_once ILYA__INCLUDE_DIR . 'db/users.php';

	ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_USER_BLOCKED, $blocked);
	ilya_db_uapprovecount_update();

	ilya_report_event($blocked ? 'u_block' : 'u_unblock', ilya_get_logged_in_userid(), ilya_get_logged_in_handle(), ilya_cookie_get(), array(
		'userid' => $userid,
		'handle' => $handle,
	));
}


/**
 * Start the 'I forgot my password' process for $userid, sending reset code
 * @param $userid
 * @return mixed
 */
function ilya_start_reset_user($userid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/users.php';
	require_once ILYA__INCLUDE_DIR . 'app/options.php';
	require_once ILYA__INCLUDE_DIR . 'app/emails.php';
	require_once ILYA__INCLUDE_DIR . 'db/selects.php';

	ilya_db_user_set($userid, 'emailcode', ilya_db_user_rand_emailcode());

	$userinfo = ilya_db_select_with_pending(ilya_db_user_account_selectspec($userid, true));

	if (!ilya_send_notification($userid, $userinfo['email'], $userinfo['handle'], ilya_lang('emails/reset_subject'), ilya_lang('emails/reset_body'), array(
		'^code' => $userinfo['emailcode'],
		'^url' => ilya_path_absolute('reset', array('c' => $userinfo['emailcode'], 'e' => $userinfo['email'])),
	))) {
		ilya_fatal_error('Could not send reset password email');
	}
}


/**
 * Successfully finish the 'I forgot my password' process for $userid, sending new password
 *
 * @deprecated This function has been replaced by ilya_finish_reset_user since Q2A 1.8
 * @param $userid
 * @return mixed
 */
function ilya_complete_reset_user($userid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'util/string.php';
	require_once ILYA__INCLUDE_DIR . 'app/options.php';
	require_once ILYA__INCLUDE_DIR . 'app/emails.php';
	require_once ILYA__INCLUDE_DIR . 'app/cookies.php';
	require_once ILYA__INCLUDE_DIR . 'db/selects.php';

	$password = ilya_random_alphanum(max(ILYA__MIN_PASSWORD_LEN, ILYA__NEW_PASSWORD_LEN));

	$userinfo = ilya_db_select_with_pending(ilya_db_user_account_selectspec($userid, true));

	if (!ilya_send_notification($userid, $userinfo['email'], $userinfo['handle'], ilya_lang('emails/new_password_subject'), ilya_lang('emails/new_password_body'), array(
		'^password' => $password,
		'^url' => ilya_opt('site_url'),
	))) {
		ilya_fatal_error('Could not send new password - password not reset');
	}

	ilya_db_user_set_password($userid, $password); // do this last, to be safe
	ilya_db_user_set($userid, 'emailcode', ''); // so can't be reused

	ilya_report_event('u_reset', $userid, $userinfo['handle'], ilya_cookie_get(), array(
		'email' => $userinfo['email'],
	));
}


/**
 * Successfully finish the 'I forgot my password' process for $userid, cleaning the emailcode field and logging in the user
 * @param mixed $userId The userid identifiying the user who will have the password reset
 * @param string $newPassword The new password for the user
 * @return void
 */
function ilya_finish_reset_user($userId, $newPassword)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	// For ilya_db_user_set_password(), ilya_db_user_set()
	require_once ILYA__INCLUDE_DIR . 'db/users.php';

	// For ilya_set_logged_in_user()
	require_once ILYA__INCLUDE_DIR . 'app/options.php';

	// For ilya_cookie_get()
	require_once ILYA__INCLUDE_DIR . 'app/cookies.php';

	// For ilya_db_select_with_pending(), ilya_db_user_account_selectspec()
	require_once ILYA__INCLUDE_DIR . 'db/selects.php';

	// For ilya_set_logged_in_user()
	require_once ILYA__INCLUDE_DIR . 'app/users.php';

	ilya_db_user_set_password($userId, $newPassword);

	ilya_db_user_set($userId, 'emailcode', ''); // to prevent re-use of the code

	$userInfo = ilya_db_select_with_pending(ilya_db_user_account_selectspec($userId, true));

	ilya_set_logged_in_user($userId, $userInfo['handle'], false, $userInfo['sessionsource']); // reinstate this specific session

	ilya_report_event('u_reset', $userId, $userInfo['handle'], ilya_cookie_get(), array(
		'email' => $userInfo['email'],
	));
}

/**
 * Flush any information about the currently logged in user, so it is retrieved from database again
 */
function ilya_logged_in_user_flush()
{
	global $ilya_cached_logged_in_user;

	$ilya_cached_logged_in_user = null;
}


/**
 * Set the avatar of $userid to the image in $imagedata, and remove $oldblobid from the database if not null
 * @param $userid
 * @param $imagedata
 * @param $oldblobid
 * @return bool
 */
function ilya_set_user_avatar($userid, $imagedata, $oldblobid = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'util/image.php';

	$imagedata = ilya_image_constrain_data($imagedata, $width, $height, ilya_opt('avatar_store_size'));

	if (isset($imagedata)) {
		require_once ILYA__INCLUDE_DIR . 'app/blobs.php';

		$newblobid = ilya_create_blob($imagedata, 'jpeg', null, $userid, null, ilya_remote_ip_address());

		if (isset($newblobid)) {
			ilya_db_user_set($userid, array(
				'avatarblobid' => $newblobid,
				'avatarwidth' => $width,
				'avatarheight' => $height,
			));

			ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_SHOW_AVATAR, true);
			ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_SHOW_GRAVATAR, false);

			if (isset($oldblobid))
				ilya_delete_blob($oldblobid);

			return true;
		}
	}

	return false;
}
