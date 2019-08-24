<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: User management (application level) for basic user operations


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

define('ILYA_USER_LEVEL_BASIC', 0);
define('ILYA_USER_LEVEL_APPROVED', 10);
define('ILYA_USER_LEVEL_EXPERT', 20);
define('ILYA_USER_LEVEL_EDITOR', 50);
define('ILYA_USER_LEVEL_MODERATOR', 80);
define('ILYA_USER_LEVEL_ADMIN', 100);
define('ILYA_USER_LEVEL_SUPER', 120);

define('ILYA_USER_FLAGS_EMAIL_CONFIRMED', 1);
define('ILYA_USER_FLAGS_USER_BLOCKED', 2);
define('ILYA_USER_FLAGS_SHOW_AVATAR', 4);
define('ILYA_USER_FLAGS_SHOW_GRAVATAR', 8);
define('ILYA_USER_FLAGS_NO_MESSAGES', 16);
define('ILYA_USER_FLAGS_NO_MAILINGS', 32);
define('ILYA_USER_FLAGS_WELCOME_NOTICE', 64);
define('ILYA_USER_FLAGS_MUST_CONFIRM', 128);
define('ILYA_USER_FLAGS_NO_WALL_POSTS', 256);
define('ILYA_USER_FLAGS_MUST_APPROVE', 512); // @deprecated

define('ILYA_FIELD_FLAGS_MULTI_LINE', 1);
define('ILYA_FIELD_FLAGS_LINK_URL', 2);
define('ILYA_FIELD_FLAGS_ON_REGISTER', 4);

if (!defined('ILYA_FORM_EXPIRY_SECS')) {
	// how many seconds a form is valid for submission
	define('ILYA_FORM_EXPIRY_SECS', 86400);
}
if (!defined('ILYA_FORM_KEY_LENGTH')) {
	define('ILYA_FORM_KEY_LENGTH', 32);
}


