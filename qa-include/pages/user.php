<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for user profile page


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


// Determine the identify of the user

$handle = ilya_request_part(1);

if (!strlen($handle)) {
	$handle = ilya_get_logged_in_handle();
	ilya_redirect(!empty($handle) ? 'user/' . $handle : 'users');
}


// Get the HTML to display for the handle, and if we're using external users, determine the userid

if (QA_FINAL_EXTERNAL_USERS) {
	$userid = ilya_handle_to_userid($handle);
	if (!isset($userid))
		return include QA_INCLUDE_DIR . 'ilya-page-not-found.php';

	$usershtml = ilya_get_users_html(array($userid), false, ilya_path_to_root(), true);
	$userhtml = @$usershtml[$userid];

} else
	$userhtml = ilya_html($handle);


// Display the appropriate page based on the request

switch (ilya_request_part(2)) {
	case 'wall':
		ilya_set_template('user-wall');
		$ilya_content = include QA_INCLUDE_DIR . 'pages/user-wall.php';
		break;

	case 'activity':
		ilya_set_template('user-activity');
		$ilya_content = include QA_INCLUDE_DIR . 'pages/user-activity.php';
		break;

	case 'questions':
		ilya_set_template('user-questions');
		$ilya_content = include QA_INCLUDE_DIR . 'pages/user-questions.php';
		break;

	case 'answers':
		ilya_set_template('user-answers');
		$ilya_content = include QA_INCLUDE_DIR . 'pages/user-answers.php';
		break;

	case null:
		$ilya_content = include QA_INCLUDE_DIR . 'pages/user-profile.php';
		break;

	default:
		$ilya_content = include QA_INCLUDE_DIR . 'ilya-page-not-found.php';
		break;
}

return $ilya_content;
