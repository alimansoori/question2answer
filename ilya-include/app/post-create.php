<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Creating questions, answers and comments (application level)


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

if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once ILYA__INCLUDE_DIR . 'db/maxima.php';
require_once ILYA__INCLUDE_DIR . 'db/post-create.php';
require_once ILYA__INCLUDE_DIR . 'db/points.php';
require_once ILYA__INCLUDE_DIR . 'db/hotness.php';
require_once ILYA__INCLUDE_DIR . 'util/string.php';


/**
 * Return value to store in database combining $notify and $email values entered by user $userid (or null for anonymous)
 * @param $userid
 * @param $notify
 * @param $email
 * @return null|string
 */
function ilya_combine_notify_email($userid, $notify, $email)
{
	return $notify ? (empty($email) ? (isset($userid) ? '@' : null) : $email) : null;
}


/**
 * Add a question (application level) - create record, update appropriate counts, index it, send notifications.
 * If question is follow-on from an answer, $followanswer should contain answer database record, otherwise null.
 * See /ilya-include/app/posts.php for a higher-level function which is easier to use.
 * @param $followanswer
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $title
 * @param $content
 * @param $format
 * @param $text
 * @param $tagstring
 * @param $notify
 * @param $email
 * @param $categoryid
 * @param $extravalue
 * @param bool $queued
 * @param $name
 * @return mixed
 */
function ilya_question_create($followanswer, $userid, $handle, $cookieid, $title, $content, $format, $text, $tagstring, $notify, $email,
	$categoryid = null, $extravalue = null, $queued = false, $name = null)
{
	require_once ILYA__INCLUDE_DIR . 'db/selects.php';

	$postid = ilya_db_post_create($queued ? 'Q_QUEUED' : 'Q', @$followanswer['postid'], $userid, isset($userid) ? null : $cookieid,
		ilya_remote_ip_address(), $title, $content, $format, $tagstring, ilya_combine_notify_email($userid, $notify, $email),
		$categoryid, isset($userid) ? null : $name);

	if (isset($extravalue)) {
		require_once ILYA__INCLUDE_DIR . 'db/metas.php';
		ilya_db_postmeta_set($postid, 'ilya_q_extra', $extravalue);
	}

	ilya_db_posts_calc_category_path($postid);
	ilya_db_hotness_update($postid);

	if ($queued) {
		ilya_db_queuedcount_update();

	} else {
		ilya_post_index($postid, 'Q', $postid, @$followanswer['postid'], $title, $content, $format, $text, $tagstring, $categoryid);
		ilya_update_counts_for_q($postid);
		ilya_db_points_update_ifuser($userid, 'qposts');
	}

	ilya_report_event($queued ? 'q_queue' : 'q_post', $userid, $handle, $cookieid, array(
		'postid' => $postid,
		'parentid' => @$followanswer['postid'],
		'parent' => $followanswer,
		'title' => $title,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'tags' => $tagstring,
		'categoryid' => $categoryid,
		'extra' => $extravalue,
		'name' => $name,
		'notify' => $notify,
		'email' => $email,
	));

	return $postid;
}


/**
 * Perform various common cached count updating operations to reflect changes in the question whose id is $postid
 * @param $postid
 */
function ilya_update_counts_for_q($postid)
{
	if (isset($postid)) // post might no longer exist
		ilya_db_category_path_qcount_update(ilya_db_post_get_category_path($postid));

	ilya_db_qcount_update();
	ilya_db_unaqcount_update();
	ilya_db_unselqcount_update();
	ilya_db_unupaqcount_update();
	ilya_db_tagcount_update();
}


/**
 * Return an array containing the elements of $inarray whose key is in $keys
 * @param $inarray
 * @param $keys
 * @return array
 */
function ilya_array_filter_by_keys($inarray, $keys)
{
	$outarray = array();

	foreach ($keys as $key) {
		if (isset($inarray[$key]))
			$outarray[$key] = $inarray[$key];
	}

	return $outarray;
}


/**
 * Suspend the indexing (and unindexing) of posts via ilya_post_index(...) and ilya_post_unindex(...)
 * if $suspend is true, otherwise reinstate it. A counter is kept to allow multiple calls.
 * @param bool $suspend
 */
function ilya_suspend_post_indexing($suspend = true)
{
	global $ilya_post_indexing_suspended;

	$ilya_post_indexing_suspended += ($suspend ? 1 : -1);
}


/**
 * Add post $postid (which comes under $questionid) of $type (Q/A/C) to the database index, with $title, $text,
 * $tagstring and $categoryid. Calls through to all installed search modules.
 * @param $postid
 * @param $type
 * @param $questionid
 * @param $parentid
 * @param $title
 * @param $content
 * @param $format
 * @param $text
 * @param $tagstring
 * @param $categoryid
 */
function ilya_post_index($postid, $type, $questionid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid)
{
	global $ilya_post_indexing_suspended;

	if ($ilya_post_indexing_suspended > 0)
		return;

	// Send through to any search modules for indexing

	$searches = ilya_load_modules_with('search', 'index_post');
	foreach ($searches as $search)
		$search->index_post($postid, $type, $questionid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid);
}


