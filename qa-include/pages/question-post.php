<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: More control for question page if it's submitted by HTTP POST


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

require_once QA_INCLUDE_DIR . 'app/limits.php';
require_once QA_INCLUDE_DIR . 'pages/question-submit.php';


$code = ilya_post_text('code');


// Process general cancel button

if (ilya_clicked('docancel'))
	ilya_page_q_refresh($pagestart);


// Process incoming answer (or button)

if ($question['answerbutton']) {
	if (ilya_clicked('q_doanswer'))
		ilya_page_q_refresh($pagestart, 'answer');

	// The 'approve', 'login', 'confirm', 'limit', 'userblock', 'ipblock' permission errors are reported to the user here
	// The other option ('level') prevents the answer button being shown, in ilya_page_q_post_rules(...)

	if (ilya_clicked('a_doadd') || $pagestate == 'answer') {
		switch (ilya_user_post_permit_error('permit_post_a', $question, QA_LIMIT_ANSWERS)) {
			case 'login':
				$pageerror = ilya_insert_login_links(ilya_lang_html('question/answer_must_login'), ilya_request());
				break;

			case 'confirm':
				$pageerror = ilya_insert_login_links(ilya_lang_html('question/answer_must_confirm'), ilya_request());
				break;

			case 'approve':
				$pageerror = strtr(ilya_lang_html('question/answer_must_be_approved'), array(
					'^1' => '<a href="' . ilya_path_html('account') . '">',
					'^2' => '</a>',
				));
				break;

			case 'limit':
				$pageerror = ilya_lang_html('question/answer_limit');
				break;

			default:
				$pageerror = ilya_lang_html('users/no_permission');
				break;

			case false:
				if (ilya_clicked('a_doadd')) {
					$answerid = ilya_page_q_add_a_submit($question, $answers, $usecaptcha, $anewin, $anewerrors);

					if (isset($answerid))
						ilya_page_q_refresh(0, null, 'A', $answerid);
					else
						$formtype = 'a_add'; // show form again

				} else
					$formtype = 'a_add'; // show form as if first time
				break;
		}
	}
}


// Process close buttons for question

if ($question['closeable']) {
	if (ilya_clicked('q_doclose'))
		ilya_page_q_refresh($pagestart, 'close');

	elseif (ilya_clicked('doclose') && ilya_page_q_permit_edit($question, 'permit_close_q', $pageerror)) {
		if (ilya_page_q_close_q_submit($question, $closepost, $closein, $closeerrors))
			ilya_page_q_refresh($pagestart);
		else
			$formtype = 'q_close'; // keep editing if an error

	} elseif ($pagestate == 'close' && ilya_page_q_permit_edit($question, 'permit_close_q', $pageerror))
		$formtype = 'q_close';
}


// Process any single click operations or delete button for question

if (ilya_page_q_single_click_q($question, $answers, $commentsfollows, $closepost, $pageerror))
	ilya_page_q_refresh($pagestart);

if (ilya_clicked('q_dodelete') && $question['deleteable'] && ilya_page_q_click_check_form_code($question, $pageerror)) {
	ilya_question_delete($question, $userid, ilya_get_logged_in_handle(), $cookieid, $closepost);
	ilya_redirect(''); // redirect since question has gone
}


// Process edit or save button for question

if ($question['editbutton'] || $question['retagcatbutton']) {
	if (ilya_clicked('q_doedit'))
		ilya_page_q_refresh($pagestart, 'edit-' . $questionid);

	elseif (ilya_clicked('q_dosave') && ilya_page_q_permit_edit($question, 'permit_edit_q', $pageerror, 'permit_retag_cat')) {
		if (ilya_page_q_edit_q_submit($question, $answers, $commentsfollows, $closepost, $qin, $qerrors))
			ilya_redirect(ilya_q_request($questionid, $qin['title'])); // don't use refresh since URL may have changed
		else {
			$formtype = 'q_edit'; // keep editing if an error
			$pageerror = @$qerrors['page']; // for security code failure
		}

	} elseif ($pagestate == ('edit-' . $questionid) && ilya_page_q_permit_edit($question, 'permit_edit_q', $pageerror, 'permit_retag_cat'))
		$formtype = 'q_edit';

	if ($formtype == 'q_edit') { // get tags for auto-completion
		if (ilya_opt('do_complete_tags'))
			$completetags = array_keys(ilya_db_select_with_pending(ilya_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS)));
		else
			$completetags = array();
	}
}


// Process adding a comment to question (shows form or processes it)

