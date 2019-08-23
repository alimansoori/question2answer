<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax create comment requests


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

require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'app/limits.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';


// Load relevant information about this question and the comment parent

$questionid = ilya_post_text('c_questionid');
$parentid = ilya_post_text('c_parentid');
$userid = ilya_get_logged_in_userid();

list($question, $parent, $children) = ilya_db_select_with_pending(
	ilya_db_full_post_selectspec($userid, $questionid),
	ilya_db_full_post_selectspec($userid, $parentid),
	ilya_db_full_child_posts_selectspec($userid, $parentid)
);


// Check if the question and parent exist, and whether the user has permission to do this

if (@$question['basetype'] == 'Q' && (@$parent['basetype'] == 'Q' || @$parent['basetype'] == 'A') &&
	!ilya_user_post_permit_error('permit_post_c', $parent, ILYA__LIMIT_COMMENTS)
) {
	require_once ILYA__INCLUDE_DIR . 'app/captcha.php';
	require_once ILYA__INCLUDE_DIR . 'app/format.php';
	require_once ILYA__INCLUDE_DIR . 'app/post-create.php';
	require_once ILYA__INCLUDE_DIR . 'app/cookies.php';
	require_once ILYA__INCLUDE_DIR . 'pages/question-view.php';
	require_once ILYA__INCLUDE_DIR . 'pages/question-submit.php';
	require_once ILYA__INCLUDE_DIR . 'util/sort.php';


	// Try to create the new comment

	$usecaptcha = ilya_user_use_captcha(ilya_user_level_for_post($question));
	$commentid = ilya_page_q_add_c_submit($question, $parent, $children, $usecaptcha, $in, $errors);


	// If successful, page content will be updated via Ajax

	if (isset($commentid)) {
		$children = ilya_db_select_with_pending(ilya_db_full_child_posts_selectspec($userid, $parentid));

		$parent = $parent + ilya_page_q_post_rules($parent, ($questionid == $parentid) ? null : $question, null, $children);
		// in theory we should retrieve the parent's siblings for the above, but they're not going to be relevant

		foreach ($children as $key => $child) {
			$children[$key] = $child + ilya_page_q_post_rules($child, $parent, $children, null);
		}

		$usershtml = ilya_userids_handles_html($children, true);

		ilya_sort_by($children, 'created');

		$c_list = ilya_page_q_comment_follow_list($question, $parent, $children, true, $usershtml, false, null);

		$themeclass = ilya_load_theme_class(ilya_get_site_theme(), 'ajax-comments', null, null);
		$themeclass->initialize();

		echo "ILYA__AJAX_RESPONSE\n1\n";


		// send back the ID of the new comment
		echo ilya_anchor('C', $commentid) . "\n";


		// send back the HTML
		$themeclass->c_list_items($c_list['cs']);

		return;
	}
}

echo "ILYA__AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if there were any problems
