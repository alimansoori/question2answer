<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Common functions for question page form submission, either regular or via Ajax


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


require_once ILYA_INCLUDE_DIR . 'app/post-create.php';
require_once ILYA_INCLUDE_DIR . 'app/post-update.php';


/**
 * Checks for a POSTed click on $question by the current user and returns true if it was permitted and processed. Pass
 * in the question's $answers, all $commentsfollows from it or its answers, and its closing $closepost (or null if
 * none). If there is an error to display, it will be passed out in $error.
 * @param $question
 * @param $answers
 * @param $commentsfollows
 * @param $closepost
 * @param $error
 * @return bool
 */
function ilya_page_q_single_click_q($question, $answers, $commentsfollows, $closepost, &$error)
{
	require_once ILYA_INCLUDE_DIR . 'app/post-update.php';
	require_once ILYA_INCLUDE_DIR . 'app/limits.php';

	$userid = ilya_get_logged_in_userid();
	$handle = ilya_get_logged_in_handle();
	$cookieid = ilya_cookie_get();

	if (ilya_clicked('q_doreopen') && $question['reopenable'] && ilya_page_q_click_check_form_code($question, $error)) {
		ilya_question_close_clear($question, $closepost, $userid, $handle, $cookieid);
		return true;
	}

	if ((ilya_clicked('q_dohide') && $question['hideable']) || (ilya_clicked('q_doreject') && $question['moderatable'])) {
		if (ilya_page_q_click_check_form_code($question, $error)) {
			ilya_question_set_status($question, ILYA_POST_STATUS_HIDDEN, $userid, $handle, $cookieid, $answers, $commentsfollows, $closepost);
			return true;
		}
	}

	if ((ilya_clicked('q_doreshow') && $question['reshowable']) || (ilya_clicked('q_doapprove') && $question['moderatable'])) {
		if (ilya_page_q_click_check_form_code($question, $error)) {
			if ($question['moderatable'] || $question['reshowimmed']) {
				$status = ILYA_POST_STATUS_NORMAL;

			} else {
				$in = ilya_page_q_prepare_post_for_filters($question);
				$filtermodules = ilya_load_modules_with('filter', 'filter_question'); // run through filters but only for queued status

				foreach ($filtermodules as $filtermodule) {
					$tempin = $in; // always pass original question in because we aren't modifying anything else
					$filtermodule->filter_question($tempin, $temperrors, $question);
					$in['queued'] = $tempin['queued']; // only preserve queued status in loop
				}

				$status = $in['queued'] ? ILYA_POST_STATUS_QUEUED : ILYA_POST_STATUS_NORMAL;
			}

			ilya_question_set_status($question, $status, $userid, $handle, $cookieid, $answers, $commentsfollows, $closepost);
			return true;
		}
	}

	if (ilya_clicked('q_doclaim') && $question['claimable'] && ilya_page_q_click_check_form_code($question, $error)) {
		if (ilya_user_limits_remaining(ILYA_LIMIT_QUESTIONS)) { // already checked 'permit_post_q'
			ilya_question_set_userid($question, $userid, $handle, $cookieid);
			return true;

		} else
			$error = ilya_lang_html('question/ask_limit');
	}

	if (ilya_clicked('q_doflag') && $question['flagbutton'] && ilya_page_q_click_check_form_code($question, $error)) {
		require_once ILYA_INCLUDE_DIR . 'app/votes.php';

		$error = ilya_flag_error_html($question, $userid, ilya_request());
		if (!$error) {
			if (ilya_flag_set_tohide($question, $userid, $handle, $cookieid, $question))
				ilya_question_set_status($question, ILYA_POST_STATUS_HIDDEN, null, null, null, $answers, $commentsfollows, $closepost); // hiding not really by this user so pass nulls
			return true;
		}
	}

	if (ilya_clicked('q_dounflag') && $question['unflaggable'] && ilya_page_q_click_check_form_code($question, $error)) {
		require_once ILYA_INCLUDE_DIR . 'app/votes.php';

		ilya_flag_clear($question, $userid, $handle, $cookieid);
		return true;
	}

	if (ilya_clicked('q_doclearflags') && $question['clearflaggable'] && ilya_page_q_click_check_form_code($question, $error)) {
		require_once ILYA_INCLUDE_DIR . 'app/votes.php';

		ilya_flags_clear_all($question, $userid, $handle, $cookieid);
		return true;
	}

	return false;
}


