<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Higher-level functions to create and manipulate posts


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

require_once ILYA__INCLUDE_DIR . 'ilya-db.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';
require_once ILYA__INCLUDE_DIR . 'app/post-create.php';
require_once ILYA__INCLUDE_DIR . 'app/post-update.php';
require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'util/string.php';


/**
 * Create a new post in the database, and return its postid.
 *
 * Set $type to 'Q' for a new question, 'A' for an answer, or 'C' for a comment. You can also use 'Q_QUEUED',
 * 'A_QUEUED' or 'C_QUEUED' to create a post which is queued for moderator approval. For questions, set $parentid to
 * the postid of the answer to which the question is related, or null if (as in most cases) the question is not related
 * to an answer. For answers, set $parentid to the postid of the question being answered. For comments, set $parentid
 * to the postid of the question or answer to which the comment relates. The $content and $format parameters go
 * together - if $format is '' then $content should be in plain UTF-8 text, and if $format is 'html' then $content
 * should be in UTF-8 HTML. Other values of $format may be allowed if an appropriate viewer module is installed. The
 * $title, $categoryid and $tags parameters are only relevant when creating a question - $tags can either be an array
 * of tags, or a string of tags separated by commas. The new post will be assigned to $userid if it is not null,
 * otherwise it will be by a non-user. If $notify is true then the author will be sent notifications relating to the
 * post - either to $email if it is specified and valid, or to the current email address of $userid if $email is '@'.
 * If you're creating a question, the $extravalue parameter will be set as the custom extra field, if not null. For all
 * post types you can specify the $name of the post's author, which is relevant if the $userid is null.
 * @param $type
 * @param $parentid
 * @param $title
 * @param $content
 * @param string $format
 * @param $categoryid
 * @param $tags
 * @param $userid
 * @param $notify
 * @param $email
 * @param $extravalue
 * @param $name
 * @return mixed
 */
function ilya_post_create($type, $parentid, $title, $content, $format = '', $categoryid = null, $tags = null, $userid = null,
	$notify = null, $email = null, $extravalue = null, $name = null)
{
	$handle = ilya_userid_to_handle($userid);
	$text = ilya_post_content_to_text($content, $format);

	switch ($type) {
		case 'Q':
		case 'Q_QUEUED':
			$followanswer = isset($parentid) ? ilya_post_get_full($parentid, 'A') : null;
			$tagstring = ilya_post_tags_to_tagstring($tags);
			$postid = ilya_question_create($followanswer, $userid, $handle, null, $title, $content, $format, $text, $tagstring,
				$notify, $email, $categoryid, $extravalue, $type == 'Q_QUEUED', $name);
			break;

		case 'A':
		case 'A_QUEUED':
			$question = ilya_post_get_full($parentid, 'Q');
			$postid = ilya_answer_create($userid, $handle, null, $content, $format, $text, $notify, $email, $question, $type == 'A_QUEUED', $name);
			break;

		case 'C':
		case 'C_QUEUED':
			$parent = ilya_post_get_full($parentid, 'QA');
			$commentsfollows = ilya_db_single_select(ilya_db_full_child_posts_selectspec(null, $parentid));
			$question = ilya_post_parent_to_question($parent);
			$postid = ilya_comment_create($userid, $handle, null, $content, $format, $text, $notify, $email, $question, $parent, $commentsfollows, $type == 'C_QUEUED', $name);
			break;

		default:
			ilya_fatal_error('Post type not recognized: ' . $type);
			break;
	}

	return $postid;
}


/**
 * Change the data stored for post $postid based on any of the $title, $content, $format, $tags, $notify, $email,
 * $extravalue and $name parameters passed which are not null. The meaning of these parameters is the same as for
 * ilya_post_create() above. Pass the identify of the user making this change in $byuserid (or null for silent).
 * @param $postid
 * @param $title
 * @param $content
 * @param $format
 * @param $tags
 * @param $notify
 * @param $email
 * @param $byuserid
 * @param $extravalue
 * @param $name
 */