if (ILYA_FINAL_EXTERNAL_USERS) {
	// If we're using single sign-on integration (WordPress or otherwise), load PHP file for that

	if (defined('ILYA_FINAL_WORDPRESS_INTEGRATE_PATH')) {
		require_once ILYA_INCLUDE_DIR . 'util/external-users-wp.php';
	} elseif (defined('ILYA_FINAL_JOOMLA_INTEGRATE_PATH')) {
		require_once ILYA_INCLUDE_DIR . 'util/external-users-joomla.php';
	} else {
		require_once ILYA_EXTERNAL_DIR . 'ilya-external-users.php';
	}

	// Access functions for user information

	/**
	 * Return array of information about the currently logged in user, cache to ensure only one call to external code
	 */
	function ilya_get_logged_in_user_cache()
	{
		global $ilya_cached_logged_in_user;

		if (!isset($ilya_cached_logged_in_user)) {
			$user = ilya_get_logged_in_user();

			if (isset($user)) {
				$user['flags'] = isset($user['blocked']) ? ILYA_USER_FLAGS_USER_BLOCKED : 0;
				$ilya_cached_logged_in_user = $user;
			} else
				$ilya_cached_logged_in_user = false;
		}

		return @$ilya_cached_logged_in_user;
	}


	/**
	 * Return $field of the currently logged in user, or null if not available
	 * @param $field
	 * @return null
	 */
	function ilya_get_logged_in_user_field($field)
	{
		$user = ilya_get_logged_in_user_cache();

		return isset($user[$field]) ? $user[$field] : null;
	}


	/**
	 * Return the userid of the currently logged in user, or null if none
	 */
	function ilya_get_logged_in_userid()
	{
		return ilya_get_logged_in_user_field('userid');
	}


	/**
	 * Return the number of points of the currently logged in user, or null if none is logged in
	 */
	function ilya_get_logged_in_points()
	{
		global $ilya_cached_logged_in_points;

		if (!isset($ilya_cached_logged_in_points)) {
			require_once ILYA_INCLUDE_DIR . 'db/selects.php';

			$ilya_cached_logged_in_points = ilya_db_select_with_pending(ilya_db_user_points_selectspec(ilya_get_logged_in_userid(), true));
		}

		return $ilya_cached_logged_in_points['points'];
	}


	/**
	 * Return HTML to display for the avatar of $userid, constrained to $size pixels, with optional $padding to that size
	 * @param $userid
	 * @param $size
	 * @param bool $padding
	 * @return mixed|null|string
	 */
	function ilya_get_external_avatar_html($userid, $size, $padding = false)
	{
		if (function_exists('ilya_avatar_html_from_userid'))
			return ilya_avatar_html_from_userid($userid, $size, $padding);
		else
			return null;
	}


} else {

	/**
	 * Open a PHP session if one isn't opened already
	 */
	function ilya_start_session()
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		@ini_set('session.gc_maxlifetime', 86400); // worth a try, but won't help in shared hosting environment
		@ini_set('session.use_trans_sid', false); // sessions need cookies to work, since we redirect after login
		@ini_set('session.cookie_domain', ILYA_COOKIE_DOMAIN);

		if (!isset($_SESSION))
			session_start();
	}


	/**
	 * Returns a suffix to be used for names of session variables to prevent them being shared between multiple ILYA sites on the same server
	 */
	function ilya_session_var_suffix()
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		global $ilya_session_suffix;

		if (!$ilya_session_suffix) {
			$prefix = defined('ILYA_MYSQL_USERS_PREFIX') ? ILYA_MYSQL_USERS_PREFIX : ILYA_MYSQL_TABLE_PREFIX;
			$ilya_session_suffix = md5(ILYA_FINAL_MYSQL_HOSTNAME . '/' . ILYA_FINAL_MYSQL_USERNAME . '/' . ILYA_FINAL_MYSQL_PASSWORD . '/' . ILYA_FINAL_MYSQL_DATABASE . '/' . $prefix);
		}

		return $ilya_session_suffix;
	}


	/**
	 * Returns a verification code used to ensure that a user session can't be generated by another PHP script running on the same server
	 * @param $userid
	 * @return mixed|string
	 */
	function ilya_session_verify_code($userid)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		return sha1($userid . '/' . ILYA_MYSQL_TABLE_PREFIX . '/' . ILYA_FINAL_MYSQL_DATABASE . '/' . ILYA_FINAL_MYSQL_PASSWORD . '/' . ILYA_FINAL_MYSQL_USERNAME . '/' . ILYA_FINAL_MYSQL_HOSTNAME);
	}


	/**
	 * Set cookie in browser for username $handle with $sessioncode (in database).
	 * Pass true if user checked 'Remember me' (either now or previously, as learned from cookie).
	 * @param $handle
	 * @param $sessioncode
	 * @param $remember
	 * @return mixed
	 */
	function ilya_set_session_cookie($handle, $sessioncode, $remember)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		// if $remember is true, store in browser for a month, otherwise store only until browser is closed
		setcookie('ilya_session', $handle . '/' . $sessioncode . '/' . ($remember ? 1 : 0), $remember ? (time() + 2592000) : 0, '/', ILYA_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true);
	}


	/**
	 * Remove session cookie from browser
	 */
	function ilya_clear_session_cookie()
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		setcookie('ilya_session', false, 0, '/', ILYA_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true);
	}


	/**
	 * Set the session variables to indicate that $userid is logged in from $source
	 * @param $userid
	 * @param $source
	 * @return mixed
	 */
	function ilya_set_session_user($userid, $source)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		$suffix = ilya_session_var_suffix();

		$_SESSION['ilya_session_userid_' . $suffix] = $userid;
		$_SESSION['ilya_session_source_' . $suffix] = $source;
		// prevents one account on a shared server being able to create a log in a user to ILYA on another account on same server
		$_SESSION['ilya_session_verify_' . $suffix] = ilya_session_verify_code($userid);
	}


	/**
	 * Clear the session variables indicating that a user is logged in
	 */
	function ilya_clear_session_user()
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		$suffix = ilya_session_var_suffix();

		unset($_SESSION['ilya_session_userid_' . $suffix]);
		unset($_SESSION['ilya_session_source_' . $suffix]);
		unset($_SESSION['ilya_session_verify_' . $suffix]);
	}


	/**
	 * Call for successful log in by $userid and $handle or successful log out with $userid=null.
	 * $remember states if 'Remember me' was checked in the login form.
	 * @param $userid
	 * @param string $handle
	 * @param bool $remember
	 * @param $source
	 * @return mixed
	 */
	function ilya_set_logged_in_user($userid, $handle = '', $remember = false, $source = null)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		require_once ILYA_INCLUDE_DIR . 'app/cookies.php';

		ilya_start_session();

		if (isset($userid)) {
			ilya_set_session_user($userid, $source);

			// PHP sessions time out too quickly on the server side, so we also set a cookie as backup.
			// Logging in from a second browser will make the previous browser's 'Remember me' no longer
			// work - I'm not sure if this is the right behavior - could see it either way.

			require_once ILYA_INCLUDE_DIR . 'db/selects.php';

			$userinfo = ilya_db_single_select(ilya_db_user_account_selectspec($userid, true));

			// if we have logged in before, and are logging in the same way as before, we don't need to change the sessioncode/source
			// this means it will be possible to automatically log in (via cookies) to the same account from more than one browser

			if (empty($userinfo['sessioncode']) || ($source !== $userinfo['sessionsource'])) {
				$sessioncode = ilya_db_user_rand_sessioncode();
				ilya_db_user_set($userid, array(
					'sessioncode' => $sessioncode,
					'sessionsource' => $source,
				));
			} else
				$sessioncode = $userinfo['sessioncode'];

			ilya_db_user_logged_in($userid, ilya_remote_ip_address());
			ilya_set_session_cookie($handle, $sessioncode, $remember);

			ilya_report_event('u_login', $userid, $userinfo['handle'], ilya_cookie_get());

		} else {
			$olduserid = ilya_get_logged_in_userid();
			$oldhandle = ilya_get_logged_in_handle();

			ilya_clear_session_cookie();
			ilya_clear_session_user();

			ilya_report_event('u_logout', $olduserid, $oldhandle, ilya_cookie_get());
		}
	}


	/**
	 * Call to log in a user based on an external identity provider $source with external $identifier
	 * A new user is created based on $fields if it's a new combination of $source and $identifier
	 * @param $source
	 * @param $identifier
	 * @param $fields
	 * @return mixed
	 */
	function ilya_log_in_external_user($source, $identifier, $fields)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		require_once ILYA_INCLUDE_DIR . 'db/users.php';

		$users = ilya_db_user_login_find($source, $identifier);
		$countusers = count($users);

		if ($countusers > 1)
			ilya_fatal_error('External login mapped to more than one user'); // should never happen

		if ($countusers) // user exists so log them in
			ilya_set_logged_in_user($users[0]['userid'], $users[0]['handle'], false, $source);

		else { // create and log in user
			require_once ILYA_INCLUDE_DIR . 'app/users-edit.php';

			ilya_db_user_login_sync(true);

			$users = ilya_db_user_login_find($source, $identifier); // check again after table is locked

			if (count($users) == 1) {
				ilya_db_user_login_sync(false);
				ilya_set_logged_in_user($users[0]['userid'], $users[0]['handle'], false, $source);

			} else {
				$handle = ilya_handle_make_valid(@$fields['handle']);

				if (strlen(@$fields['email'])) { // remove email address if it will cause a duplicate
					$emailusers = ilya_db_user_find_by_email($fields['email']);
					if (count($emailusers)) {
						ilya_redirect('login', array('e' => $fields['email'], 'ee' => '1'));
						unset($fields['email']);
						unset($fields['confirmed']);
					}
				}

				$userid = ilya_create_new_user((string)@$fields['email'], null /* no password */, $handle,
					isset($fields['level']) ? $fields['level'] : ILYA_USER_LEVEL_BASIC, @$fields['confirmed']);

				ilya_db_user_login_add($userid, $source, $identifier);
				ilya_db_user_login_sync(false);

				$profilefields = array('name', 'location', 'website', 'about');

				foreach ($profilefields as $fieldname) {
					if (strlen(@$fields[$fieldname]))
						ilya_db_user_profile_set($userid, $fieldname, $fields[$fieldname]);
				}

				if (strlen(@$fields['avatar']))
					ilya_set_user_avatar($userid, $fields['avatar']);

				ilya_set_logged_in_user($userid, $handle, false, $source);
			}
		}
	}


	/**
	 * Return the userid of the currently logged in user, or null if none logged in
	 */
	function ilya_get_logged_in_userid()
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		global $ilya_logged_in_userid_checked;

		$suffix = ilya_session_var_suffix();

		if (!$ilya_logged_in_userid_checked) { // only check once
			ilya_start_session(); // this will load logged in userid from the native PHP session, but that's not enough

			$sessionuserid = @$_SESSION['ilya_session_userid_' . $suffix];

			if (isset($sessionuserid)) // check verify code matches
				if (!hash_equals(ilya_session_verify_code($sessionuserid), @$_SESSION['ilya_session_verify_' . $suffix]))
					ilya_clear_session_user();

			if (!empty($_COOKIE['ilya_session'])) {
				@list($handle, $sessioncode, $remember) = explode('/', $_COOKIE['ilya_session']);

				if ($remember)
					ilya_set_session_cookie($handle, $sessioncode, $remember); // extend 'remember me' cookies each time

				$sessioncode = trim($sessioncode); // trim to prevent passing in blank values to match uninitiated DB rows

				// Try to recover session from the database if PHP session has timed out
				if (!isset($_SESSION['ilya_session_userid_' . $suffix]) && !empty($handle) && !empty($sessioncode)) {
					require_once ILYA_INCLUDE_DIR . 'db/selects.php';

					$userinfo = ilya_db_single_select(ilya_db_user_account_selectspec($handle, false)); // don't get any pending

					if (strtolower(trim($userinfo['sessioncode'])) == strtolower($sessioncode))
						ilya_set_session_user($userinfo['userid'], $userinfo['sessionsource']);
					else
						ilya_clear_session_cookie(); // if cookie not valid, remove it to save future checks
				}
			}

			$ilya_logged_in_userid_checked = true;
		}

		return @$_SESSION['ilya_session_userid_' . $suffix];
	}


	/**
	 * Get the source of the currently logged in user, from call to ilya_log_in_external_user() or null if logged in normally
	 */
	function ilya_get_logged_in_source()
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		$userid = ilya_get_logged_in_userid();
		$suffix = ilya_session_var_suffix();

		if (isset($userid))
			return @$_SESSION['ilya_session_source_' . $suffix];
	}


	/**
	 * Return array of information about the currently logged in user, cache to ensure only one call to external code
	 */
	function ilya_get_logged_in_user_cache()
	{
		global $ilya_cached_logged_in_user;

		if (!isset($ilya_cached_logged_in_user)) {
			$userid = ilya_get_logged_in_userid();

			if (isset($userid)) {
				require_once ILYA_INCLUDE_DIR . 'db/selects.php';
				$ilya_cached_logged_in_user = ilya_db_get_pending_result('loggedinuser', ilya_db_user_account_selectspec($userid, true));

				// If the site is configured to share the ^users table then there might not be a record in the
				// ^userpoints table so this creates it
				if ($ilya_cached_logged_in_user['points'] === null) {
					require_once ILYA_INCLUDE_DIR . 'db/points.php';
					require_once ILYA_INCLUDE_DIR . 'db/users.php';

					ilya_db_points_update_ifuser($userid, null);
					ilya_db_uapprovecount_update();
					$ilya_cached_logged_in_user = ilya_db_single_select(ilya_db_user_account_selectspec($userid, true));
				}

				if (!isset($ilya_cached_logged_in_user)) {
					// the user can no longer be found (should only apply to deleted users)
					ilya_clear_session_user();
					ilya_redirect(''); // implicit exit;
				}
			}
		}

		return $ilya_cached_logged_in_user;
	}


	/**
	 * Return $field of the currently logged in user
	 * @param $field
	 * @return mixed|null
	 */
	function ilya_get_logged_in_user_field($field)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		$usercache = ilya_get_logged_in_user_cache();

		return isset($usercache[$field]) ? $usercache[$field] : null;
	}


	/**
	 * Return the number of points of the currently logged in user, or null if none is logged in
	 */
	function ilya_get_logged_in_points()
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		return ilya_get_logged_in_user_field('points');
	}


	/**
	 * Return column type to use for users (if not using single sign-on integration)
	 */
	function ilya_get_mysql_user_column_type()
	{
		return 'INT UNSIGNED';
	}


	/**
	 * Return the URL to the $blobId with a stored size of $width and $height.
	 * Constrain the image to $size (width AND height)
	 *
	 * @since 1.8.0
	 * @param string $blobId The blob ID from the image
	 * @param int|null $size The resulting image's size. If omitted the original image size will be used. If the
	 * size is present it must be greater than 0
	 * @param bool $absolute Whether the link returned should be absolute or relative
	 * @return string|null The URL to the avatar or null if the $blobId was empty or the $size not valid
	 */
	function ilya_get_avatar_blob_url($blobId, $size = null, $absolute = false)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		require_once ILYA_INCLUDE_DIR . 'util/image.php';

		if (strlen($blobId) == 0 || (isset($size) && (int)$size <= 0)) {
			return null;
		}

		$params = array('ilya_blobid' => $blobId);
		if (isset($size)) {
			$params['ilya_size'] = $size;
		}

		$rootUrl = $absolute ? ilya_opt('site_url') : null;

		return ilya_path('image', $params, $rootUrl, ILYA_URL_FORMAT_PARAMS);
	}


	/**
	 * Get HTML to display a username, linked to their user page.
	 *
	 * @param string $handle  The username.
	 * @param bool $microdata  Whether to include microdata.
	 * @param bool $favorited  Show the user as favorited.
	 * @return string  The user HTML.
	 */
	function ilya_get_one_user_html($handle, $microdata = false, $favorited = false)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		if (strlen($handle) === 0) {
			return ilya_lang('main/anonymous');
		}

		$url = ilya_path_html('user/' . $handle);
		$favclass = $favorited ? ' ilya-user-favorited' : '';
		$mfAttr = $microdata ? ' itemprop="url"' : '';

		$userHandle = $microdata ? '<span itemprop="name">' . ilya_html($handle) . '</span>' : ilya_html($handle);
		$userHtml = '<a href="' . $url . '" class="ilya-user-link' . $favclass . '"' . $mfAttr . '>' . $userHandle . '</a>';

		if ($microdata) {
			$userHtml = '<span itemprop="author" itemscope itemtype="https://schema.org/Person">' . $userHtml . '</span>';
		}

		return $userHtml;
	}


	/**
	 * Return where the avatar will be fetched from for the given user flags. The possible return values are
	 * 'gravatar' for an avatar that will be fetched from Gravatar, 'local-user' for an avatar fetched locally from
	 * the user's profile, 'local-default' for an avatar fetched locally from the default avatar blob ID, and NULL
	 * if the avatar could not be fetched from any of these sources
	 *
	 * @since 1.8.0
	 * @param int $flags The user's flags
	 * @param string|null $email The user's email
	 * @param string|null $blobId The blob ID for a locally stored avatar.
	 * @return string|null The source of the avatar: 'gravatar', 'local-user', 'local-default' and null
	 */
	function ilya_get_user_avatar_source($flags, $email, $blobId)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		if (ilya_opt('avatar_allow_gravatar') && (($flags & ILYA_USER_FLAGS_SHOW_GRAVATAR) > 0) && isset($email)) {
			return 'gravatar';
		} elseif (ilya_opt('avatar_allow_upload') && (($flags & ILYA_USER_FLAGS_SHOW_AVATAR) > 0) && isset($blobId)) {
			return 'local-user';
		} elseif ((ilya_opt('avatar_allow_gravatar') || ilya_opt('avatar_allow_upload')) && ilya_opt('avatar_default_show') && strlen(ilya_opt('avatar_default_blobid') > 0)) {
			return 'local-default';
		} else {
			return null;
		}
	}


	/**
	 * Return the avatar URL, either Gravatar or from a blob ID, constrained to $size pixels.
	 *
	 * @param int $flags The user's flags
	 * @param string $email The user's email. Only needed to return the Gravatar link
	 * @param string $blobId The blob ID. Only needed to return the locally stored avatar
	 * @param int $size The size to constrain the final image
	 * @param bool $absolute Whether the link returned should be absolute or relative
	 * @return null|string The URL to the user's avatar or null if none could be found (not even as a default site avatar)
	 */
	function ilya_get_user_avatar_url($flags, $email, $blobId, $size = null, $absolute = false)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		$avatarSource = ilya_get_user_avatar_source($flags, $email, $blobId);

		switch ($avatarSource) {
			case 'gravatar':
				return ilya_get_gravatar_url($email, $size);
			case 'local-user':
				return ilya_get_avatar_blob_url($blobId, $size, $absolute);
			case 'local-default':
				return ilya_get_avatar_blob_url(ilya_opt('avatar_default_blobid'), $size, $absolute);
			default: // NULL
				return null;
		}
	}


	/**
	 * Return HTML to display for the user's avatar, constrained to $size pixels, with optional $padding to that size
	 *
	 * @param int $flags The user's flags
	 * @param string $email The user's email. Only needed to return the Gravatar HTML
	 * @param string $blobId The blob ID. Only needed to return the locally stored avatar HTML
	 * @param string $handle The handle of the user that the avatar will link to
	 * @param int $width The width to constrain the image
	 * @param int $height The height to constrain the image
	 * @param int $size The size to constrain the final image
	 * @param bool $padding HTML padding to add to the image
	 * @return string|null The HTML to the user's avatar or null if no valid source for the avatar could be found
	 */
	function ilya_get_user_avatar_html($flags, $email, $handle, $blobId, $width, $height, $size, $padding = false)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		require_once ILYA_INCLUDE_DIR . 'app/format.php';

		$avatarSource = ilya_get_user_avatar_source($flags, $email, $blobId);

		switch ($avatarSource) {
			case 'gravatar':
				$html = ilya_get_gravatar_html($email, $size);
				break;
			case 'local-user':
				$html = ilya_get_avatar_blob_html($blobId, $width, $height, $size, $padding);
				break;
			case 'local-default':
				$html = ilya_get_avatar_blob_html(ilya_opt('avatar_default_blobid'), ilya_opt('avatar_default_width'), ilya_opt('avatar_default_height'), $size, $padding);
				if (strlen($handle) == 0) {
					return $html;
				}
				break;
			default: // NULL
				return null;
		}

		return sprintf('<a href="%s" class="ilya-avatar-link">%s</a>', ilya_path_html('user/' . $handle), $html);
	}


	/**
	 * Return email address for user $userid (if not using single sign-on integration)
	 * @param $userid
	 * @return string
	 */
	function ilya_get_user_email($userid)
	{
		$userinfo = ilya_db_select_with_pending(ilya_db_user_account_selectspec($userid, true));

		return $userinfo['email'];
	}


	/**
	 * Called after a database write $action performed by a user $userid
	 * @param $userid
	 * @param $action
	 * @return mixed
	 */
	function ilya_user_report_action($userid, $action)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		require_once ILYA_INCLUDE_DIR . 'db/users.php';

		ilya_db_user_written($userid, ilya_remote_ip_address());
	}


	/**
	 * Return textual representation of the user $level
	 * @param $level
	 * @return mixed|string
	 */
	function ilya_user_level_string($level)
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		if ($level >= ILYA_USER_LEVEL_SUPER)
			$string = 'users/level_super';
		elseif ($level >= ILYA_USER_LEVEL_ADMIN)
			$string = 'users/level_admin';
		elseif ($level >= ILYA_USER_LEVEL_MODERATOR)
			$string = 'users/level_moderator';
		elseif ($level >= ILYA_USER_LEVEL_EDITOR)
			$string = 'users/level_editor';
		elseif ($level >= ILYA_USER_LEVEL_EXPERT)
			$string = 'users/level_expert';
		elseif ($level >= ILYA_USER_LEVEL_APPROVED)
			$string = 'users/approved_user';
		else
			$string = 'users/registered_user';

		return ilya_lang($string);
	}


	/**
	 * Return an array of links to login, register, email confirm and logout pages (if not using single sign-on integration)
	 * @param $rooturl
	 * @param $tourl
	 * @return array
	 */
	function ilya_get_login_links($rooturl, $tourl)
	{
		return array(
			'login' => ilya_path('login', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
			'register' => ilya_path('register', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
			'confirm' => ilya_path('confirm', null, $rooturl),
			'logout' => ilya_path('logout', null, $rooturl),
		);
	}

} // end of: if (ILYA_FINAL_EXTERNAL_USERS) { ... } else { ... }


/**
 * Return whether someone is logged in at the moment
 */
function ilya_is_logged_in()
{
	$userid = ilya_get_logged_in_userid();
	return isset($userid);
}


/**
 * Return displayable handle/username of currently logged in user, or null if none
 */
function ilya_get_logged_in_handle()
{
	return ilya_get_logged_in_user_field(ILYA_FINAL_EXTERNAL_USERS ? 'publicusername' : 'handle');
}


/**
 * Return email of currently logged in user, or null if none
 */
function ilya_get_logged_in_email()
{
	return ilya_get_logged_in_user_field('email');
}


/**
 * Return level of currently logged in user, or null if none
 */
function ilya_get_logged_in_level()
{
	return ilya_get_logged_in_user_field('level');
}


/**
 * Return flags (see ILYA_USER_FLAGS_*) of currently logged in user, or null if none
 */
function ilya_get_logged_in_flags()
{
	if (ILYA_FINAL_EXTERNAL_USERS)
		return ilya_get_logged_in_user_field('blocked') ? ILYA_USER_FLAGS_USER_BLOCKED : 0;
	else
		return ilya_get_logged_in_user_field('flags');
}


/**
 * Return an array of all the specific (e.g. per category) level privileges for the logged in user, retrieving from the database if necessary
 */
function ilya_get_logged_in_levels()
{
	require_once ILYA_INCLUDE_DIR . 'db/selects.php';

	return ilya_db_get_pending_result('userlevels', ilya_db_user_levels_selectspec(ilya_get_logged_in_userid(), true));
}


/**
 * Return an array mapping each userid in $userids to that user's handle (public username), or to null if not found
 * @param $userids
 * @return array
 */
function ilya_userids_to_handles($userids)
{
	if (ILYA_FINAL_EXTERNAL_USERS)
		$rawuseridhandles = ilya_get_public_from_userids($userids);

	else {
		require_once ILYA_INCLUDE_DIR . 'db/users.php';
		$rawuseridhandles = ilya_db_user_get_userid_handles($userids);
	}

	$gotuseridhandles = array();
	foreach ($userids as $userid)
		$gotuseridhandles[$userid] = @$rawuseridhandles[$userid];

	return $gotuseridhandles;
}


/**
 * Return an string mapping the received userid to that user's handle (public username), or to null if not found
 * @param $userid
 * @return mixed|null
 */
function ilya_userid_to_handle($userid)
{
	$handles = ilya_userids_to_handles(array($userid));
	return empty($handles) ? null : $handles[$userid];
}


/**
 * Return an array mapping each handle in $handles the user's userid, or null if not found. If $exactonly is true then
 * $handles must have the correct case and accents. Otherwise, handles are case- and accent-insensitive, and the keys
 * of the returned array will match the $handles provided, not necessary those in the DB.
 * @param $handles
 * @param bool $exactonly
 * @return array
 */
function ilya_handles_to_userids($handles, $exactonly = false)
{
	require_once ILYA_INCLUDE_DIR . 'util/string.php';

	if (ILYA_FINAL_EXTERNAL_USERS)
		$rawhandleuserids = ilya_get_userids_from_public($handles);

	else {
		require_once ILYA_INCLUDE_DIR . 'db/users.php';
		$rawhandleuserids = ilya_db_user_get_handle_userids($handles);
	}

	$gothandleuserids = array();

	if ($exactonly) { // only take the exact matches
		foreach ($handles as $handle)
			$gothandleuserids[$handle] = @$rawhandleuserids[$handle];

	} else { // normalize to lowercase without accents, and then find matches
		$normhandleuserids = array();
		foreach ($rawhandleuserids as $handle => $userid)
			$normhandleuserids[ilya_string_remove_accents(ilya_strtolower($handle))] = $userid;

		foreach ($handles as $handle)
			$gothandleuserids[$handle] = @$normhandleuserids[ilya_string_remove_accents(ilya_strtolower($handle))];
	}

	return $gothandleuserids;
}


/**
 * Return the userid corresponding to $handle (not case- or accent-sensitive)
 * @param $handle
 * @return mixed|null
 */
function ilya_handle_to_userid($handle)
{
	if (ILYA_FINAL_EXTERNAL_USERS)
		$handleuserids = ilya_get_userids_from_public(array($handle));

	else {
		require_once ILYA_INCLUDE_DIR . 'db/users.php';
		$handleuserids = ilya_db_user_get_handle_userids(array($handle));
	}

	if (count($handleuserids) == 1)
		return reset($handleuserids); // don't use $handleuserids[$handle] since capitalization might be different

	return null;
}


/**
 * Return the level of the logged in user for a post with $categoryids (expressing the full hierarchy to the final category)
 * @param $categoryids
 * @return mixed|null
 */
function ilya_user_level_for_categories($categoryids)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA_INCLUDE_DIR . 'app/updates.php';

	$level = ilya_get_logged_in_level();

	if (count($categoryids)) {
		$userlevels = ilya_get_logged_in_levels();

		$categorylevels = array(); // create a map
		foreach ($userlevels as $userlevel) {
			if ($userlevel['entitytype'] == ILYA_ENTITY_CATEGORY)
				$categorylevels[$userlevel['entityid']] = $userlevel['level'];
		}

		foreach ($categoryids as $categoryid) {
			$level = max($level, @$categorylevels[$categoryid]);
		}
	}

	return $level;
}


