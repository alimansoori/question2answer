<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for question page (only viewing functionality here)


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
	header('Location: ../../');
	exit;
}

require_once ILYA__INCLUDE_DIR . 'app/cookies.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'util/sort.php';
require_once ILYA__INCLUDE_DIR . 'util/string.php';
require_once ILYA__INCLUDE_DIR . 'app/captcha.php';
require_once ILYA__INCLUDE_DIR . 'pages/question-view.php';
require_once ILYA__INCLUDE_DIR . 'app/updates.php';

$questionid = ilya_request_part(0);
$userid = ilya_get_logged_in_userid();
$cookieid = ilya_cookie_get();
$pagestate = ilya_get_state();


// Get information about this question

$cacheDriver = ILYA_Storage_CacheFactory::getCacheDriver();
$cacheKey = "question:$questionid";
$useCache = $userid === null && $cacheDriver->isEnabled() && !ilya_is_http_post() && empty($pagestate);
$saveCache = false;

if ($useCache) {
	$questionData = $cacheDriver->get($cacheKey);
}

if (!isset($questionData)) {
	$questionData = ilya_db_select_with_pending(
		ilya_db_full_post_selectspec($userid, $questionid),
		ilya_db_full_child_posts_selectspec($userid, $questionid),
		ilya_db_full_a_child_posts_selectspec($userid, $questionid),
		ilya_db_post_parent_q_selectspec($questionid),
		ilya_db_post_close_post_selectspec($questionid),
		ilya_db_post_duplicates_selectspec($questionid),
		ilya_db_post_meta_selectspec($questionid, 'ilya_q_extra'),
		ilya_db_category_nav_selectspec($questionid, true, true, true),
		isset($userid) ? ilya_db_is_favorite_selectspec($userid, ILYA__ENTITY_QUESTION, $questionid) : null
	);

	// whether to save the cache (actioned below, after basic checks)
	$saveCache = $useCache;
}

list($question, $childposts, $achildposts, $parentquestion, $closepost, $duplicateposts, $extravalue, $categories, $favorite) = $questionData;


if ($question['basetype'] != 'Q') // don't allow direct viewing of other types of post
	$question = null;

if (isset($question)) {
	$q_request = ilya_q_request($questionid, $question['title']);

	if (trim($q_request, '/') !== trim(ilya_request(), '/')) {
		// redirect if the current URL is incorrect
		ilya_redirect($q_request);
	}

	$question['extra'] = $extravalue;

	$answers = ilya_page_q_load_as($question, $childposts);
	$commentsfollows = ilya_page_q_load_c_follows($question, $childposts, $achildposts, $duplicateposts);

	$question = $question + ilya_page_q_post_rules($question, null, null, $childposts + $duplicateposts); // array union

	if ($question['selchildid'] && (@$answers[$question['selchildid']]['type'] != 'A'))
		$question['selchildid'] = null; // if selected answer is hidden or somehow not there, consider it not selected

	foreach ($answers as $key => $answer) {
		$answers[$key] = $answer + ilya_page_q_post_rules($answer, $question, $answers, $achildposts);
		$answers[$key]['isselected'] = ($answer['postid'] == $question['selchildid']);
	}

	foreach ($commentsfollows as $key => $commentfollow) {
		$parent = ($commentfollow['parentid'] == $questionid) ? $question : @$answers[$commentfollow['parentid']];
		$commentsfollows[$key] = $commentfollow + ilya_page_q_post_rules($commentfollow, $parent, $commentsfollows, null);
	}
}

// Deal with question not found or not viewable, otherwise report the view event

if (!isset($question))
	return include ILYA__INCLUDE_DIR . 'ilya-page-not-found.php';

if (!$question['viewable']) {
	$ilya_content = ilya_content_prepare();

	if ($question['queued'])
		$ilya_content['error'] = ilya_lang_html('question/q_waiting_approval');
	elseif ($question['flagcount'] && !isset($question['lastuserid']))
		$ilya_content['error'] = ilya_lang_html('question/q_hidden_flagged');
	elseif ($question['authorlast'])
		$ilya_content['error'] = ilya_lang_html('question/q_hidden_author');
	else
		$ilya_content['error'] = ilya_lang_html('question/q_hidden_other');

	$ilya_content['suggest_next'] = ilya_html_suggest_qs_tags(ilya_using_tags());

	return $ilya_content;
}

$permiterror = ilya_user_post_permit_error('permit_view_q_page', $question, null, false);