if ($question['commentbutton']) {
	if (ilya_clicked('q_docomment'))
		ilya_page_q_refresh($pagestart, 'comment-' . $questionid, 'C', $questionid);

	if (ilya_clicked('c' . $questionid . '_doadd') || $pagestate == ('comment-' . $questionid))
		ilya_page_q_do_comment($question, $question, $commentsfollows, $pagestart, $usecaptcha, $cnewin, $cnewerrors, $formtype, $formpostid, $pageerror);
}


// Process clicked buttons for answers

foreach ($answers as $answerid => $answer) {
	$prefix = 'a' . $answerid . '_';

	if (ilya_page_q_single_click_a($answer, $question, $answers, $commentsfollows, true, $pageerror))
		ilya_page_q_refresh($pagestart, null, 'A', $answerid);

	if ($answer['editbutton']) {
		if (ilya_clicked($prefix . 'doedit'))
			ilya_page_q_refresh($pagestart, 'edit-' . $answerid);

		elseif (ilya_clicked($prefix . 'dosave') && ilya_page_q_permit_edit($answer, 'permit_edit_a', $pageerror)) {
			$editedtype = ilya_page_q_edit_a_submit($answer, $question, $answers, $commentsfollows, $aeditin[$answerid], $aediterrors[$answerid]);

			if (isset($editedtype))
				ilya_page_q_refresh($pagestart, null, $editedtype, $answerid);

			else {
				$formtype = 'a_edit';
				$formpostid = $answerid; // keep editing if an error
			}

		} elseif ($pagestate == ('edit-' . $answerid) && ilya_page_q_permit_edit($answer, 'permit_edit_a', $pageerror)) {
			$formtype = 'a_edit';
			$formpostid = $answerid;
		}
	}

	if ($answer['commentbutton']) {
		if (ilya_clicked($prefix . 'docomment'))
			ilya_page_q_refresh($pagestart, 'comment-' . $answerid, 'C', $answerid);

		if (ilya_clicked('c' . $answerid . '_doadd') || $pagestate == ('comment-' . $answerid))
			ilya_page_q_do_comment($question, $answer, $commentsfollows, $pagestart, $usecaptcha, $cnewin, $cnewerrors, $formtype, $formpostid, $pageerror);
	}

	if (ilya_clicked($prefix . 'dofollow')) {
		$params = array('follow' => $answerid);
		if (isset($question['categoryid']))
			$params['cat'] = $question['categoryid'];

		ilya_redirect('ask', $params);
	}
}


// Process hide, show, delete, flag, unflag, edit or save button for comments

foreach ($commentsfollows as $commentid => $comment) {
	if ($comment['basetype'] == 'C') {
		$cparentid = $comment['parentid'];
		$commentparent = isset($answers[$cparentid]) ? $answers[$cparentid] : $question;
		$prefix = 'c' . $commentid . '_';

		if (ilya_page_q_single_click_c($comment, $question, $commentparent, $pageerror))
			ilya_page_q_refresh($pagestart, 'showcomments-' . $cparentid, $commentparent['basetype'], $cparentid);

		if ($comment['editbutton']) {
			if (ilya_clicked($prefix . 'doedit')) {
				if (ilya_page_q_permit_edit($comment, 'permit_edit_c', $pageerror)) // extra check here ensures error message is visible
					ilya_page_q_refresh($pagestart, 'edit-' . $commentid, 'C', $commentid);
			} elseif (ilya_clicked($prefix . 'dosave') && ilya_page_q_permit_edit($comment, 'permit_edit_c', $pageerror)) {
				if (ilya_page_q_edit_c_submit($comment, $question, $commentparent, $ceditin[$commentid], $cediterrors[$commentid]))
					ilya_page_q_refresh($pagestart, null, 'C', $commentid);
				else {
					$formtype = 'c_edit';
					$formpostid = $commentid; // keep editing if an error
				}
			} elseif ($pagestate == ('edit-' . $commentid) && ilya_page_q_permit_edit($comment, 'permit_edit_c', $pageerror)) {
				$formtype = 'c_edit';
				$formpostid = $commentid;
			}
		}
	}
}


// Functions used above - also see functions in /ilya-include/pages/question-submit.php (which are shared with Ajax)

/*
	Redirects back to the question page, with the specified parameters
*/
function ilya_page_q_refresh($start = 0, $state = null, $showtype = null, $showid = null)
{
	$params = array();

	if ($start > 0)
		$params['start'] = $start;
	if (isset($state))
		$params['state'] = $state;

	if (isset($showtype) && isset($showid)) {
		$anchor = ilya_anchor($showtype, $showid);
		$params['show'] = $showid;
	} else
		$anchor = null;

	ilya_redirect(ilya_request(), $params, null, null, $anchor);
}