/**
 * Return the level of the logged in user for $post, as retrieved from the database
 * @param $post
 * @return mixed|null
 */
function ilya_user_level_for_post($post)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	if (strlen(@$post['categoryids']))
		return ilya_user_level_for_categories(explode(',', $post['categoryids']));

	return null;
}


/**
 * Return the maximum possible level of the logged in user in any context (i.e. for any category)
 */
function ilya_user_level_maximum()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$level = ilya_get_logged_in_level();

	$userlevels = ilya_get_logged_in_levels();
	foreach ($userlevels as $userlevel) {
		$level = max($level, $userlevel['level']);
	}

	return $level;
}


/**
 * Check whether the logged in user has permission to perform $permitoption on post $post (from the database)
 * Other parameters and the return value are as for ilya_user_permit_error(...)
 * @param $permitoption
 * @param $post
 * @param $limitaction
 * @param bool $checkblocks
 * @return bool|string
 */
function ilya_user_post_permit_error($permitoption, $post, $limitaction = null, $checkblocks = true)
{
	return ilya_user_permit_error($permitoption, $limitaction, ilya_user_level_for_post($post), $checkblocks);
}


/**
 * Check whether the logged in user would have permittion to perform $permitoption in any context (i.e. for any category)
 * Other parameters and the return value are as for ilya_user_permit_error(...)
 * @param $permitoption
 * @param $limitaction
 * @param bool $checkblocks
 * @return bool|string
 */
