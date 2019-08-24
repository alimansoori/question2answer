<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax single clicks on answer


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

require_once ILYA_INCLUDE_DIR . 'app/cookies.php';
require_once ILYA_INCLUDE_DIR . 'app/format.php';
require_once ILYA_INCLUDE_DIR . 'app/users.php';
require_once ILYA_INCLUDE_DIR . 'db/selects.php';
require_once ILYA_INCLUDE_DIR . 'pages/question-view.php';
require_once ILYA_INCLUDE_DIR . 'pages/question-submit.php';
require_once ILYA_INCLUDE_DIR . 'util/sort.php';


// Load relevant information about this answer

$answerid = ilya_post_text('answerid');
$questionid = ilya_post_text('questionid');

$userid = ilya_get_logged_in_userid();

list($answer, $question, $qchildposts, $achildposts) = ilya_db_select_with_pending(
	ilya_db_full_post_selectspec($userid, $answerid),
	ilya_db_full_post_selectspec($userid, $questionid),
	ilya_db_full_child_posts_selectspec($userid, $questionid),
	ilya_db_full_child_posts_selectspec($userid, $answerid)
);


// Check if there was an operation that succeeded

if (@$answer['basetype'] == 'A' && @$question['basetype'] == 'Q') {
	$answers = ilya_page_q_load_as($question, $qchildposts);

	$question = $question + ilya_page_q_post_rules($question, null, null, $qchildposts); // array union
	$answer = $answer + ilya_page_q_post_rules($answer, $question, $qchildposts, $achildposts);

	if (ilya_page_q_single_click_a($answer, $question, $answers, $achildposts, false, $error)) {
		list($answer, $question) = ilya_db_select_with_pending(
			ilya_db_full_post_selectspec($userid, $answerid),
			ilya_db_full_post_selectspec($userid, $questionid)
		);


		// If so, page content to be updated via Ajax

		echo "ILYA_AJAX_RESPONSE\n1\n";


		// Send back new count of answers

		$countanswers = $question['acount'];

		if ($countanswers == 1)
			echo ilya_lang_html('question/1_answer_title');
		else
			echo ilya_lang_html_sub('question/x_answers_title', $countanswers);


		// If the answer was not deleted....

		if (isset($answer)) {
			$question = $question + ilya_page_q_post_rules($question, null, null, $qchildposts); // array union
			$answer = $answer + ilya_page_q_post_rules($answer, $question, $qchildposts, $achildposts);

			$commentsfollows = ilya_page_q_load_c_follows($question, $qchildposts, $achildposts);

			foreach ($commentsfollows as $key => $commentfollow) {
				$commentsfollows[$key] = $commentfollow + ilya_page_q_post_rules($commentfollow, $answer, $commentsfollows, null);
			}

			$usershtml = ilya_userids_handles_html(array_merge(array($answer), $commentsfollows), true);
			ilya_sort_by($commentsfollows, 'created');

			$a_view = ilya_page_q_answer_view($question, $answer, ($answer['postid'] == $question['selchildid'] && $answer['type'] == 'A'),
				$usershtml, false);

			$a_view['c_list'] = ilya_page_q_comment_follow_list($question, $answer, $commentsfollows, false, $usershtml, false, null);

			$themeclass = ilya_load_theme_class(ilya_get_site_theme(), 'ajax-answer', null, null);
			$themeclass->initialize();


			// ... send back the HTML for it

			echo "\n";

			$themeclass->a_list_item($a_view);
		}

		return;
	}
}


echo "ILYA_AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if something failed