/**
 * Add an answer (application level) - create record, update appropriate counts, index it, send notifications.
 * $question should contain database record for the question this is an answer to.
 * See /ilya-include/app/posts.php for a higher-level function which is easier to use.
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $content
 * @param $format
 * @param $text
 * @param $notify
 * @param $email
 * @param $question
 * @param bool $queued
 * @param $name
 * @return mixed
 */
function ilya_answer_create($userid, $handle, $cookieid, $content, $format, $text, $notify, $email, $question, $queued = false, $name = null)
{
	$postid = ilya_db_post_create($queued ? 'A_QUEUED' : 'A', $question['postid'], $userid, isset($userid) ? null : $cookieid,
		ilya_remote_ip_address(), null, $content, $format, null, ilya_combine_notify_email($userid, $notify, $email),
		$question['categoryid'], isset($userid) ? null : $name);

	ilya_db_posts_calc_category_path($postid);

	if ($queued) {
		ilya_db_queuedcount_update();

	} else {
		if ($question['type'] == 'Q') // don't index answer if parent question is hidden or queued
			ilya_post_index($postid, 'A', $question['postid'], $question['postid'], null, $content, $format, $text, null, $question['categoryid']);

		ilya_update_q_counts_for_a($question['postid']);
		ilya_db_points_update_ifuser($userid, 'aposts');
	}

	ilya_report_event($queued ? 'a_queue' : 'a_post', $userid, $handle, $cookieid, array(
		'postid' => $postid,
		'parentid' => $question['postid'],
		'parent' => $question,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'categoryid' => $question['categoryid'],
		'name' => $name,
		'notify' => $notify,
		'email' => $email,
	));

	return $postid;
}


/**
 * Perform various common cached count updating operations to reflect changes in an answer of question $questionid
 * @param $questionid
 */
function ilya_update_q_counts_for_a($questionid)
{
	ilya_db_post_acount_update($questionid);
	ilya_db_hotness_update($questionid);
	ilya_db_acount_update();
	ilya_db_unaqcount_update();
	ilya_db_unupaqcount_update();
}


/**
 * Add a comment (application level) - create record, update appropriate counts, index it, send notifications.
 * $question should contain database record for the question this is part of (as direct or comment on Q's answer).
 * If this is a comment on an answer, $answer should contain database record for the answer, otherwise null.
 * $commentsfollows should contain database records for all previous comments on the same question or answer,
 * but it can also contain other records that are ignored.
 * See /ilya-include/app/posts.php for a higher-level function which is easier to use.
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param $content
 * @param $format
 * @param $text
 * @param $notify
 * @param $email
 * @param $question
 * @param $parent
 * @param $commentsfollows
 * @param bool $queued
 * @param $name
 * @return mixed
 */
function ilya_comment_create($userid, $handle, $cookieid, $content, $format, $text, $notify, $email, $question, $parent, $commentsfollows, $queued = false, $name = null)
{
	require_once ILYA__INCLUDE_DIR . 'app/emails.php';
	require_once ILYA__INCLUDE_DIR . 'app/options.php';
	require_once ILYA__INCLUDE_DIR . 'app/format.php';
	require_once ILYA__INCLUDE_DIR . 'util/string.php';

	if (!isset($parent))
		$parent = $question; // for backwards compatibility with old answer parameter

	$postid = ilya_db_post_create($queued ? 'C_QUEUED' : 'C', $parent['postid'], $userid, isset($userid) ? null : $cookieid,
		ilya_remote_ip_address(), null, $content, $format, null, ilya_combine_notify_email($userid, $notify, $email),
		$question['categoryid'], isset($userid) ? null : $name);

	ilya_db_posts_calc_category_path($postid);

	if ($queued) {
		ilya_db_queuedcount_update();

	} else {
		if ($question['type'] == 'Q' && ($parent['type'] == 'Q' || $parent['type'] == 'A')) { // only index if antecedents fully visible
			ilya_post_index($postid, 'C', $question['postid'], $parent['postid'], null, $content, $format, $text, null, $question['categoryid']);
		}

		ilya_db_points_update_ifuser($userid, 'cposts');
		ilya_db_ccount_update();
	}

	$thread = array();

	foreach ($commentsfollows as $comment) {
		if ($comment['type'] == 'C' && $comment['parentid'] == $parent['postid']) // find just those for this parent, fully visible
			$thread[] = $comment;
	}

	ilya_report_event($queued ? 'c_queue' : 'c_post', $userid, $handle, $cookieid, array(
		'postid' => $postid,
		'parentid' => $parent['postid'],
		'parenttype' => $parent['basetype'],
		'parent' => $parent,
		'questionid' => $question['postid'],
		'question' => $question,
		'thread' => $thread,
		'content' => $content,
		'format' => $format,
		'text' => $text,
		'categoryid' => $question['categoryid'],
		'name' => $name,
		'notify' => $notify,
		'email' => $email,
	));

	return $postid;
}