function ilya_user_maximum_permit_error($permitoption, $limitaction = null, $checkblocks = true)
{
	return ilya_user_permit_error($permitoption, $limitaction, ilya_user_level_maximum(), $checkblocks);
}


/**
 * Check whether the logged in user has permission to perform an action.
 *
 * @param string $permitoption The permission to check (if null, this simply checks whether the user is blocked).
 * @param string $limitaction Constant from /ilya-include/app/limits.php to check against user or IP rate limits.
 * @param int $userlevel A ILYA_USER_LEVEL_* constant to consider the user at a different level to usual (e.g. if
 *   they are performing this action in a category for which they have elevated privileges).
 * @param bool $checkblocks Whether to check the user's blocked status.
 * @param array $userfields Cache for logged in user, containing keys 'userid', 'level' (optional), 'flags'.
 *
 * @return bool|string The permission error, or false if no error. Possible errors, in order of priority:
 *   'login' => the user should login or register
 *   'level' => a special privilege level (e.g. expert) or minimum number of points is required
 *   'userblock' => the user has been blocked
 *   'ipblock' => the ip address has been blocked
 *   'confirm' => the user should confirm their email address
 *   'approve' => the user needs to be approved by the site admins (no longer used as global permission)
 *   'limit' => the user or IP address has reached a rate limit (if $limitaction specified)
 *   false => the operation can go ahead
 */