function ilya_post_set_content($postid, $title, $content, $format = null, $tags = null, $notify = null, $email = null, $byuserid = null, $extravalue = null, $name = null)
{
	$oldpost = ilya_post_get_full($postid, 'QAC');

	if (!isset($title))
		$title = $oldpost['title'];

	if (!isset($content))
		$content = $oldpost['content'];

	if (!isset($format))
		$format = $oldpost['format'];

	if (!isset($tags))
		$tags = ilya_tagstring_to_tags($oldpost['tags']);

	if (isset($notify) || isset($email))
		$setnotify = ilya_combine_notify_email($oldpost['userid'], isset($notify) ? $notify : isset($oldpost['notify']),
			isset($email) ? $email : $oldpost['notify']);
	else
		$setnotify = $oldpost['notify'];

	$byhandle = ilya_userid_to_handle($byuserid);

	$text = ilya_post_content_to_text($content, $format);

	switch ($oldpost['basetype']) {
		case 'Q':
			$tagstring = ilya_post_tags_to_tagstring($tags);
			ilya_question_set_content($oldpost, $title, $content, $format, $text, $tagstring, $setnotify, $byuserid, $byhandle, null, $extravalue, $name);
			break;

		case 'A':
			$question = ilya_post_get_full($oldpost['parentid'], 'Q');
			ilya_answer_set_content($oldpost, $content, $format, $text, $setnotify, $byuserid, $byhandle, null, $question, $name);
			break;

		case 'C':
			$parent = ilya_post_get_full($oldpost['parentid'], 'QA');
			$question = ilya_post_parent_to_question($parent);
			ilya_comment_set_content($oldpost, $content, $format, $text, $setnotify, $byuserid, $byhandle, null, $question, $parent, $name);
			break;
	}
}


/**
 * Change the category of $postid to $categoryid. The category of all related posts (shown together on the same
 * question page) will also be changed. Pass the identify of the user making this change in $byuserid (or null for an
 * anonymous change).
 * @param $postid
 * @param $categoryid
 * @param $byuserid
 */
function ilya_post_set_category($postid, $categoryid, $byuserid = null)
{
	$oldpost = ilya_post_get_full($postid, 'QAC');

	if ($oldpost['basetype'] == 'Q') {
		$byhandle = ilya_userid_to_handle($byuserid);
		$answers = ilya_post_get_question_answers($postid);
		$commentsfollows = ilya_post_get_question_commentsfollows($postid);
		$closepost = ilya_post_get_question_closepost($postid);
		ilya_question_set_category($oldpost, $categoryid, $byuserid, $byhandle, null, $answers, $commentsfollows, $closepost);

	} else
		ilya_post_set_category($oldpost['parentid'], $categoryid, $byuserid); // keep looking until we find the parent question
}


/**
 * Set the selected best answer of $questionid to $answerid (or to none if $answerid is null). Pass the identify of the
 * user in $byuserid (or null for an anonymous change).
 * @param $questionid
 * @param $answerid
 * @param $byuserid
 */
function ilya_post_set_selchildid($questionid, $answerid, $byuserid = null)
{
	$oldquestion = ilya_post_get_full($questionid, 'Q');
	$byhandle = ilya_userid_to_handle($byuserid);
	$answers = ilya_post_get_question_answers($questionid);

	if (isset($answerid) && !isset($answers[$answerid]))
		ilya_fatal_error('Answer ID could not be found: ' . $answerid);

	ilya_question_set_selchildid($byuserid, $byhandle, null, $oldquestion, $answerid, $answers);
}


/**
 * Close $questionid if $closed is true, otherwise reopen it. If $closed is true, pass either the $originalpostid of
 * the question that it is a duplicate of, or a $note to explain why it's closed. Pass the identifier of the user in
 * $byuserid (or null for an anonymous change).
 * @param $questionid
 * @param bool $closed
 * @param $originalpostid
 * @param $note
 * @param $byuserid
 */
function ilya_post_set_closed($questionid, $closed = true, $originalpostid = null, $note = null, $byuserid = null)
{
	$oldquestion = ilya_post_get_full($questionid, 'Q');
	$oldclosepost = ilya_post_get_question_closepost($questionid);
	$byhandle = ilya_userid_to_handle($byuserid);

	if ($closed) {
		if (isset($originalpostid))
			ilya_question_close_duplicate($oldquestion, $oldclosepost, $originalpostid, $byuserid, $byhandle, null);
		elseif (isset($note))
			ilya_question_close_other($oldquestion, $oldclosepost, $note, $byuserid, $byhandle, null);
		else
			ilya_fatal_error('Question must be closed as a duplicate or with a note');

	} else
		ilya_question_close_clear($oldquestion, $oldclosepost, $byuserid, $byhandle, null);
}