if ($permiterror && (ilya_is_human_probably() || !ilya_opt('allow_view_q_bots'))) {
	$ilya_content = ilya_content_prepare();
	$topage = ilya_q_request($questionid, $question['title']);

	switch ($permiterror) {
		case 'login':
			$ilya_content['error'] = ilya_insert_login_links(ilya_lang_html('main/view_q_must_login'), $topage);
			break;

		case 'confirm':
			$ilya_content['error'] = ilya_insert_login_links(ilya_lang_html('main/view_q_must_confirm'), $topage);
			break;

		case 'approve':
			$ilya_content['error'] = strtr(ilya_lang_html('main/view_q_must_be_approved'), array(
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


// Save question data to cache (if older than configured limit)

if ($saveCache) {
	$questionAge = ilya_opt('db_time') - $question['created'];
	if ($questionAge > 86400 * ilya_opt('caching_q_start')) {
		$cacheDriver->set($cacheKey, $questionData, ilya_opt('caching_q_time'));
	}
}


// Determine if captchas will be required

$captchareason = ilya_user_captcha_reason(ilya_user_level_for_post($question));
$usecaptcha = ($captchareason != false);


// If we're responding to an HTTP POST, include file that handles all posting/editing/etc... logic
// This is in a separate file because it's a *lot* of logic, and will slow down ordinary page views

$pagestart = ilya_get_start();
$showid = ilya_get('show');
$pageerror = null;
$formtype = null;
$formpostid = null;
$jumptoanchor = null;
$commentsall = null;

if (substr($pagestate, 0, 13) == 'showcomments-') {
	$commentsall = substr($pagestate, 13);
	$pagestate = null;

} elseif (isset($showid)) {
	foreach ($commentsfollows as $comment) {
		if ($comment['postid'] == $showid) {
			$commentsall = $comment['parentid'];
			break;
		}
	}
}

if (ilya_is_http_post() || strlen($pagestate))
	require ILYA__INCLUDE_DIR . 'pages/question-post.php';

$formrequested = isset($formtype);

if (!$formrequested && $question['answerbutton']) {
	$immedoption = ilya_opt('show_a_form_immediate');

	if ($immedoption == 'always' || ($immedoption == 'if_no_as' && !$question['isbyuser'] && !$question['acount']))
		$formtype = 'a_add'; // show answer form by default
}


// Get information on the users referenced

$usershtml = ilya_userids_handles_html(array_merge(array($question), $answers, $commentsfollows), true);


// Prepare content for theme

$ilya_content = ilya_content_prepare(true, array_keys(ilya_category_path($categories, $question['categoryid'])));

if (isset($userid) && !$formrequested)
	$ilya_content['favorite'] = ilya_favorite_form(ILYA__ENTITY_QUESTION, $questionid, $favorite,
		ilya_lang($favorite ? 'question/remove_q_favorites' : 'question/add_q_favorites'));

if (isset($pageerror))
	$ilya_content['error'] = $pageerror; // might also show voting error set in ilya-index.php

elseif ($question['queued'])
	$ilya_content['error'] = $question['isbyuser'] ? ilya_lang_html('question/q_your_waiting_approval') : ilya_lang_html('question/q_waiting_your_approval');

if ($question['hidden'])
	$ilya_content['hidden'] = true;

ilya_sort_by($commentsfollows, 'created');


// Prepare content for the question...

if ($formtype == 'q_edit') { // ...in edit mode
	$ilya_content['title'] = ilya_lang_html($question['editable'] ? 'question/edit_q_title' :
		(ilya_using_categories() ? 'question/recat_q_title' : 'question/retag_q_title'));
	$ilya_content['form_q_edit'] = ilya_page_q_edit_q_form($ilya_content, $question, @$qin, @$qerrors, $completetags, $categories);
	$ilya_content['q_view']['raw'] = $question;

} else { // ...in view mode
	$ilya_content['q_view'] = ilya_page_q_question_view($question, $parentquestion, $closepost, $usershtml, $formrequested);

	$ilya_content['title'] = $ilya_content['q_view']['title'];

	$ilya_content['description'] = ilya_html(ilya_shorten_string_line(ilya_viewer_text($question['content'], $question['format']), 150));

	$categorykeyword = @$categories[$question['categoryid']]['title'];

	$ilya_content['keywords'] = ilya_html(implode(',', array_merge(
		(ilya_using_categories() && strlen($categorykeyword)) ? array($categorykeyword) : array(),
		ilya_tagstring_to_tags($question['tags'])
	))); // as far as I know, META keywords have zero effect on search rankings or listings, but many people have asked for this
}

$microdata = ilya_opt('use_microdata');
if ($microdata) {
	$ilya_content['head_lines'][] = '<meta itemprop="name" content="' . ilya_html($ilya_content['q_view']['raw']['title']) . '">';
	$ilya_content['html_tags'] .= ' itemscope itemtype="https://schema.org/Article"';
	$ilya_content['wrapper_tags'] = ' itemprop="mainEntity" itemscope itemtype="https://schema.org/Article"';
}


// Prepare content for an answer being edited (if any) or to be added

if ($formtype == 'a_edit') {
	$ilya_content['a_form'] = ilya_page_q_edit_a_form($ilya_content, 'a' . $formpostid, $answers[$formpostid],
		$question, $answers, $commentsfollows, @$aeditin[$formpostid], @$aediterrors[$formpostid]);

	$ilya_content['a_form']['c_list'] = ilya_page_q_comment_follow_list($question, $answers[$formpostid],
		$commentsfollows, true, $usershtml, $formrequested, $formpostid);

	$jumptoanchor = 'a' . $formpostid;

} elseif ($formtype == 'a_add' || ($question['answerbutton'] && !$formrequested)) {
	$ilya_content['a_form'] = ilya_page_q_add_a_form($ilya_content, 'anew', $captchareason, $question, @$anewin, @$anewerrors, $formtype == 'a_add', $formrequested);

	if ($formrequested) {
		$jumptoanchor = 'anew';
	} elseif ($formtype == 'a_add') {
		$ilya_content['script_onloads'][] = array(
			"ilya_element_revealed=document.getElementById('anew');"
		);
	}
}


// Prepare content for comments on the question, plus add or edit comment forms

if ($formtype == 'q_close') {
	$ilya_content['q_view']['c_form'] = ilya_page_q_close_q_form($ilya_content, $question, 'close', @$closein, @$closeerrors);
	$jumptoanchor = 'close';

} elseif (($formtype == 'c_add' && $formpostid == $questionid) || ($question['commentbutton'] && !$formrequested)) { // ...to be added
	$ilya_content['q_view']['c_form'] = ilya_page_q_add_c_form($ilya_content, $question, $question, 'c' . $questionid,
		$captchareason, @$cnewin[$questionid], @$cnewerrors[$questionid], $formtype == 'c_add');

	if ($formtype == 'c_add' && $formpostid == $questionid) {
		$jumptoanchor = 'c' . $questionid;
		$commentsall = $questionid;
	}

} elseif ($formtype == 'c_edit' && @$commentsfollows[$formpostid]['parentid'] == $questionid) { // ...being edited
	$ilya_content['q_view']['c_form'] = ilya_page_q_edit_c_form($ilya_content, 'c' . $formpostid, $commentsfollows[$formpostid],
		@$ceditin[$formpostid], @$cediterrors[$formpostid]);

	$jumptoanchor = 'c' . $formpostid;
	$commentsall = $questionid;
}

$ilya_content['q_view']['c_list'] = ilya_page_q_comment_follow_list($question, $question, $commentsfollows,
	$commentsall == $questionid, $usershtml, $formrequested, $formpostid); // ...for viewing


// Prepare content for existing answers (could be added to by Ajax)

$ilya_content['a_list'] = array(
	'tags' => 'id="a_list"',
	'as' => array(),
);

// sort according to the site preferences

if (ilya_opt('sort_answers_by') == 'votes') {
	foreach ($answers as $answerid => $answer)
		$answers[$answerid]['sortvotes'] = $answer['downvotes'] - $answer['upvotes'];

	ilya_sort_by($answers, 'sortvotes', 'created');

} else {
	ilya_sort_by($answers, 'created');
}

// further changes to ordering to deal with queued, hidden and selected answers

$countfortitle = (int) $question['acount'];
$nextposition = 10000;
$answerposition = array();

foreach ($answers as $answerid => $answer) {
	if ($answer['viewable']) {
		$position = $nextposition++;

		if ($answer['hidden'])
			$position += 10000;

		elseif ($answer['queued']) {
			$position -= 10000;
			$countfortitle++; // include these in displayed count

		} elseif ($answer['isselected'] && ilya_opt('show_selected_first'))
			$position -= 5000;

		$answerposition[$answerid] = $position;
	}
}

asort($answerposition, SORT_NUMERIC);

// extract IDs and prepare for pagination

$answerids = array_keys($answerposition);
$countforpages = count($answerids);
$pagesize = ilya_opt('page_size_q_as');

// see if we need to display a particular answer

if (isset($showid)) {
	if (isset($commentsfollows[$showid]))
		$showid = $commentsfollows[$showid]['parentid'];

	$position = array_search($showid, $answerids);

	if (is_numeric($position))
		$pagestart = floor($position / $pagesize) * $pagesize;
}

// set the canonical url based on possible pagination

$ilya_content['canonical'] = ilya_path_html(ilya_q_request($question['postid'], $question['title']),
	($pagestart > 0) ? array('start' => $pagestart) : null, ilya_opt('site_url'));

// build the actual answer list

$answerids = array_slice($answerids, $pagestart, $pagesize);

foreach ($answerids as $answerid) {
	$answer = $answers[$answerid];

	if (!($formtype == 'a_edit' && $formpostid == $answerid)) {
		$a_view = ilya_page_q_answer_view($question, $answer, $answer['isselected'], $usershtml, $formrequested);

		// Prepare content for comments on this answer, plus add or edit comment forms

		if (($formtype == 'c_add' && $formpostid == $answerid) || ($answer['commentbutton'] && !$formrequested)) { // ...to be added
			$a_view['c_form'] = ilya_page_q_add_c_form($ilya_content, $question, $answer, 'c' . $answerid,
				$captchareason, @$cnewin[$answerid], @$cnewerrors[$answerid], $formtype == 'c_add');

			if ($formtype == 'c_add' && $formpostid == $answerid) {
				$jumptoanchor = 'c' . $answerid;
				$commentsall = $answerid;
			}

		} elseif ($formtype == 'c_edit' && @$commentsfollows[$formpostid]['parentid'] == $answerid) { // ...being edited
			$a_view['c_form'] = ilya_page_q_edit_c_form($ilya_content, 'c' . $formpostid, $commentsfollows[$formpostid],
				@$ceditin[$formpostid], @$cediterrors[$formpostid]);

			$jumptoanchor = 'c' . $formpostid;
			$commentsall = $answerid;
		}

		$a_view['c_list'] = ilya_page_q_comment_follow_list($question, $answer, $commentsfollows,
			$commentsall == $answerid, $usershtml, $formrequested, $formpostid); // ...for viewing

		// Add the answer to the list

		$ilya_content['a_list']['as'][] = $a_view;
	}
}

if ($question['basetype'] == 'Q') {
	$ilya_content['a_list']['title_tags'] = 'id="a_list_title"';

	$split = $countfortitle == 1
		? ilya_lang_html_sub_split('question/1_answer_title', '1', '1')
		: ilya_lang_html_sub_split('question/x_answers_title', $countfortitle);

	if ($microdata) {
		$split['data'] = '<span itemprop="answerCount">' . $split['data'] . '</span>';
	}

	$ilya_content['a_list']['title'] = $split['prefix'] . $split['data'] . $split['suffix'];

	if ($countfortitle == 0) {
		$ilya_content['a_list']['title_tags'] .= ' style="display:none;" ';
	}
}

if (!$formrequested) {
	$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $pagestart, $pagesize, $countforpages, ilya_opt('pages_prev_next'), array(), false, 'a_list_title');
}


// Some generally useful stuff

if (ilya_using_categories() && count($categories)) {
	$ilya_content['navigation']['cat'] = ilya_category_navigation($categories, $question['categoryid']);
}

if (isset($jumptoanchor)) {
	$ilya_content['script_onloads'][] = array(
		'ilya_scroll_page_to($("#"+' . ilya_js($jumptoanchor) . ').offset().top);'
	);
}


// Determine whether this request should be counted for page view statistics.
// The lastviewip check is now part of the hotness query in order to bypass caching.

if (ilya_opt('do_count_q_views') && !$formrequested && !ilya_is_http_post() && ilya_is_human_probably() &&
	(!$question['views'] || (
		// if it has more than zero views, then it must be different IP & user & cookieid from the creator
		(@inet_ntop($question['createip']) != ilya_remote_ip_address() || !isset($question['createip'])) &&
		($question['userid'] != $userid || !isset($question['userid'])) &&
		($question['cookieid'] != $cookieid || !isset($question['cookieid']))
	))
) {
	$ilya_content['inc_views_postid'] = $questionid;
}


return $ilya_content;