function ilya_user_permit_error($permitoption = null, $limitaction = null, $userlevel = null, $checkblocks = true, $userfields = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA_INCLUDE_DIR . 'app/limits.php';

	if (!isset($userfields))
		$userfields = ilya_get_logged_in_user_cache();

	$userid = isset($userfields['userid']) ? $userfields['userid'] : null;

	if (!isset($userlevel))
		$userlevel = isset($userfields['level']) ? $userfields['level'] : null;

	$flags = isset($userfields['flags']) ? $userfields['flags'] : null;
	if (!$checkblocks)
		$flags &= ~ILYA_USER_FLAGS_USER_BLOCKED;

	$error = ilya_permit_error($permitoption, $userid, $userlevel, $flags);

	if ($checkblocks && !$error && ilya_is_ip_blocked())
		$error = 'ipblock';

	if (!$error && isset($userid) && ($flags & ILYA_USER_FLAGS_MUST_CONFIRM) && ilya_opt('confirm_user_emails'))
		$error = 'confirm';

	if (isset($limitaction) && !$error) {
		if (ilya_user_limits_remaining($limitaction) <= 0)
			$error = 'limit';
	}

	return $error;
}


/**
 * Check whether user can perform $permitoption. Result as for ilya_user_permit_error(...).
 *
 * @param string $permitoption Permission option name (from database) for action.
 * @param int $userid ID of user (null for no user).
 * @param int $userlevel Level to check against.
 * @param int $userflags Flags for this user.
 * @param int $userpoints User's points: if $userid is currently logged in, you can set $userpoints=null to retrieve them only if necessary.
 *
 * @return string|bool Reason the user is not permitted, or false if the operation can go ahead.
 */
