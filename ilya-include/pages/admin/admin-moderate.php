<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for admin page showing questions, answers and comments waiting for approval


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
	header('Location: ../../../');
	exit;
}

require_once ILYA__INCLUDE_DIR . 'app/admin.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';


// Find queued questions, answers, comments

$userid = ilya_get_logged_in_userid();

list($queuedquestions, $queuedanswers, $queuedcomments) = ilya_db_select_with_pending(
	ilya_db_qs_selectspec($userid, 'created', 0, null, null, 'Q_QUEUED', true),
	ilya_db_recent_a_qs_selectspec($userid, 0, null, null, 'A_QUEUED', true),
	ilya_db_recent_c_qs_selectspec($userid, 0, null, null, 'C_QUEUED', true)
);


// Check admin privileges (do late to allow one DB query)

if (ilya_user_maximum_permit_error('permit_moderate')) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}


// Check to see if any were approved/rejected here

$pageerror = ilya_admin_check_clicks();


// Combine sets of questions and remove those this user has no permission to moderate

$questions = ilya_any_sort_by_date(array_merge($queuedquestions, $queuedanswers, $queuedcomments));

if (ilya_user_permit_error('permit_moderate')) { // if user not allowed to moderate all posts
	foreach ($questions as $index => $question) {
		if (ilya_user_post_permit_error('permit_moderate', $question))
			unset($questions[$index]);
	}
}


// Get information for users

$usershtml = ilya_userids_handles_html(ilya_any_get_userids_handles($questions));


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/recent_approve_title');
$ilya_content['error'] = isset($pageerror) ? $pageerror : ilya_admin_page_error();

$ilya_content['q_list'] = array(
	'form' => array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'hidden' => array(
			'code' => ilya_get_form_security_code('admin/click'),
		),
	),

	'qs' => array(),
);

if (count($questions)) {
	foreach ($questions as $question) {
		$postid = ilya_html(isset($question['opostid']) ? $question['opostid'] : $question['postid']);
		$elementid = 'p' . $postid;

		$htmloptions = ilya_post_html_options($question);
		$htmloptions['voteview'] = false;
		$htmloptions['tagsview'] = !isset($question['opostid']);
		$htmloptions['answersview'] = false;
		$htmloptions['viewsview'] = false;
		$htmloptions['contentview'] = true;
		$htmloptions['elementid'] = $elementid;

		$htmlfields = ilya_any_to_q_html_fields($question, $userid, ilya_cookie_get(), $usershtml, null, $htmloptions);

		if (isset($htmlfields['what_url'])) // link directly to relevant content
			$htmlfields['url'] = $htmlfields['what_url'];

		$posttype = ilya_strtolower(isset($question['obasetype']) ? $question['obasetype'] : $question['basetype']);
		switch ($posttype) {
			case 'q':
			default:
				$approveKey = 'question/approve_q_popup';
				$rejectKey = 'question/reject_q_popup';
				break;
			case 'a':
				$approveKey = 'question/approve_a_popup';
				$rejectKey = 'question/reject_a_popup';
				break;
			case 'c':
				$approveKey = 'question/approve_c_popup';
				$rejectKey = 'question/reject_c_popup';
				break;
		}

		$htmlfields['form'] = array(
			'style' => 'light',

			'buttons' => array(
				// Possible values for popup: approve_q_popup, approve_a_popup, approve_c_popup
				'approve' => array(
					'tags' => 'name="admin_' . $postid . '_approve" onclick="return ilya_admin_click(this);"',
					'label' => ilya_lang_html('question/approve_button'),
					'popup' => ilya_lang_html($approveKey),
				),

				// Possible values for popup: reject_q_popup, reject_a_popup, reject_c_popup
				'reject' => array(
					'tags' => 'name="admin_' . $postid . '_reject" onclick="return ilya_admin_click(this);"',
					'label' => ilya_lang_html('question/reject_button'),
					'popup' => ilya_lang_html($rejectKey),
				),
			),
		);

		$ilya_content['q_list']['qs'][] = $htmlfields;
	}

} else
	$ilya_content['title'] = ilya_lang_html('admin/no_approve_found');


$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();
$ilya_content['script_rel'][] = 'ilya-content/ilya-admin.js?' . ILYA__VERSION;


return $ilya_content;
