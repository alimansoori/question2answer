<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax single clicks on comments


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

require_once ILYA__INCLUDE_DIR . 'app/cookies.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';
require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'pages/question-view.php';
require_once ILYA__INCLUDE_DIR . 'pages/question-submit.php';


// Load relevant information about this comment

$commentid = ilya_post_text('commentid');
$questionid = ilya_post_text('questionid');
$parentid = ilya_post_text('parentid');

$userid = ilya_get_logged_in_userid();

list($comment, $question, $parent, $children) = ilya_db_select_with_pending(
	ilya_db_full_post_selectspec($userid, $commentid),
	ilya_db_full_post_selectspec($userid, $questionid),
	ilya_db_full_post_selectspec($userid, $parentid),
	ilya_db_full_child_posts_selectspec($userid, $parentid)
);


// Check if there was an operation that succeeded

if (@$comment['basetype'] == 'C' && @$question['basetype'] == 'Q' &&
	(@$parent['basetype'] == 'Q' || @$parent['basetype'] == 'A')
) {
	$comment = $comment + ilya_page_q_post_rules($comment, $parent, $children, null); // array union

	if (ilya_page_q_single_click_c($comment, $question, $parent, $error)) {
		$comment = ilya_db_select_with_pending(ilya_db_full_post_selectspec($userid, $commentid));

		// If so, page content to be updated via Ajax

		echo "ILYA__AJAX_RESPONSE\n1";

		// If the comment was not deleted...

		if (isset($comment)) {
			$parent = $parent + ilya_page_q_post_rules($parent, ($questionid == $parentid) ? null : $question, null, $children);
			// in theory we should retrieve the parent's siblings for the above, but they're not going to be relevant
			$comment = $comment + ilya_page_q_post_rules($comment, $parent, $children, null);

			$usershtml = ilya_userids_handles_html(array($comment), true);

			$c_view = ilya_page_q_comment_view($question, $parent, $comment, $usershtml, false);

			$themeclass = ilya_load_theme_class(ilya_get_site_theme(), 'ajax-comment', null, null);
			$themeclass->initialize();


			// ... send back the HTML for it

			echo "\n";

			$themeclass->c_list_item($c_view);
		}

		return;
	}
}


echo "ILYA__AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if something failed