function ilya_permit_error($permitoption, $userid, $userlevel, $userflags, $userpoints = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$permit = isset($permitoption) ? ilya_opt($permitoption) : ILYA_PERMIT_ALL;

	if (isset($userid) && ($permit == ILYA_PERMIT_POINTS || $permit == ILYA_PERMIT_POINTS_CONFIRMED || $permit == ILYA_PERMIT_APPROVED_POINTS)) {
		// deal with points threshold by converting as appropriate

		if (!isset($userpoints) && $userid == ilya_get_logged_in_userid())
			$userpoints = ilya_get_logged_in_points(); // allow late retrieval of points (to avoid unnecessary DB query when using external users)

		if ($userpoints >= ilya_opt($permitoption . '_points')) {
			$permit = $permit == ILYA_PERMIT_APPROVED_POINTS
				? ILYA_PERMIT_APPROVED
				: ($permit == ILYA_PERMIT_POINTS_CONFIRMED ? ILYA_PERMIT_CONFIRMED : ILYA_PERMIT_USERS); // convert if user has enough points
		} else
			$permit = ILYA_PERMIT_EXPERTS; // otherwise show a generic message so they're not tempted to collect points just for this
	}

	return ilya_permit_value_error($permit, $userid, $userlevel, $userflags);
}