/**
 * Checks for a POSTed click on $answer by the current user and returns true if it was permitted and processed. Pass in
 * the $question, all of its $answers, and all $commentsfollows from it or its answers. Set $allowselectmove to whether
 * it is legitimate to change the selected answer for the question from one to another (this can't be done via Ajax).
 * If there is an error to display, it will be passed out in $error.
 * @param $answer
 * @param $question
 * @param $answers
 * @param $commentsfollows
 * @param $allowselectmove
 * @param $error
 * @return bool
 */
function ilya_page_q_single_click_a($answer, $question, $answers, $commentsfollows, $allowselectmove, &$error)
{
	$userid = ilya_get_logged_in_userid();
	$handle = ilya_get_logged_in_handle();
	$cookieid = ilya_cookie_get();

	$prefix = 'a' . $answer['postid'] . '_';

	if (ilya_clicked($prefix . 'doselect') && $question['aselectable'] && ($allowselectmove || ((!isset($question['selchildid'])) && !ilya_opt('do_close_on_select'))) && ilya_page_q_click_check_form_code($answer, $error)) {
		ilya_question_set_selchildid($userid, $handle, $cookieid, $question, $answer['postid'], $answers);
		return true;
	}

	if (ilya_clicked($prefix . 'dounselect') && $question['aselectable'] && ($question['selchildid'] == $answer['postid']) && ($allowselectmove || !ilya_opt('do_close_on_select')) && ilya_page_q_click_check_form_code($answer, $error)) {
		ilya_question_set_selchildid($userid, $handle, $cookieid, $question, null, $answers);
		return true;
	}

	if ((ilya_clicked($prefix . 'dohide') && $answer['hideable']) || (ilya_clicked($prefix . 'doreject') && $answer['moderatable'])) {
		if (ilya_page_q_click_check_form_code($answer, $error)) {
			ilya_answer_set_status($answer, ILYA_POST_STATUS_HIDDEN, $userid, $handle, $cookieid, $question, $commentsfollows);
			return true;
		}
	}

	if ((ilya_clicked($prefix . 'doreshow') && $answer['reshowable']) || (ilya_clicked($prefix . 'doapprove') && $answer['moderatable'])) {
		if (ilya_page_q_click_check_form_code($answer, $error)) {
			if ($answer['moderatable'] || $answer['reshowimmed']) {
				$status = ILYA_POST_STATUS_NORMAL;

			} else {
				$in = ilya_page_q_prepare_post_for_filters($answer);
				$filtermodules = ilya_load_modules_with('filter', 'filter_answer'); // run through filters but only for queued status

				foreach ($filtermodules as $filtermodule) {
					$tempin = $in; // always pass original answer in because we aren't modifying anything else
					$filtermodule->filter_answer($tempin, $temperrors, $question, $answer);
					$in['queued'] = $tempin['queued']; // only preserve queued status in loop
				}

				$status = $in['queued'] ? ILYA_POST_STATUS_QUEUED : ILYA_POST_STATUS_NORMAL;
			}

			ilya_answer_set_status($answer, $status, $userid, $handle, $cookieid, $question, $commentsfollows);
			return true;
		}
	}

	if (ilya_clicked($prefix . 'dodelete') && $answer['deleteable'] && ilya_page_q_click_check_form_code($answer, $error)) {
		ilya_answer_delete($answer, $question, $userid, $handle, $cookieid);
		return true;
	}

	if (ilya_clicked($prefix . 'doclaim') && $answer['claimable'] && ilya_page_q_click_check_form_code($answer, $error)) {
		if (ilya_user_limits_remaining(ILYA_LIMIT_ANSWERS)) { // already checked 'permit_post_a'
			ilya_answer_set_userid($answer, $userid, $handle, $cookieid);
			return true;

		} else
			$error = ilya_lang_html('question/answer_limit');
	}

	if (ilya_clicked($prefix . 'doflag') && $answer['flagbutton'] && ilya_page_q_click_check_form_code($answer, $error)) {
		require_once ILYA_INCLUDE_DIR . 'app/votes.php';

		$error = ilya_flag_error_html($answer, $userid, ilya_request());
		if (!$error) {
			if (ilya_flag_set_tohide($answer, $userid, $handle, $cookieid, $question))
				ilya_answer_set_status($answer, ILYA_POST_STATUS_HIDDEN, null, null, null, $question, $commentsfollows); // hiding not really by this user so pass nulls

			return true;
		}
	}

	if (ilya_clicked($prefix . 'dounflag') && $answer['unflaggable'] && ilya_page_q_click_check_form_code($answer, $error)) {
		require_once ILYA_INCLUDE_DIR . 'app/votes.php';

		ilya_flag_clear($answer, $userid, $handle, $cookieid);
		return true;
	}

	if (ilya_clicked($prefix . 'doclearflags') && $answer['clearflaggable'] && ilya_page_q_click_check_form_code($answer, $error)) {
		require_once ILYA_INCLUDE_DIR . 'app/votes.php';

		ilya_flags_clear_all($answer, $userid, $handle, $cookieid);
		return true;
	}

	return false;
}


