<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for ask a question page


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


require_once QA_INCLUDE_DIR.'app/format.php';
require_once QA_INCLUDE_DIR.'app/limits.php';
require_once QA_INCLUDE_DIR.'db/selects.php';
require_once QA_INCLUDE_DIR.'util/sort.php';


// Check whether this is a follow-on question and get some info we need from the database

$in = array();

$followpostid = ilya_get('follow');
$in['categoryid'] = ilya_clicked('doask') ? ilya_get_category_field_value('category') : ilya_get('cat');
$userid = ilya_get_logged_in_userid();

list($categories, $followanswer, $completetags) = ilya_db_select_with_pending(
	ilya_db_category_nav_selectspec($in['categoryid'], true),
	isset($followpostid) ? ilya_db_full_post_selectspec($userid, $followpostid) : null,
	ilya_db_popular_tags_selectspec(0, QA_DB_RETRIEVE_COMPLETE_TAGS)
);

if (!isset($categories[$in['categoryid']])) {
	$in['categoryid'] = null;
}

if (@$followanswer['basetype'] != 'A') {
	$followanswer = null;
}


// Check for permission error

$permiterror = ilya_user_maximum_permit_error('permit_post_q', QA_LIMIT_QUESTIONS);

if ($permiterror) {
	$ilya_content = ilya_content_prepare();

	// The 'approve', 'login', 'confirm', 'limit', 'userblock', 'ipblock' permission errors are reported to the user here
	// The other option ('level') prevents the menu option being shown, in ilya_content_prepare(...)

	switch ($permiterror) {
		case 'login':
			$ilya_content['error'] = ilya_insert_login_links(ilya_lang_html('question/ask_must_login'), ilya_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
			break;

		case 'confirm':
			$ilya_content['error'] = ilya_insert_login_links(ilya_lang_html('question/ask_must_confirm'), ilya_request(), isset($followpostid) ? array('follow' => $followpostid) : null);
			break;

		case 'limit':
			$ilya_content['error'] = ilya_lang_html('question/ask_limit');
			break;

		case 'approve':
			$ilya_content['error'] = strtr(ilya_lang_html('question/ask_must_be_approved'), array(
				'^1' => '<a href="' . ilya_path_html('account') . '">',
				'^2' => '</a>',
			));
			break;

		default:
			$ilya_content['error'] = ilya_lang_html('users/no_permission');
			break;
	}

	return $ilya_content;
}


// Process input

$captchareason = ilya_user_captcha_reason();

$in['title'] = ilya_get_post_title('title'); // allow title and tags to be posted by an external form
$in['extra'] = ilya_opt('extra_field_active') ? ilya_post_text('extra') : null;

if (ilya_using_tags()) {
	$in['tags'] = ilya_get_tags_field_value('tags');
}

if (ilya_clicked('doask')) {
	require_once QA_INCLUDE_DIR.'app/post-create.php';
	require_once QA_INCLUDE_DIR.'util/string.php';

	$categoryids = array_keys(ilya_category_path($categories, @$in['categoryid']));
	$userlevel = ilya_user_level_for_categories($categoryids);

	$in['name'] = ilya_opt('allow_anonymous_naming') ? ilya_post_text('name') : null;
	$in['notify'] = strlen(ilya_post_text('notify')) > 0;
	$in['email'] = ilya_post_text('email');
	$in['queued'] = ilya_user_moderation_reason($userlevel) !== false;

	ilya_get_post_content('editor', 'content', $in['editor'], $in['content'], $in['format'], $in['text']);

	$errors = array();

	if (!ilya_check_form_security_code('ask', ilya_post_text('code'))) {
		$errors['page'] = ilya_lang_html('misc/form_security_again');
	}
	else {
		$filtermodules = ilya_load_modules_with('filter', 'filter_question');
		foreach ($filtermodules as $filtermodule) {
			$oldin = $in;
			$filtermodule->filter_question($in, $errors, null);
			ilya_update_post_text($in, $oldin);
		}

		if (ilya_using_categories() && count($categories) && (!ilya_opt('allow_no_category')) && !isset($in['categoryid'])) {
			// check this here because we need to know count($categories)
			$errors['categoryid'] = ilya_lang_html('question/category_required');
		}
		elseif (ilya_user_permit_error('permit_post_q', null, $userlevel)) {
			$errors['categoryid'] = ilya_lang_html('question/category_ask_not_allowed');
		}

		if ($captchareason) {
			require_once QA_INCLUDE_DIR.'app/captcha.php';
			ilya_captcha_validate_post($errors);
		}

		if (empty($errors)) {
			// check if the question is already posted
			$testTitleWords = implode(' ', ilya_string_to_words($in['title']));
			$testContentWords = implode(' ', ilya_string_to_words($in['content']));
			$recentQuestions = ilya_db_select_with_pending(ilya_db_qs_selectspec(null, 'created', 0, null, null, false, true, 5));

			foreach ($recentQuestions as $question) {
				if (!$question['hidden']) {
					$qTitleWords = implode(' ', ilya_string_to_words($question['title']));
					$qContentWords = implode(' ', ilya_string_to_words($question['content']));

					if ($qTitleWords == $testTitleWords && $qContentWords == $testContentWords) {
						$errors['page'] = ilya_lang_html('question/duplicate_content');
						break;
					}
				}
			}
		}

		if (empty($errors)) {
			$cookieid = isset($userid) ? ilya_cookie_get() : ilya_cookie_get_create(); // create a new cookie if necessary

			$questionid = ilya_question_create($followanswer, $userid, ilya_get_logged_in_handle(), $cookieid,
				$in['title'], $in['content'], $in['format'], $in['text'], isset($in['tags']) ? ilya_tags_to_tagstring($in['tags']) : '',
				$in['notify'], $in['email'], $in['categoryid'], $in['extra'], $in['queued'], $in['name']);

			ilya_redirect(ilya_q_request($questionid, $in['title'])); // our work is done here
		}
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare(false, array_keys(ilya_category_path($categories, @$in['categoryid'])));

$ilya_content['title'] = ilya_lang_html(isset($followanswer) ? 'question/ask_follow_title' : 'question/ask_title');
$ilya_content['error'] = @$errors['page'];

$editorname = isset($in['editor']) ? $in['editor'] : ilya_opt('editor_for_qs');
$editor = ilya_load_editor(@$in['content'], @$in['format'], $editorname);

$field = ilya_editor_load_field($editor, $ilya_content, @$in['content'], @$in['format'], 'content', 12, false);
$field['label'] = ilya_lang_html('question/q_content_label');
$field['error'] = ilya_html(@$errors['content']);

$custom = ilya_opt('show_custom_ask') ? trim(ilya_opt('custom_ask')) : '';

$ilya_content['form'] = array(
	'tags' => 'name="ask" method="post" action="'.ilya_self_html().'"',

	'style' => 'tall',

	'fields' => array(
		'custom' => array(
			'type' => 'custom',
			'note' => $custom,
		),

		'title' => array(
			'label' => ilya_lang_html('question/q_title_label'),
			'tags' => 'name="title" id="title" autocomplete="off"',
			'value' => ilya_html(@$in['title']),
			'error' => ilya_html(@$errors['title']),
		),

		'similar' => array(
			'type' => 'custom',
			'html' => '<span id="similar"></span>',
		),

		'content' => $field,
	),

	'buttons' => array(
		'ask' => array(
			'tags' => 'onclick="ilya_show_waiting_after(this, false); '.
				(method_exists($editor, 'update_script') ? $editor->update_script('content') : '').'"',
			'label' => ilya_lang_html('question/ask_button'),
		),
	),

	'hidden' => array(
		'editor' => ilya_html($editorname),
		'code' => ilya_get_form_security_code('ask'),
		'doask' => '1',
	),
);

if (!strlen($custom)) {
	unset($ilya_content['form']['fields']['custom']);
}

if (ilya_opt('do_ask_check_qs') || ilya_opt('do_example_tags')) {
	$ilya_content['form']['fields']['title']['tags'] .= ' onchange="ilya_title_change(this.value);"';

	if (strlen(@$in['title'])) {
		$ilya_content['script_onloads'][] = 'ilya_title_change('.ilya_js($in['title']).');';
	}
}

if (isset($followanswer)) {
	$viewer = ilya_load_viewer($followanswer['content'], $followanswer['format']);

	$field = array(
		'type' => 'static',
		'label' => ilya_lang_html('question/ask_follow_from_a'),
		'value' => $viewer->get_html($followanswer['content'], $followanswer['format'], array('blockwordspreg' => ilya_get_block_words_preg())),
	);

	ilya_array_insert($ilya_content['form']['fields'], 'title', array('follows' => $field));
}

if (ilya_using_categories() && count($categories)) {
	$field = array(
		'label' => ilya_lang_html('question/q_category_label'),
		'error' => ilya_html(@$errors['categoryid']),
	);

	ilya_set_up_category_field($ilya_content, $field, 'category', $categories, $in['categoryid'], true, ilya_opt('allow_no_sub_category'));

	if (!ilya_opt('allow_no_category')) // don't auto-select a category even though one is required
		$field['options'][''] = '';

	ilya_array_insert($ilya_content['form']['fields'], 'content', array('category' => $field));
}

if (ilya_opt('extra_field_active')) {
	$field = array(
		'label' => ilya_html(ilya_opt('extra_field_prompt')),
		'tags' => 'name="extra"',
		'value' => ilya_html(@$in['extra']),
		'error' => ilya_html(@$errors['extra']),
	);

	ilya_array_insert($ilya_content['form']['fields'], null, array('extra' => $field));
}

if (ilya_using_tags()) {
	$field = array(
		'error' => ilya_html(@$errors['tags']),
	);

	ilya_set_up_tag_field($ilya_content, $field, 'tags', isset($in['tags']) ? $in['tags'] : array(), array(),
		ilya_opt('do_complete_tags') ? array_keys($completetags) : array(), ilya_opt('page_size_ask_tags'));

	ilya_array_insert($ilya_content['form']['fields'], null, array('tags' => $field));
}

if (!isset($userid) && ilya_opt('allow_anonymous_naming')) {
	ilya_set_up_name_field($ilya_content, $ilya_content['form']['fields'], @$in['name']);
}

ilya_set_up_notify_fields($ilya_content, $ilya_content['form']['fields'], 'Q', ilya_get_logged_in_email(),
	isset($in['notify']) ? $in['notify'] : ilya_opt('notify_users_default'), @$in['email'], @$errors['email']);

if ($captchareason) {
	require_once QA_INCLUDE_DIR.'app/captcha.php';
	ilya_set_up_captcha_field($ilya_content, $ilya_content['form']['fields'], @$errors, ilya_captcha_reason_note($captchareason));
}

$ilya_content['focusid'] = 'title';


return $ilya_content;