/**
 * Check whether user can reach the permission level. Result as for ilya_user_permit_error(...).
 *
 * @param int $permit Permission constant.
 * @param int $userid ID of user (null for no user).
 * @param int $userlevel Level to check against.
 * @param int $userflags Flags for this user.
 *
 * @return string|bool Reason the user is not permitted, or false if the operation can go ahead
 */
function ilya_permit_value_error($permit, $userid, $userlevel, $userflags)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	if (!isset($userid) && $permit < ILYA_PERMIT_ALL)
		return 'login';

	$levelError =
		($permit <= ILYA_PERMIT_SUPERS && $userlevel < ILYA_USER_LEVEL_SUPER) ||
		($permit <= ILYA_PERMIT_ADMINS && $userlevel < ILYA_USER_LEVEL_ADMIN) ||
		($permit <= ILYA_PERMIT_MODERATORS && $userlevel < ILYA_USER_LEVEL_MODERATOR) ||
		($permit <= ILYA_PERMIT_EDITORS && $userlevel < ILYA_USER_LEVEL_EDITOR) ||
		($permit <= ILYA_PERMIT_EXPERTS && $userlevel < ILYA_USER_LEVEL_EXPERT);

	if ($levelError)
		return 'level';

	if (isset($userid) && ($userflags & ILYA_USER_FLAGS_USER_BLOCKED))
		return 'userblock';

	if ($permit >= ILYA_PERMIT_USERS)
		return false;

	if ($permit >= ILYA_PERMIT_CONFIRMED) {
		$confirmed = ($userflags & ILYA_USER_FLAGS_EMAIL_CONFIRMED);
		// not currently supported by single sign-on integration; approved users and above don't need confirmation
		if (!ILYA_FINAL_EXTERNAL_USERS && ilya_opt('confirm_user_emails') && $userlevel < ILYA_USER_LEVEL_APPROVED && !$confirmed) {
			return 'confirm';
		}
	} elseif ($permit >= ILYA_PERMIT_APPROVED) {
		// check user is approved, only if we require it
		if (ilya_opt('moderate_users') && $userlevel < ILYA_USER_LEVEL_APPROVED) {
			return 'approve';
		}
	}

	return false;
}


/**
 * Return whether a captcha is required for posts submitted by the current user. You can pass in a ILYA_USER_LEVEL_*
 * constant in $userlevel to consider the user at a different level to usual (e.g. if they are performing this action
 * in a category for which they have elevated privileges).
 *
 * Possible results:
 * 'login' => captcha required because the user is not logged in
 * 'approve' => captcha required because the user has not been approved
 * 'confirm' => captcha required because the user has not confirmed their email address
 * false => captcha is not required
 * @param $userlevel
 * @return bool|mixed|string
 */
function ilya_user_captcha_reason($userlevel = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$reason = false;
	if (!isset($userlevel))
		$userlevel = ilya_get_logged_in_level();

	if ($userlevel < ILYA_USER_LEVEL_APPROVED) { // approved users and above aren't shown captchas
		$userid = ilya_get_logged_in_userid();

		if (ilya_opt('captcha_on_anon_post') && !isset($userid))
			$reason = 'login';
		elseif (ilya_opt('moderate_users') && ilya_opt('captcha_on_unapproved'))
			$reason = 'approve';
		elseif (ilya_opt('confirm_user_emails') && ilya_opt('captcha_on_unconfirmed') && !(ilya_get_logged_in_flags() & ILYA_USER_FLAGS_EMAIL_CONFIRMED))
			$reason = 'confirm';
	}

	return $reason;
}


/**
 * Return whether a captcha should be presented to the logged in user for writing posts. You can pass in a
 * ILYA_USER_LEVEL_* constant in $userlevel to consider the user at a different level to usual.
 * @param $userlevel
 * @return bool|mixed
 */
function ilya_user_use_captcha($userlevel = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return ilya_user_captcha_reason($userlevel) != false;
}


/**
 * Return whether moderation is required for posts submitted by the current user. You can pass in a ILYA_USER_LEVEL_*
 * constant in $userlevel to consider the user at a different level to usual (e.g. if they are performing this action
 * in a category for which they have elevated privileges).
 *
 * Possible results:
 * 'login' => moderation required because the user is not logged in
 * 'approve' => moderation required because the user has not been approved
 * 'confirm' => moderation required because the user has not confirmed their email address
 * 'points' => moderation required because the user has insufficient points
 * false => moderation is not required
 * @param $userlevel
 * @return bool|string
 */
function ilya_user_moderation_reason($userlevel = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$reason = false;
	if (!isset($userlevel))
		$userlevel = ilya_get_logged_in_level();

	if ($userlevel < ILYA_USER_LEVEL_EXPERT && ilya_user_permit_error('permit_moderate')) {
		// experts and above aren't moderated; if the user can approve posts, no point in moderating theirs
		$userid = ilya_get_logged_in_userid();

		if (isset($userid)) {
			if (ilya_opt('moderate_users') && ilya_opt('moderate_unapproved') && ($userlevel < ILYA_USER_LEVEL_APPROVED))
				$reason = 'approve';
			elseif (ilya_opt('confirm_user_emails') && ilya_opt('moderate_unconfirmed') && !(ilya_get_logged_in_flags() & ILYA_USER_FLAGS_EMAIL_CONFIRMED))
				$reason = 'confirm';
			elseif (ilya_opt('moderate_by_points') && (ilya_get_logged_in_points() < ilya_opt('moderate_points_limit')))
				$reason = 'points';

		} elseif (ilya_opt('moderate_anon_post'))
			$reason = 'login';
	}

	return $reason;
}


