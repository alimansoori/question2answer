<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for page for a specific tag


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

require_once ILYA_INCLUDE_DIR . 'db/selects.php';
require_once ILYA_INCLUDE_DIR . 'app/format.php';
require_once ILYA_INCLUDE_DIR . 'app/updates.php';

$tag = ilya_request_part(1); // picked up from ilya-page.php
$start = ilya_get_start();
$userid = ilya_get_logged_in_userid();


// Find the questions with this tag

if (!strlen($tag)) {
	ilya_redirect('tags');
}

list($questions, $tagword) = ilya_db_select_with_pending(
	ilya_db_tag_recent_qs_selectspec($userid, $tag, $start, false, ilya_opt_if_loaded('page_size_tag_qs')),
	ilya_db_tag_word_selectspec($tag)
);

$pagesize = ilya_opt('page_size_tag_qs');
$questions = array_slice($questions, 0, $pagesize);
$usershtml = ilya_userids_handles_html($questions);


// Prepare content for theme

$ilya_content = ilya_content_prepare(true);

$ilya_content['title'] = ilya_lang_html_sub('main/questions_tagged_x', ilya_html($tag));

if (isset($userid) && isset($tagword)) {
	$favoritemap = ilya_get_favorite_non_qs_map();
	$favorite = @$favoritemap['tag'][ilya_strtolower($tagword['word'])];

	$ilya_content['favorite'] = ilya_favorite_form(ILYA_ENTITY_TAG, $tagword['wordid'], $favorite,
		ilya_lang_sub($favorite ? 'main/remove_x_favorites' : 'main/add_tag_x_favorites', $tagword['word']));
}

if (!count($questions))
	$ilya_content['q_list']['title'] = ilya_lang_html('main/no_questions_found');

$ilya_content['q_list']['form'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '"',

	'hidden' => array(
		'code' => ilya_get_form_security_code('vote'),
	),
);

$ilya_content['q_list']['qs'] = array();
foreach ($questions as $postid => $question) {
	$ilya_content['q_list']['qs'][] =
		ilya_post_html_fields($question, $userid, ilya_cookie_get(), $usershtml, null, ilya_post_html_options($question));
}

$ilya_content['canonical'] = ilya_get_canonical();

$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $tagword['tagcount'], ilya_opt('pages_prev_next'));

if (empty($ilya_content['page_links']))
	$ilya_content['suggest_next'] = ilya_html_suggest_qs_tags(true);

if (ilya_opt('feed_for_tag_qs')) {
	$ilya_content['feed'] = array(
		'url' => ilya_path_html(ilya_feed_request('tag/' . $tag)),
		'label' => ilya_lang_html_sub('main/questions_tagged_x', ilya_html($tag)),
	);
}


return $ilya_content;
