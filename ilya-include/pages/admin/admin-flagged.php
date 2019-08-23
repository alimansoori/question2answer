<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for admin page showing posts with the most flags


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


// Find most flagged questions, answers, comments

$userid = ilya_get_logged_in_userid();

$questions = ilya_db_select_with_pending(
	ilya_db_flagged_post_qs_selectspec($userid, 0, true)
);


// Check admin privileges (do late to allow one DB query)

if (ilya_user_maximum_permit_error('permit_hide_show')) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}


// Check to see if any were cleared or hidden here

$pageerror = ilya_admin_check_clicks();


// Remove questions the user has no permission to hide/show

if (ilya_user_permit_error('permit_hide_show')) { // if user not allowed to show/hide all posts
	foreach ($questions as $index => $question) {
		if (ilya_user_post_permit_error('permit_hide_show', $question)) {
			unset($questions[$index]);
		}
	}
}


// Get information for users

$usershtml = ilya_userids_handles_html(ilya_any_get_userids_handles($questions));


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/most_flagged_title');
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
		$htmloptions['tagsview'] = ($question['obasetype'] == 'Q');
		$htmloptions['answersview'] = false;
		$htmloptions['viewsview'] = false;
		$htmloptions['contentview'] = true;
		$htmloptions['flagsview'] = true;
		$htmloptions['elementid'] = $elementid;

		$htmlfields = ilya_any_to_q_html_fields($question, $userid, ilya_cookie_get(), $usershtml, null, $htmloptions);

		if (isset($htmlfields['what_url'])) // link directly to relevant content
			$htmlfields['url'] = $htmlfields['what_url'];

		$htmlfields['form'] = array(
			'style' => 'light',

			'buttons' => array(
				'clearflags' => array(
					'tags' => 'name="admin_' . $postid . '_clearflags" onclick="return ilya_admin_click(this);"',
					'label' => ilya_lang_html('question/clear_flags_button'),
				),

				'hide' => array(
					'tags' => 'name="admin_' . $postid . '_hide" onclick="return ilya_admin_click(this);"',
					'label' => ilya_lang_html('question/hide_button'),
				),
			),
		);

		$ilya_content['q_list']['qs'][] = $htmlfields;
	}

} else
	$ilya_content['title'] = ilya_lang_html('admin/no_flagged_found');


$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();
$ilya_content['script_rel'][] = 'ilya-content/ilya-admin.js?' . ILYA__VERSION;


return $ilya_content;