/**
 * Return the label to display for $userfield as retrieved from the database, using default if no name set
 * @param $userfield
 * @return string
 */
function ilya_user_userfield_label($userfield)
{
	if (isset($userfield['content']))
		return $userfield['content'];

	else {
		$defaultlabels = array(
			'name' => 'users/full_name',
			'about' => 'users/about',
			'location' => 'users/location',
			'website' => 'users/website',
		);

		if (isset($defaultlabels[$userfield['title']]))
			return ilya_lang($defaultlabels[$userfield['title']]);
	}

	return '';
}


/**
 * Set or extend the cookie in browser of non logged-in users which identifies them for the purposes of form security (anti-CSRF protection)
 */
function ilya_set_form_security_key()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_form_key_cookie_set;

	if (!ilya_is_logged_in() && !@$ilya_form_key_cookie_set) {
		$ilya_form_key_cookie_set = true;

		if (strlen(@$_COOKIE['ilya_key']) != ILYA_FORM_KEY_LENGTH) {
			require_once ILYA_INCLUDE_DIR . 'util/string.php';
			$_COOKIE['ilya_key'] = ilya_random_alphanum(ILYA_FORM_KEY_LENGTH);
		}

		setcookie('ilya_key', $_COOKIE['ilya_key'], time() + 2 * ILYA_FORM_EXPIRY_SECS, '/', ILYA_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true); // extend on every page request
	}
}


/**
 * Return the form security (anti-CSRF protection) hash for an $action (any string), that can be performed within
 * ILYA_FORM_EXPIRY_SECS of $timestamp (in unix seconds) by the current user.
 * @param $action
 * @param $timestamp
 * @return mixed|string
 */
function ilya_calc_form_security_hash($action, $timestamp)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$salt = ilya_opt('form_security_salt');

	if (ilya_is_logged_in())
		return sha1($salt . '/' . $action . '/' . $timestamp . '/' . ilya_get_logged_in_userid() . '/' . ilya_get_logged_in_user_field('passsalt'));
	else
		return sha1($salt . '/' . $action . '/' . $timestamp . '/' . @$_COOKIE['ilya_key']); // lower security for non logged in users - code+cookie can be transferred
}


/**
 * Return the full form security (anti-CSRF protection) code for an $action (any string) performed within
 * ILYA_FORM_EXPIRY_SECS of now by the current user.
 * @param $action
 * @return mixed|string
 */
function ilya_get_form_security_code($action)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	ilya_set_form_security_key();

	$timestamp = ilya_opt('db_time');

	return (int)ilya_is_logged_in() . '-' . $timestamp . '-' . ilya_calc_form_security_hash($action, $timestamp);
}


/**
 * Return whether $value matches the expected form security (anti-CSRF protection) code for $action (any string) and
 * that the code has not expired (if more than ILYA_FORM_EXPIRY_SECS have passed). Logs causes for suspicion.
 * @param $action
 * @param $value
 * @return bool
 */
function ilya_check_form_security_code($action, $value)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$reportproblems = array();
	$silentproblems = array();

	if (!isset($value)) {
		$silentproblems[] = 'code missing';

	} elseif (!strlen($value)) {
		$silentproblems[] = 'code empty';

	} else {
		$parts = explode('-', $value);

		if (count($parts) == 3) {
			$loggedin = $parts[0];
			$timestamp = $parts[1];
			$hash = $parts[2];
			$timenow = ilya_opt('db_time');

			if ($timestamp > $timenow) {
				$reportproblems[] = 'time ' . ($timestamp - $timenow) . 's in future';
			} elseif ($timestamp < ($timenow - ILYA_FORM_EXPIRY_SECS)) {
				$silentproblems[] = 'timeout after ' . ($timenow - $timestamp) . 's';
			}

			if (ilya_is_logged_in()) {
				if (!$loggedin) {
					$silentproblems[] = 'now logged in';
				}
			} else {
				if ($loggedin) {
					$silentproblems[] = 'now logged out';
				} else {
					$key = @$_COOKIE['ilya_key'];

					if (!isset($key)) {
						$silentproblems[] = 'key cookie missing';
					} elseif (!strlen($key)) {
						$silentproblems[] = 'key cookie empty';
					} elseif (strlen($key) != ILYA_FORM_KEY_LENGTH) {
						$reportproblems[] = 'key cookie ' . $key . ' invalid';
					}
				}
			}

			if (empty($silentproblems) && empty($reportproblems)) {
				if (!hash_equals(strtolower(ilya_calc_form_security_hash($action, $timestamp)), strtolower($hash))) {
					$reportproblems[] = 'code mismatch';
				}
			}

		} else {
			$reportproblems[] = 'code ' . $value . ' malformed';
		}
	}

	if (!empty($reportproblems) && ILYA_DEBUG_PERFORMANCE) {
		@error_log(
			'PHP IlyaIdea form security violation for ' . $action .
			' by ' . (ilya_is_logged_in() ? ('userid ' . ilya_get_logged_in_userid()) : 'anonymous') .
			' (' . implode(', ', array_merge($reportproblems, $silentproblems)) . ')' .
			' on ' . @$_SERVER['REQUEST_URI'] .
			' via ' . @$_SERVER['HTTP_REFERER']
		);
	}

	return (empty($silentproblems) && empty($reportproblems));
}


/**
 * Return the URL for the Gravatar corresponding to $email, constrained to $size
 *
 * @since 1.8.0
 * @param string $email The email of the Gravatar to return
 * @param int|null $size The size of the Gravatar to return. If omitted the default size will be used
 * @return string The URL to the Gravatar of the user
 */
function ilya_get_gravatar_url($email, $size = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$link = 'https://www.gravatar.com/avatar/%s';

	$params = array(md5(strtolower(trim($email))));

	$size = (int)$size;
	if ($size > 0) {
		$link .= '?s=%d';
		$params[] = $size;
	}

	return vsprintf($link, $params);
}