/**
 * Checks for a POSTed click on $comment by the current user and returns true if it was permitted and processed. Pass
 * in the antecedent $question and the comment's $parent post. If there is an error to display, it will be passed out
 * in $error.
 * @param $comment
 * @param $question
 * @param $parent
 * @param $error
 * @return bool
 */
function ilya_page_q_single_click_c($comment, $question, $parent, &$error)
{
	$userid = ilya_get_logged_in_userid();
	$handle = ilya_get_logged_in_handle();
	$cookieid = ilya_cookie_get();

	$prefix = 'c' . $comment['postid'] . '_';

	if ((ilya_clicked($prefix . 'dohide') && $comment['hideable']) || (ilya_clicked($prefix . 'doreject') && $comment['moderatable'])) {
		if (ilya_page_q_click_check_form_code($parent, $error)) {
			ilya_comment_set_status($comment, ILYA_POST_STATUS_HIDDEN, $userid, $handle, $cookieid, $question, $parent);
			return true;
		}
	}

	if ((ilya_clicked($prefix . 'doreshow') && $comment['reshowable']) || (ilya_clicked($prefix . 'doapprove') && $comment['moderatable'])) {
		if (ilya_page_q_click_check_form_code($parent, $error)) {
			if ($comment['moderatable'] || $comment['reshowimmed']) {
				$status = ILYA_POST_STATUS_NORMAL;

			} else {
				$in = ilya_page_q_prepare_post_for_filters($comment);
				$filtermodules = ilya_load_modules_with('filter', 'filter_comment'); // run through filters but only for queued status

				foreach ($filtermodules as $filtermodule) {
					$tempin = $in; // always pass original comment in because we aren't modifying anything else
					$filtermodule->filter_comment($tempin, $temperrors, $question, $parent, $comment);
					$in['queued'] = $tempin['queued']; // only preserve queued status in loop
				}

				$status = $in['queued'] ? ILYA_POST_STATUS_QUEUED : ILYA_POST_STATUS_NORMAL;
			}

			ilya_comment_set_status($comment, $status, $userid, $handle, $cookieid, $question, $parent);
			return true;
		}
	}

	if (ilya_clicked($prefix . 'dodelete') && $comment['deleteable'] && ilya_page_q_click_check_form_code($parent, $error)) {
		ilya_comment_delete($comment, $question, $parent, $userid, $handle, $cookieid);
		return true;
	}

	if (ilya_clicked($prefix . 'doclaim') && $comment['claimable'] && ilya_page_q_click_check_form_code($parent, $error)) {
		if (ilya_user_limits_remaining(ILYA_LIMIT_COMMENTS)) {
			ilya_comment_set_userid($comment, $userid, $handle, $cookieid);
			return true;

		} else
			$error = ilya_lang_html('question/comment_limit');
	}

	if (ilya_clicked($prefix . 'doflag') && $comment['flagbutton'] && ilya_page_q_click_check_form_code($parent, $error)) {
		require_once ILYA_INCLUDE_DIR . 'app/votes.php';

		$error = ilya_flag_error_html($comment, $userid, ilya_request());
		if (!$error) {
			if (ilya_flag_set_tohide($comment, $userid, $handle, $cookieid, $question))
				ilya_comment_set_status($comment, ILYA_POST_STATUS_HIDDEN, null, null, null, $question, $parent); // hiding not really by this user so pass nulls

			return true;
		}
	}

	if (ilya_clicked($prefix . 'dounflag') && $comment['unflaggable'] && ilya_page_q_click_check_form_code($parent, $error)) {
		require_once ILYA_INCLUDE_DIR . 'app/votes.php';

		ilya_flag_clear($comment, $userid, $handle, $cookieid);
		return true;
	}

	if (ilya_clicked($prefix . 'doclearflags') && $comment['clearflaggable'] && ilya_page_q_click_check_form_code($parent, $error)) {
		require_once ILYA_INCLUDE_DIR . 'app/votes.php';

		ilya_flags_clear_all($comment, $userid, $handle, $cookieid);
		return true;
	}

	return false;
}