/**
 * Return whether the given question is closed. This check takes into account the do_close_on_select option which
 * considers questions with a selected answer as closed.
 * @since 1.8.2
 * @param array $question
 * @return bool
 */
function ilya_post_is_closed(array $question)
{
	return isset($question['closedbyid']) || (isset($question['selchildid']) && ilya_opt('do_close_on_select'));
}


/**
 * Hide $postid if $hidden is true, otherwise show the post. Pass the identify of the user making this change in
 * $byuserid (or null for a silent change).
 * @deprecated Replaced by ilya_post_set_status.
 * @param $postid
 * @param bool $hidden
 * @param $byuserid
 */
function ilya_post_set_hidden($postid, $hidden = true, $byuserid = null)
{
	ilya_post_set_status($postid, $hidden ? ILYA__POST_STATUS_HIDDEN : ILYA__POST_STATUS_NORMAL, $byuserid);
}


/**
 * Change the status of $postid to $status, which should be one of the ILYA__POST_STATUS_* constants defined in
 * /ilya-include/app/post-update.php. Pass the identify of the user making this change in $byuserid (or null for a silent change).
 * @param $postid
 * @param $status
 * @param $byuserid
 */
function ilya_post_set_status($postid, $status, $byuserid = null)
{
	$oldpost = ilya_post_get_full($postid, 'QAC');
	$byhandle = ilya_userid_to_handle($byuserid);

	switch ($oldpost['basetype']) {
		case 'Q':
			$answers = ilya_post_get_question_answers($postid);
			$commentsfollows = ilya_post_get_question_commentsfollows($postid);
			$closepost = ilya_post_get_question_closepost($postid);
			ilya_question_set_status($oldpost, $status, $byuserid, $byhandle, null, $answers, $commentsfollows, $closepost);
			break;

		case 'A':
			$question = ilya_post_get_full($oldpost['parentid'], 'Q');
			$commentsfollows = ilya_post_get_answer_commentsfollows($postid);
			ilya_answer_set_status($oldpost, $status, $byuserid, $byhandle, null, $question, $commentsfollows);
			break;

		case 'C':
			$parent = ilya_post_get_full($oldpost['parentid'], 'QA');
			$question = ilya_post_parent_to_question($parent);
			ilya_comment_set_status($oldpost, $status, $byuserid, $byhandle, null, $question, $parent);
			break;
	}
}


/**
 * Set the created date of $postid to $created, which is a unix timestamp.
 * @param $postid
 * @param $created
 */
function ilya_post_set_created($postid, $created)
{
	$oldpost = ilya_post_get_full($postid);

	ilya_db_post_set_created($postid, $created);

	switch ($oldpost['basetype']) {
		case 'Q':
			ilya_db_hotness_update($postid);
			break;

		case 'A':
			ilya_db_hotness_update($oldpost['parentid']);
			break;
	}
}


/**
 * Delete $postid from the database, hiding it first if appropriate.
 * @param $postid
 */
function ilya_post_delete($postid)
{
	$oldpost = ilya_post_get_full($postid, 'QAC');

	if (!$oldpost['hidden']) {
		ilya_post_set_status($postid, ILYA__POST_STATUS_HIDDEN, null);
		$oldpost = ilya_post_get_full($postid, 'QAC');
	}

	switch ($oldpost['basetype']) {
		case 'Q':
			$answers = ilya_post_get_question_answers($postid);
			$commentsfollows = ilya_post_get_question_commentsfollows($postid);
			$closepost = ilya_post_get_question_closepost($postid);

			if (count($answers) || count($commentsfollows))
				ilya_fatal_error('Could not delete question ID due to dependents: ' . $postid);

			ilya_question_delete($oldpost, null, null, null, $closepost);
			break;

		case 'A':
			$question = ilya_post_get_full($oldpost['parentid'], 'Q');
			$commentsfollows = ilya_post_get_answer_commentsfollows($postid);

			if (count($commentsfollows))
				ilya_fatal_error('Could not delete answer ID due to dependents: ' . $postid);

			ilya_answer_delete($oldpost, $question, null, null, null);
			break;

		case 'C':
			$parent = ilya_post_get_full($oldpost['parentid'], 'QA');
			$question = ilya_post_parent_to_question($parent);
			ilya_comment_delete($oldpost, $question, $parent, null, null, null);
			break;
	}
}