/*
	Returns whether the editing operation (as specified by $permitoption or $permitoption2) on $post is permitted.
	If not, sets the $error variable appropriately
*/
function ilya_page_q_permit_edit($post, $permitoption, &$error, $permitoption2 = null)
{
	// The 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
	// The other options ('approve', 'level') prevent the edit button being shown, in ilya_page_q_post_rules(...)

	$permiterror = ilya_user_post_permit_error($post['isbyuser'] ? null : $permitoption, $post);
	// if it's by the user, this will only check whether they are blocked

	if ($permiterror && isset($permitoption2)) {
		$permiterror2 = ilya_user_post_permit_error($post['isbyuser'] ? null : $permitoption2, $post);

		if ($permiterror == 'level' || $permiterror == 'approve' || !$permiterror2) // if it's a less strict error
			$permiterror = $permiterror2;
	}

	switch ($permiterror) {
		case 'login':
			$error = ilya_insert_login_links(ilya_lang_html('question/edit_must_login'), ilya_request());
			break;

		case 'confirm':
			$error = ilya_insert_login_links(ilya_lang_html('question/edit_must_confirm'), ilya_request());
			break;

		default:
			$error = ilya_lang_html('users/no_permission');
			break;

		case false:
			break;
	}

	return !$permiterror;
}


