<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax create answer requests


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

require_once ILYA__INCLUDE_DIR . 'app/posts.php';
require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'app/limits.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';


// Load relevant information about this question

$questionid = ilya_post_text('a_questionid');
$userid = ilya_get_logged_in_userid();

list($question, $childposts) = ilya_db_select_with_pending(
	ilya_db_full_post_selectspec($userid, $questionid),
	ilya_db_full_child_posts_selectspec($userid, $questionid)
);


// Check if the question exists, is not closed, and whether the user has permission to do this

if (@$question['basetype'] == 'Q' && !ilya_post_is_closed($question) && !ilya_user_post_permit_error('permit_post_a', $question, ILYA__LIMIT_ANSWERS)) {
	require_once ILYA__INCLUDE_DIR . 'app/captcha.php';
	require_once ILYA__INCLUDE_DIR . 'app/format.php';
	require_once ILYA__INCLUDE_DIR . 'app/post-create.php';
	require_once ILYA__INCLUDE_DIR . 'app/cookies.php';
	require_once ILYA__INCLUDE_DIR . 'pages/question-view.php';
	require_once ILYA__INCLUDE_DIR . 'pages/question-submit.php';


	// Try to create the new answer

	$usecaptcha = ilya_user_use_captcha(ilya_user_level_for_post($question));
	$answers = ilya_page_q_load_as($question, $childposts);
	$answerid = ilya_page_q_add_a_submit($question, $answers, $usecaptcha, $in, $errors);

	// If successful, page content will be updated via Ajax

	if (isset($answerid)) {
		$answer = ilya_db_select_with_pending(ilya_db_full_post_selectspec($userid, $answerid));

		$question = $question + ilya_page_q_post_rules($question, null, null, $childposts); // array union
		$answer = $answer + ilya_page_q_post_rules($answer, $question, $answers, null);

		$usershtml = ilya_userids_handles_html(array($answer), true);

		$a_view = ilya_page_q_answer_view($question, $answer, false, $usershtml, false);

		$themeclass = ilya_load_theme_class(ilya_get_site_theme(), 'ajax-answer', null, null);
		$themeclass->initialize();

		echo "ILYA__AJAX_RESPONSE\n1\n";


		// Send back whether the 'answer' button should still be visible

		echo (int)ilya_opt('allow_multi_answers') . "\n";


		// Send back the count of answers

		$countanswers = $question['acount'] + 1;

		if ($countanswers == 1) {
			echo ilya_lang_html('question/1_answer_title') . "\n";
		} else {
			echo ilya_lang_html_sub('question/x_answers_title', $countanswers) . "\n";
		}


		// Send back the HTML

		$themeclass->a_list_item($a_view);

		return;
	}
}


echo "ILYA__AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if there were any problems
