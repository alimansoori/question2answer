<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for user page showing all user's questions


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

require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'app/format.php';


// $handle, $userhtml are already set by /ilya-include/page/user.php - also $userid if using external user integration

$start = ilya_get_start();


// Find the questions for this user

$loginuserid = ilya_get_logged_in_userid();
$identifier = QA_FINAL_EXTERNAL_USERS ? $userid : $handle;

list($useraccount, $userpoints, $questions) = ilya_db_select_with_pending(
	QA_FINAL_EXTERNAL_USERS ? null : ilya_db_user_account_selectspec($handle, false),
	ilya_db_user_points_selectspec($identifier),
	ilya_db_user_recent_qs_selectspec($loginuserid, $identifier, ilya_opt_if_loaded('page_size_qs'), $start)
);

if (!QA_FINAL_EXTERNAL_USERS && !is_array($useraccount)) // check the user exists
	return include QA_INCLUDE_DIR . 'ilya-page-not-found.php';


// Get information on user questions

$pagesize = ilya_opt('page_size_qs');
$count = (int)@$userpoints['qposts'];
$questions = array_slice($questions, 0, $pagesize);
$usershtml = ilya_userids_handles_html($questions, false);


// Prepare content for theme

$ilya_content = ilya_content_prepare(true);

if (count($questions))
	$ilya_content['title'] = ilya_lang_html_sub('profile/questions_by_x', $userhtml);
else
	$ilya_content['title'] = ilya_lang_html_sub('profile/no_questions_by_x', $userhtml);


// Recent questions by this user

$ilya_content['q_list']['form'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '"',

	'hidden' => array(
		'code' => ilya_get_form_security_code('vote'),
	),
);

$ilya_content['q_list']['qs'] = array();

$htmldefaults = ilya_post_html_defaults('Q');
$htmldefaults['whoview'] = false;
$htmldefaults['avatarsize'] = 0;

foreach ($questions as $question) {
	$ilya_content['q_list']['qs'][] = ilya_post_html_fields($question, $loginuserid, ilya_cookie_get(),
		$usershtml, null, ilya_post_html_options($question, $htmldefaults));
}

$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $count, ilya_opt('pages_prev_next'));


// Sub menu for navigation in user pages

$ismyuser = isset($loginuserid) && $loginuserid == (QA_FINAL_EXTERNAL_USERS ? $userid : $useraccount['userid']);
$ilya_content['navigation']['sub'] = ilya_user_sub_navigation($handle, 'questions', $ismyuser);


return $ilya_content;