/**
 * Return the full information from the database for $postid in an array.
 * @param $postid
 * @param $requiredbasetypes
 * @return array|mixed
 */
function ilya_post_get_full($postid, $requiredbasetypes = null)
{
	$post = ilya_db_single_select(ilya_db_full_post_selectspec(null, $postid));

	if (!is_array($post))
		ilya_fatal_error('Post ID could not be found: ' . $postid);

	if (isset($requiredbasetypes) && !is_numeric(strpos($requiredbasetypes, $post['basetype'])))
		ilya_fatal_error('Post of wrong type: ' . $post['basetype']);

	return $post;
}


/**
 * Return the handle corresponding to $userid, unless it is null in which case return null.
 *
 * @deprecated Deprecated from 1.7; use `ilya_userid_to_handle($userid)` instead.
 * @param $userid
 * @return mixed|null
 */
function ilya_post_userid_to_handle($userid)
{
	return ilya_userid_to_handle($userid);
}


/**
 * Return the textual rendition of $content in $format (used for indexing).
 * @param $content
 * @param $format
 * @return string
 */
function ilya_post_content_to_text($content, $format)
{
	$viewer = ilya_load_viewer($content, $format);

	if (!isset($viewer))
		ilya_fatal_error('Content could not be parsed in format: ' . $format);

	return $viewer->get_text($content, $format, array());
}


/**
 * Return tagstring to store in the database based on $tags as an array or a comma-separated string.
 * @param $tags
 * @return mixed|string
 */
function ilya_post_tags_to_tagstring($tags)
{
	if (is_array($tags))
		$tags = implode(',', $tags);

	return ilya_tags_to_tagstring(array_unique(preg_split('/\s*,\s*/', ilya_strtolower(strtr($tags, '/', ' ')), -1, PREG_SPLIT_NO_EMPTY)));
}


/**
 * Return the full database records for all answers to question $questionid
 * @param $questionid
 * @return array
 */
function ilya_post_get_question_answers($questionid)
{
	$answers = array();

	$childposts = ilya_db_single_select(ilya_db_full_child_posts_selectspec(null, $questionid));

	foreach ($childposts as $postid => $post) {
		if ($post['basetype'] == 'A')
			$answers[$postid] = $post;
	}

	return $answers;
}


/**
 * Return the full database records for all comments or follow-on questions for question $questionid or its answers
 * @param $questionid
 * @return array
 */
function ilya_post_get_question_commentsfollows($questionid)
{
	$commentsfollows = array();

	list($childposts, $achildposts) = ilya_db_multi_select(array(
		ilya_db_full_child_posts_selectspec(null, $questionid),
		ilya_db_full_a_child_posts_selectspec(null, $questionid),
	));

	foreach ($childposts as $postid => $post) {
		if ($post['basetype'] == 'C')
			$commentsfollows[$postid] = $post;
	}

	foreach ($achildposts as $postid => $post) {
		if ($post['basetype'] == 'Q' || $post['basetype'] == 'C')
			$commentsfollows[$postid] = $post;
	}

	return $commentsfollows;
}


/**
 * Return the full database record for the post which closed $questionid, if there is any
 * @param $questionid
 * @return array|mixed
 */
function ilya_post_get_question_closepost($questionid)
{
	return ilya_db_single_select(ilya_db_post_close_post_selectspec($questionid));
}


/**
 * Return the full database records for all comments or follow-on questions for answer $answerid
 * @param $answerid
 * @return array
 */
function ilya_post_get_answer_commentsfollows($answerid)
{
	$commentsfollows = array();

	$childposts = ilya_db_single_select(ilya_db_full_child_posts_selectspec(null, $answerid));

	foreach ($childposts as $postid => $post) {
		if ($post['basetype'] == 'Q' || $post['basetype'] == 'C')
			$commentsfollows[$postid] = $post;
	}

	return $commentsfollows;
}


/**
 * Return $parent if it's the database record for a question, otherwise return the database record for its parent
 * @param $parent
 * @return array|mixed
 */
function ilya_post_parent_to_question($parent)
{
	if ($parent['basetype'] == 'Q')
		$question = $parent;
	else
		$question = ilya_post_get_full($parent['parentid'], 'Q');

	return $question;
}
