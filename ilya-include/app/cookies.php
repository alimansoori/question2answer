<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: User cookie management (application level) for tracking anonymous posts


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


/**
 * Return the user identification cookie sent by the browser for this page request, or null if none
 */
function ilya_cookie_get()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return isset($_COOKIE['ilya_id']) ? ilya_gpc_to_string($_COOKIE['ilya_id']) : null;
}


/**
 * Return user identification cookie sent by browser if valid, or create a new one if not.
 * Either way, extend for another year (this is used when an anonymous post is created)
 */
function ilya_cookie_get_create()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/cookies.php';

	$cookieid = ilya_cookie_get();

	if (!isset($cookieid) || !ilya_db_cookie_exists($cookieid)) {
		// cookie is invalid
		$cookieid = ilya_db_cookie_create(ilya_remote_ip_address());
	}

	setcookie('ilya_id', $cookieid, time() + 86400 * 365, '/', ILYA__COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true);
	$_COOKIE['ilya_id'] = $cookieid;

	return $cookieid;
}


/**
 * Called after a database write $action performed by a user identified by $cookieid,
 * relating to $questionid, $answerid and/or $commentid
 * @param $cookieid
 * @param $action
 */
function ilya_cookie_report_action($cookieid, $action)
{
	require_once ILYA__INCLUDE_DIR . 'db/cookies.php';

	ilya_db_cookie_written($cookieid, ilya_remote_ip_address());
}