/*
	Returns a $ilya_content form for editing the question and sets up other parts of $ilya_content accordingly
*/
function ilya_page_q_edit_q_form(&$ilya_content, $question, $in, $errors, $completetags, $categories)
{
	$form = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'style' => 'tall',

		'fields' => array(
			'title' => array(
				'type' => $question['editable'] ? 'text' : 'static',
				'label' => ilya_lang_html('question/q_title_label'),
				'tags' => 'name="q_title"',
				'value' => ilya_html(($question['editable'] && isset($in['title'])) ? $in['title'] : $question['title']),
				'error' => ilya_html(@$errors['title']),
			),

			'category' => array(
				'label' => ilya_lang_html('question/q_category_label'),
				'error' => ilya_html(@$errors['categoryid']),
			),

			'content' => array(
				'label' => ilya_lang_html('question/q_content_label'),
				'error' => ilya_html(@$errors['content']),
			),

			'extra' => array(
				'label' => ilya_html(ilya_opt('extra_field_prompt')),
				'tags' => 'name="q_extra"',
				'value' => ilya_html(isset($in['extra']) ? $in['extra'] : $question['extra']),
				'error' => ilya_html(@$errors['extra']),
			),

			'tags' => array(
				'error' => ilya_html(@$errors['tags']),
			),

		),

		'buttons' => array(
			'save' => array(
				'tags' => 'onclick="ilya_show_waiting_after(this, false);"',
				'label' => ilya_lang_html('main/save_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => ilya_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			'q_dosave' => '1',
			'code' => ilya_get_form_security_code('edit-' . $question['postid']),
		),
	);

	if ($question['editable']) {
		$content = isset($in['content']) ? $in['content'] : $question['content'];
		$format = isset($in['format']) ? $in['format'] : $question['format'];

		$editorname = isset($in['editor']) ? $in['editor'] : ilya_opt('editor_for_qs');
		$editor = ilya_load_editor($content, $format, $editorname);

		$form['fields']['content'] = array_merge($form['fields']['content'],
			ilya_editor_load_field($editor, $ilya_content, $content, $format, 'q_content', 12, true));

		if (method_exists($editor, 'update_script'))
			$form['buttons']['save']['tags'] = 'onclick="ilya_show_waiting_after(this, false); ' . $editor->update_script('q_content') . '"';

		$form['hidden']['q_editor'] = ilya_html($editorname);

	} else
		unset($form['fields']['content']);

	if (ilya_using_categories() && count($categories) && $question['retagcatable']) {
		ilya_set_up_category_field($ilya_content, $form['fields']['category'], 'q_category', $categories,
			isset($in['categoryid']) ? $in['categoryid'] : $question['categoryid'],
			ilya_opt('allow_no_category') || !isset($question['categoryid']), ilya_opt('allow_no_sub_category'));
	} else {
		unset($form['fields']['category']);
	}

	if (!($question['editable'] && ilya_opt('extra_field_active')))
		unset($form['fields']['extra']);

	if (ilya_using_tags() && $question['retagcatable']) {
		ilya_set_up_tag_field($ilya_content, $form['fields']['tags'], 'q_tags', isset($in['tags']) ? $in['tags'] : ilya_tagstring_to_tags($question['tags']),
			array(), $completetags, ilya_opt('page_size_ask_tags'));
	} else {
		unset($form['fields']['tags']);
	}

	if ($question['isbyuser']) {
		if (!ilya_is_logged_in() && ilya_opt('allow_anonymous_naming'))
			ilya_set_up_name_field($ilya_content, $form['fields'], isset($in['name']) ? $in['name'] : @$question['name'], 'q_');

		ilya_set_up_notify_fields($ilya_content, $form['fields'], 'Q', ilya_get_logged_in_email(),
			isset($in['notify']) ? $in['notify'] : !empty($question['notify']),
			isset($in['email']) ? $in['email'] : @$question['notify'], @$errors['email'], 'q_');
	}

	if (!ilya_user_post_permit_error('permit_edit_silent', $question)) {
		$form['fields']['silent'] = array(
			'type' => 'checkbox',
			'label' => ilya_lang_html('question/save_silent_label'),
			'tags' => 'name="q_silent"',
			'value' => ilya_html(@$in['silent']),
		);
	}

	return $form;
}


/*
	Processes a POSTed form for editing the question and returns true if successful
*/
function ilya_page_q_edit_q_submit($question, $answers, $commentsfollows, $closepost, &$in, &$errors)
{
	$in = array();

	if ($question['editable']) {
		$in['title'] = ilya_get_post_title('q_title');
		ilya_get_post_content('q_editor', 'q_content', $in['editor'], $in['content'], $in['format'], $in['text']);
		$in['extra'] = ilya_opt('extra_field_active') ? ilya_post_text('q_extra') : null;
	}

	if ($question['retagcatable']) {
		if (ilya_using_tags())
			$in['tags'] = ilya_get_tags_field_value('q_tags');

		if (ilya_using_categories())
			$in['categoryid'] = ilya_get_category_field_value('q_category');
	}

	if (array_key_exists('categoryid', $in)) { // need to check if we can move it to that category, and if we need moderation
		$categories = ilya_db_select_with_pending(ilya_db_category_nav_selectspec($in['categoryid'], true));
		$categoryids = array_keys(ilya_category_path($categories, $in['categoryid']));
		$userlevel = ilya_user_level_for_categories($categoryids);

	} else
		$userlevel = null;

	if ($question['isbyuser']) {
		$in['name'] = ilya_opt('allow_anonymous_naming') ? ilya_post_text('q_name') : null;
		$in['notify'] = ilya_post_text('q_notify') !== null;
		$in['email'] = ilya_post_text('q_email');
	}

	if (!ilya_user_post_permit_error('permit_edit_silent', $question))
		$in['silent'] = ilya_post_text('q_silent');

	// here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters

	$errors = array();

	if (!ilya_check_form_security_code('edit-' . $question['postid'], ilya_post_text('code')))
		$errors['page'] = ilya_lang_html('misc/form_security_again');

	else {
		$in['queued'] = ilya_opt('moderate_edited_again') && ilya_user_moderation_reason($userlevel);

		$filtermodules = ilya_load_modules_with('filter', 'filter_question');
		foreach ($filtermodules as $filtermodule) {
			$oldin = $in;
			$filtermodule->filter_question($in, $errors, $question);

			if ($question['editable'])
				ilya_update_post_text($in, $oldin);
		}

		if (array_key_exists('categoryid', $in) && strcmp($in['categoryid'], $question['categoryid'])) {
			if (ilya_user_permit_error('permit_post_q', null, $userlevel))
				$errors['categoryid'] = ilya_lang_html('question/category_ask_not_allowed');
		}

		if (empty($errors)) {
			$userid = ilya_get_logged_in_userid();
			$handle = ilya_get_logged_in_handle();
			$cookieid = ilya_cookie_get();

			// now we fill in the missing values in the $in array, so that we have everything we need for ilya_question_set_content()
			// we do things in this way to avoid any risk of a validation failure on elements the user can't see (e.g. due to admin setting changes)

			if (!$question['editable']) {
				$in['title'] = $question['title'];
				$in['content'] = $question['content'];
				$in['format'] = $question['format'];
				$in['text'] = ilya_viewer_text($in['content'], $in['format']);
				$in['extra'] = $question['extra'];
			}

			if (!isset($in['tags']))
				$in['tags'] = ilya_tagstring_to_tags($question['tags']);

			if (!array_key_exists('categoryid', $in))
				$in['categoryid'] = $question['categoryid'];

			if (!isset($in['silent']))
				$in['silent'] = false;

			$setnotify = $question['isbyuser'] ? ilya_combine_notify_email($question['userid'], $in['notify'], $in['email']) : $question['notify'];

			ilya_question_set_content($question, $in['title'], $in['content'], $in['format'], $in['text'], ilya_tags_to_tagstring($in['tags']),
				$setnotify, $userid, $handle, $cookieid, $in['extra'], @$in['name'], $in['queued'], $in['silent']);

			if (ilya_using_categories() && strcmp($in['categoryid'], $question['categoryid'])) {
				ilya_question_set_category($question, $in['categoryid'], $userid, $handle, $cookieid,
					$answers, $commentsfollows, $closepost, $in['silent']);
			}

			return true;
		}
	}

	return false;
}


/*
	Returns a $ilya_content form for closing the question and sets up other parts of $ilya_content accordingly
*/
function ilya_page_q_close_q_form(&$ilya_content, $question, $id, $in, $errors)
{
	$form = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'id' => $id,

		'style' => 'tall',

		'title' => ilya_lang_html('question/close_form_title'),

		'fields' => array(
			'details' => array(
				'tags' => 'name="q_close_details" id="q_close_details"',
				'label' =>
					'<span id="close_label_other">' . ilya_lang_html('question/close_reason_title') . '</span>',
				'value' => @$in['details'],
				'error' => ilya_html(@$errors['details']),
			),
		),

		'buttons' => array(
			'close' => array(
				'tags' => 'onclick="ilya_show_waiting_after(this, false);"',
				'label' => ilya_lang_html('question/close_form_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => ilya_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			'doclose' => '1',
			'code' => ilya_get_form_security_code('close-' . $question['postid']),
		),
	);

	$ilya_content['focusid'] = 'q_close_details';

	return $form;
}


/*
	Processes a POSTed form for closing the question and returns true if successful
*/
function ilya_page_q_close_q_submit($question, $closepost, &$in, &$errors)
{
	$in = array(
		'details' => trim(ilya_post_text('q_close_details')),
	);

	$userid = ilya_get_logged_in_userid();
	$handle = ilya_get_logged_in_handle();
	$cookieid = ilya_cookie_get();

	$sanitizedUrl = filter_var($in['details'], FILTER_SANITIZE_URL);
	$isduplicateurl = filter_var($sanitizedUrl, FILTER_VALIDATE_URL);

	if (!ilya_check_form_security_code('close-' . $question['postid'], ilya_post_text('code'))) {
		$errors['details'] = ilya_lang_html('misc/form_security_again');
	} elseif ($isduplicateurl) {
		// be liberal in what we accept, but there are two potential unlikely pitfalls here:
		// a) URLs could have a fixed numerical path, e.g. http://qa.mysite.com/1/478/...
		// b) There could be a question title which is just a number, e.g. http://qa.mysite.com/478/12345/...
		// so we check if more than one question could match, and if so, show an error

		$parts = preg_split('|[=/&]|', $sanitizedUrl, -1, PREG_SPLIT_NO_EMPTY);
		$keypostids = array();

		foreach ($parts as $part) {
			if (preg_match('/^[0-9]+$/', $part))
				$keypostids[$part] = true;
		}

		$questionids = ilya_db_posts_filter_q_postids(array_keys($keypostids));

		if (count($questionids) == 1 && $questionids[0] != $question['postid']) {
			ilya_question_close_duplicate($question, $closepost, $questionids[0], $userid, $handle, $cookieid);
			return true;

		} else
			$errors['details'] = ilya_lang('question/close_duplicate_error');

	} else {
		if (strlen($in['details']) > 0) {
			ilya_question_close_other($question, $closepost, $in['details'], $userid, $handle, $cookieid);
			return true;

		} else
			$errors['details'] = ilya_lang('main/field_required');
	}

	return false;
}


/*
	Returns a $ilya_content form for editing an answer and sets up other parts of $ilya_content accordingly
*/
function ilya_page_q_edit_a_form(&$ilya_content, $id, $answer, $question, $answers, $commentsfollows, $in, $errors)
{
	require_once QA_INCLUDE_DIR . 'util/string.php';

	$answerid = $answer['postid'];
	$prefix = 'a' . $answerid . '_';

	$content = isset($in['content']) ? $in['content'] : $answer['content'];
	$format = isset($in['format']) ? $in['format'] : $answer['format'];

	$editorname = isset($in['editor']) ? $in['editor'] : ilya_opt('editor_for_as');
	$editor = ilya_load_editor($content, $format, $editorname);

	$hascomments = false;
	foreach ($commentsfollows as $commentfollow) {
		if ($commentfollow['parentid'] == $answerid)
			$hascomments = true;
	}

	$form = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'id' => $id,

		'title' => ilya_lang_html('question/edit_a_title'),

		'style' => 'tall',

		'fields' => array(
			'content' => array_merge(
				ilya_editor_load_field($editor, $ilya_content, $content, $format, $prefix . 'content', 12),
				array(
					'error' => ilya_html(@$errors['content']),
				)
			),
		),

		'buttons' => array(
			'save' => array(
				'tags' => 'onclick="ilya_show_waiting_after(this, false); ' .
					(method_exists($editor, 'update_script') ? $editor->update_script($prefix . 'content') : '') . '"',
				'label' => ilya_lang_html('main/save_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => ilya_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			$prefix . 'editor' => ilya_html($editorname),
			$prefix . 'dosave' => '1',
			$prefix . 'code' => ilya_get_form_security_code('edit-' . $answerid),
		),
	);

	// Show option to convert this answer to a comment, if appropriate

	$commentonoptions = array();

	$lastbeforeid = $question['postid']; // used to find last post created before this answer - this is default given
	$lastbeforetime = $question['created'];

	if ($question['commentable']) {
		$commentonoptions[$question['postid']] =
			ilya_lang_html('question/comment_on_q') . ilya_html(ilya_shorten_string_line($question['title'], 80));
	}

	foreach ($answers as $otheranswer) {
		if ($otheranswer['postid'] != $answerid && $otheranswer['created'] < $answer['created'] && $otheranswer['commentable'] && !$otheranswer['hidden']) {
			$commentonoptions[$otheranswer['postid']] =
				ilya_lang_html('question/comment_on_a') . ilya_html(ilya_shorten_string_line(ilya_viewer_text($otheranswer['content'], $otheranswer['format']), 80));

			if ($otheranswer['created'] > $lastbeforetime) {
				$lastbeforeid = $otheranswer['postid'];
				$lastbeforetime = $otheranswer['created'];
			}
		}
	}

	if (count($commentonoptions)) {
		$form['fields']['tocomment'] = array(
			'tags' => 'name="' . $prefix . 'dotoc" id="' . $prefix . 'dotoc"',
			'label' => '<span id="' . $prefix . 'toshown">' . ilya_lang_html('question/a_convert_to_c_on') . '</span>' .
				'<span id="' . $prefix . 'tohidden" style="display:none;">' . ilya_lang_html('question/a_convert_to_c') . '</span>',
			'type' => 'checkbox',
			'tight' => true,
		);

		$form['fields']['commenton'] = array(
			'tags' => 'name="' . $prefix . 'commenton"',
			'id' => $prefix . 'commenton',
			'type' => 'select',
			'note' => ilya_lang_html($hascomments ? 'question/a_convert_warn_cs' : 'question/a_convert_warn'),
			'options' => $commentonoptions,
			'value' => @$commentonoptions[$lastbeforeid],
		);

		ilya_set_display_rules($ilya_content, array(
			$prefix . 'commenton' => $prefix . 'dotoc',
			$prefix . 'toshown' => $prefix . 'dotoc',
			$prefix . 'tohidden' => '!' . $prefix . 'dotoc',
		));
	}

	// Show name and notification field if appropriate

	if ($answer['isbyuser']) {
		if (!ilya_is_logged_in() && ilya_opt('allow_anonymous_naming'))
			ilya_set_up_name_field($ilya_content, $form['fields'], isset($in['name']) ? $in['name'] : @$answer['name'], $prefix);

		ilya_set_up_notify_fields($ilya_content, $form['fields'], 'A', ilya_get_logged_in_email(),
			isset($in['notify']) ? $in['notify'] : !empty($answer['notify']),
			isset($in['email']) ? $in['email'] : @$answer['notify'], @$errors['email'], $prefix);
	}

	if (!ilya_user_post_permit_error('permit_edit_silent', $answer)) {
		$form['fields']['silent'] = array(
			'type' => 'checkbox',
			'label' => ilya_lang_html('question/save_silent_label'),
			'tags' => 'name="' . $prefix . 'silent"',
			'value' => ilya_html(@$in['silent']),
		);
	}

	return $form;
}


/*
	Processes a POSTed form for editing an answer and returns the new type of the post if successful
*/
function ilya_page_q_edit_a_submit($answer, $question, $answers, $commentsfollows, &$in, &$errors)
{
	$answerid = $answer['postid'];
	$prefix = 'a' . $answerid . '_';

	$in = array(
		'dotoc' => ilya_post_text($prefix . 'dotoc'),
		'commenton' => ilya_post_text($prefix . 'commenton'),
	);

	if ($answer['isbyuser']) {
		$in['name'] = ilya_opt('allow_anonymous_naming') ? ilya_post_text($prefix . 'name') : null;
		$in['notify'] = ilya_post_text($prefix . 'notify') !== null;
		$in['email'] = ilya_post_text($prefix . 'email');
	}

	if (!ilya_user_post_permit_error('permit_edit_silent', $answer))
		$in['silent'] = ilya_post_text($prefix . 'silent');

	ilya_get_post_content($prefix . 'editor', $prefix . 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

	// here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters

	$errors = array();

	if (!ilya_check_form_security_code('edit-' . $answerid, ilya_post_text($prefix . 'code')))
		$errors['content'] = ilya_lang_html('misc/form_security_again');

	else {
		$in['queued'] = ilya_opt('moderate_edited_again') && ilya_user_moderation_reason(ilya_user_level_for_post($answer));

		$filtermodules = ilya_load_modules_with('filter', 'filter_answer');
		foreach ($filtermodules as $filtermodule) {
			$oldin = $in;
			$filtermodule->filter_answer($in, $errors, $question, $answer);
			ilya_update_post_text($in, $oldin);
		}

		if (empty($errors)) {
			$userid = ilya_get_logged_in_userid();
			$handle = ilya_get_logged_in_handle();
			$cookieid = ilya_cookie_get();

			if (!isset($in['silent']))
				$in['silent'] = false;

			$setnotify = $answer['isbyuser'] ? ilya_combine_notify_email($answer['userid'], $in['notify'], $in['email']) : $answer['notify'];

			if ($in['dotoc'] && (
					(($in['commenton'] == $question['postid']) && $question['commentable']) ||
					(($in['commenton'] != $answerid) && @$answers[$in['commenton']]['commentable'])
				)
			) { // convert to a comment
				if (ilya_user_limits_remaining(QA_LIMIT_COMMENTS)) { // already checked 'permit_post_c'
					ilya_answer_to_comment($answer, $in['commenton'], $in['content'], $in['format'], $in['text'], $setnotify,
						$userid, $handle, $cookieid, $question, $answers, $commentsfollows, @$in['name'], $in['queued'], $in['silent']);

					return 'C'; // to signify that redirect should be to the comment

				} else
					$errors['content'] = ilya_lang_html('question/comment_limit'); // not really best place for error, but it will do

			} else {
				ilya_answer_set_content($answer, $in['content'], $in['format'], $in['text'], $setnotify,
					$userid, $handle, $cookieid, $question, @$in['name'], $in['queued'], $in['silent']);

				return 'A';
			}
		}
	}

	return null;
}


/*
	Processes a request to add a comment to $parent, with antecedent $question, checking for permissions errors
*/
function ilya_page_q_do_comment($question, $parent, $commentsfollows, $pagestart, $usecaptcha, &$cnewin, &$cnewerrors, &$formtype, &$formpostid, &$error)
{
	// The 'approve', 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
	// The other option ('level') prevents the comment button being shown, in ilya_page_q_post_rules(...)

	$parentid = $parent['postid'];

	switch (ilya_user_post_permit_error('permit_post_c', $parent, QA_LIMIT_COMMENTS)) {
		case 'login':
			$error = ilya_insert_login_links(ilya_lang_html('question/comment_must_login'), ilya_request());
			break;

		case 'confirm':
			$error = ilya_insert_login_links(ilya_lang_html('question/comment_must_confirm'), ilya_request());
			break;

		case 'approve':
			$error = strtr(ilya_lang_html('question/comment_must_be_approved'), array(
				'^1' => '<a href="' . ilya_path_html('account') . '">',
				'^2' => '</a>',
			));
			break;

		case 'limit':
			$error = ilya_lang_html('question/comment_limit');
			break;

		default:
			$error = ilya_lang_html('users/no_permission');
			break;

		case false:
			if (ilya_clicked('c' . $parentid . '_doadd')) {
				$commentid = ilya_page_q_add_c_submit($question, $parent, $commentsfollows, $usecaptcha, $cnewin[$parentid], $cnewerrors[$parentid]);

				if (isset($commentid))
					ilya_page_q_refresh($pagestart, null, 'C', $commentid);

				else {
					$formtype = 'c_add';
					$formpostid = $parentid; // show form again
				}

			} else {
				$formtype = 'c_add';
				$formpostid = $parentid; // show form first time
			}
			break;
	}
}


/*
	Returns a $ilya_content form for editing a comment and sets up other parts of $ilya_content accordingly
*/
function ilya_page_q_edit_c_form(&$ilya_content, $id, $comment, $in, $errors)
{
	$commentid = $comment['postid'];
	$prefix = 'c' . $commentid . '_';

	$content = isset($in['content']) ? $in['content'] : $comment['content'];
	$format = isset($in['format']) ? $in['format'] : $comment['format'];

	$editorname = isset($in['editor']) ? $in['editor'] : ilya_opt('editor_for_cs');
	$editor = ilya_load_editor($content, $format, $editorname);

	$form = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'id' => $id,

		'title' => ilya_lang_html('question/edit_c_title'),

		'style' => 'tall',

		'fields' => array(
			'content' => array_merge(
				ilya_editor_load_field($editor, $ilya_content, $content, $format, $prefix . 'content', 4, true),
				array(
					'error' => ilya_html(@$errors['content']),
				)
			),
		),

		'buttons' => array(
			'save' => array(
				'tags' => 'onclick="ilya_show_waiting_after(this, false); ' .
					(method_exists($editor, 'update_script') ? $editor->update_script($prefix . 'content') : '') . '"',
				'label' => ilya_lang_html('main/save_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => ilya_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			$prefix . 'editor' => ilya_html($editorname),
			$prefix . 'dosave' => '1',
			$prefix . 'code' => ilya_get_form_security_code('edit-' . $commentid),
		),
	);

	if ($comment['isbyuser']) {
		if (!ilya_is_logged_in() && ilya_opt('allow_anonymous_naming'))
			ilya_set_up_name_field($ilya_content, $form['fields'], isset($in['name']) ? $in['name'] : @$comment['name'], $prefix);

		ilya_set_up_notify_fields($ilya_content, $form['fields'], 'C', ilya_get_logged_in_email(),
			isset($in['notify']) ? $in['notify'] : !empty($comment['notify']),
			isset($in['email']) ? $in['email'] : @$comment['notify'], @$errors['email'], $prefix);
	}

	if (!ilya_user_post_permit_error('permit_edit_silent', $comment)) {
		$form['fields']['silent'] = array(
			'type' => 'checkbox',
			'label' => ilya_lang_html('question/save_silent_label'),
			'tags' => 'name="' . $prefix . 'silent"',
			'value' => ilya_html(@$in['silent']),
		);
	}

	return $form;
}


/*
	Processes a POSTed form for editing a comment and returns true if successful
*/
function ilya_page_q_edit_c_submit($comment, $question, $parent, &$in, &$errors)
{
	$commentid = $comment['postid'];
	$prefix = 'c' . $commentid . '_';

	$in = array();

	if ($comment['isbyuser']) {
		$in['name'] = ilya_opt('allow_anonymous_naming') ? ilya_post_text($prefix . 'name') : null;
		$in['notify'] = ilya_post_text($prefix . 'notify') !== null;
		$in['email'] = ilya_post_text($prefix . 'email');
	}

	if (!ilya_user_post_permit_error('permit_edit_silent', $comment))
		$in['silent'] = ilya_post_text($prefix . 'silent');

	ilya_get_post_content($prefix . 'editor', $prefix . 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

	// here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters

	$errors = array();

	if (!ilya_check_form_security_code('edit-' . $commentid, ilya_post_text($prefix . 'code')))
		$errors['content'] = ilya_lang_html('misc/form_security_again');

	else {
		$in['queued'] = ilya_opt('moderate_edited_again') && ilya_user_moderation_reason(ilya_user_level_for_post($comment));

		$filtermodules = ilya_load_modules_with('filter', 'filter_comment');
		foreach ($filtermodules as $filtermodule) {
			$oldin = $in;
			$filtermodule->filter_comment($in, $errors, $question, $parent, $comment);
			ilya_update_post_text($in, $oldin);
		}

		if (empty($errors)) {
			$userid = ilya_get_logged_in_userid();
			$handle = ilya_get_logged_in_handle();
			$cookieid = ilya_cookie_get();

			if (!isset($in['silent']))
				$in['silent'] = false;

			$setnotify = $comment['isbyuser'] ? ilya_combine_notify_email($comment['userid'], $in['notify'], $in['email']) : $comment['notify'];

			ilya_comment_set_content($comment, $in['content'], $in['format'], $in['text'], $setnotify,
				$userid, $handle, $cookieid, $question, $parent, @$in['name'], $in['queued'], $in['silent']);

			return true;
		}
	}

	return false;
}