/**
 * Check the form security (anti-CSRF protection) for one of the buttons shown for post $post. Return true if the
 * security passed, otherwise return false and set an error message in $error
 * @param $post
 * @param $error
 * @return bool
 */
function ilya_page_q_click_check_form_code($post, &$error)
{
	$result = ilya_check_form_security_code('buttons-' . $post['postid'], ilya_post_text('code'));

	if (!$result)
		$error = ilya_lang_html('misc/form_security_again');

	return $result;
}


/**
 * Processes a POSTed form to add an answer to $question, returning the postid if successful, otherwise null. Pass in
 * other $answers to the question and whether a $usecaptcha is required. The form fields submitted will be passed out
 * as an array in $in, as well as any $errors on those fields.
 * @param $question
 * @param $answers
 * @param $usecaptcha
 * @param $in
 * @param $errors
 * @return mixed|null
 */
function ilya_page_q_add_a_submit($question, $answers, $usecaptcha, &$in, &$errors)
{
	$in = array(
		'name' => ilya_opt('allow_anonymous_naming') ? ilya_post_text('a_name') : null,
		'notify' => ilya_post_text('a_notify') !== null,
		'email' => ilya_post_text('a_email'),
		'queued' => ilya_user_moderation_reason(ilya_user_level_for_post($question)) !== false,
	);

	ilya_get_post_content('a_editor', 'a_content', $in['editor'], $in['content'], $in['format'], $in['text']);

	$errors = array();

	if (!ilya_check_form_security_code('answer-' . $question['postid'], ilya_post_text('code')))
		$errors['content'] = ilya_lang_html('misc/form_security_again');

	else {
		// call any filter plugins
		$filtermodules = ilya_load_modules_with('filter', 'filter_answer');
		foreach ($filtermodules as $filtermodule) {
			$oldin = $in;
			$filtermodule->filter_answer($in, $errors, $question, null);
			ilya_update_post_text($in, $oldin);
		}

		// check CAPTCHA
		if ($usecaptcha)
			ilya_captcha_validate_post($errors);

		// check for duplicate posts
		if (empty($errors)) {
			$testwords = implode(' ', ilya_string_to_words($in['content']));

			foreach ($answers as $answer) {
				if (!$answer['hidden']) {
					if (implode(' ', ilya_string_to_words($answer['content'])) == $testwords) {
						$errors['content'] = ilya_lang_html('question/duplicate_content');
						break;
					}
				}
			}
		}

		$userid = ilya_get_logged_in_userid();

		// if this is an additional answer, check we can add it
		if (empty($errors) && !ilya_opt('allow_multi_answers')) {
			foreach ($answers as $answer) {
				if (ilya_post_is_by_user($answer, $userid, ilya_cookie_get())) {
					$errors[] = '';
					break;
				}
			}
		}

		// create the answer
		if (empty($errors)) {
			$handle = ilya_get_logged_in_handle();
			$cookieid = isset($userid) ? ilya_cookie_get() : ilya_cookie_get_create(); // create a new cookie if necessary

			$answerid = ilya_answer_create($userid, $handle, $cookieid, $in['content'], $in['format'], $in['text'], $in['notify'], $in['email'],
				$question, $in['queued'], $in['name']);

			return $answerid;
		}
	}

	return null;
}


/**
 * Processes a POSTed form to add a comment, returning the postid if successful, otherwise null. Pass in the antecedent
 * $question and the comment's $parent post. Set $usecaptcha to whether a captcha is required. Pass an array which
 * includes the other comments with the same parent in $commentsfollows (it can contain other posts which are ignored).
 * The form fields submitted will be passed out as an array in $in, as well as any $errors on those fields.
 * @param $question
 * @param $parent
 * @param $commentsfollows
 * @param $usecaptcha
 * @param $in
 * @param $errors
 * @return mixed|null
 */
function ilya_page_q_add_c_submit($question, $parent, $commentsfollows, $usecaptcha, &$in, &$errors)
{
	$parentid = $parent['postid'];

	$prefix = 'c' . $parentid . '_';

	$in = array(
		'name' => ilya_opt('allow_anonymous_naming') ? ilya_post_text($prefix . 'name') : null,
		'notify' => ilya_post_text($prefix . 'notify') !== null,
		'email' => ilya_post_text($prefix . 'email'),
		'queued' => ilya_user_moderation_reason(ilya_user_level_for_post($parent)) !== false,
	);

	ilya_get_post_content($prefix . 'editor', $prefix . 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

	$errors = array();

	if (!ilya_check_form_security_code('comment-' . $parent['postid'], ilya_post_text($prefix . 'code')))
		$errors['content'] = ilya_lang_html('misc/form_security_again');

	else {
		$filtermodules = ilya_load_modules_with('filter', 'filter_comment');
		foreach ($filtermodules as $filtermodule) {
			$oldin = $in;
			$filtermodule->filter_comment($in, $errors, $question, $parent, null);
			ilya_update_post_text($in, $oldin);
		}

		if ($usecaptcha)
			ilya_captcha_validate_post($errors);

		if (empty($errors)) {
			$testwords = implode(' ', ilya_string_to_words($in['content']));

			foreach ($commentsfollows as $comment) {
				if ($comment['basetype'] == 'C' && $comment['parentid'] == $parentid && !$comment['hidden']) {
					if (implode(' ', ilya_string_to_words($comment['content'])) == $testwords) {
						$errors['content'] = ilya_lang_html('question/duplicate_content');
						break;
					}
				}
			}
		}

		if (empty($errors)) {
			$userid = ilya_get_logged_in_userid();
			$handle = ilya_get_logged_in_handle();
			$cookieid = isset($userid) ? ilya_cookie_get() : ilya_cookie_get_create(); // create a new cookie if necessary

			$commentid = ilya_comment_create($userid, $handle, $cookieid, $in['content'], $in['format'], $in['text'], $in['notify'], $in['email'],
				$question, $parent, $commentsfollows, $in['queued'], $in['name']);

			return $commentid;
		}
	}

	return null;
}


/**
 * Return the array of information to be passed to filter modules for the post in $post (from the database)
 * @param $post
 * @return array
 */
function ilya_page_q_prepare_post_for_filters($post)
{
	$in = array(
		'content' => $post['content'],
		'format' => $post['format'],
		'text' => ilya_viewer_text($post['content'], $post['format']),
		'notify' => isset($post['notify']),
		'email' => ilya_email_validate($post['notify']) ? $post['notify'] : null,
		'queued' => ilya_user_moderation_reason(ilya_user_level_for_post($post)) !== false,
	);

	if ($post['basetype'] == 'Q') {
		$in['title'] = $post['title'];
		$in['tags'] = ilya_tagstring_to_tags($post['tags']);
		$in['categoryid'] = $post['categoryid'];
		$in['extra'] = $post['extra'];
	}

	return $in;
}
