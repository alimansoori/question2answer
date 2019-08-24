<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for page listing hot questions


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
require_once ILYA_INCLUDE_DIR . 'app/q-list.php';


// Get list of hottest questions, allow per-category if ILYA_ALLOW_UNINDEXED_QUERIES set in ilya-config.php

$categoryslugs = ILYA_ALLOW_UNINDEXED_QUERIES ? ilya_request_parts(1) : null;
$countslugs = @count($categoryslugs);

$start = ilya_get_start();
$userid = ilya_get_logged_in_userid();

list($questions, $categories, $categoryid) = ilya_db_select_with_pending(
	ilya_db_qs_selectspec($userid, 'hotness', $start, $categoryslugs, null, false, false, ilya_opt_if_loaded('page_size_hot_qs')),
	ilya_db_category_nav_selectspec($categoryslugs, false, false, true),
	$countslugs ? ilya_db_slugs_to_category_id_selectspec($categoryslugs) : null
);

if ($countslugs) {
	if (!isset($categoryid))
		return include ILYA_INCLUDE_DIR . 'ilya-page-not-found.php';

	$categorytitlehtml = ilya_html($categories[$categoryid]['title']);
	$sometitle = ilya_lang_html_sub('main/hot_qs_in_x', $categorytitlehtml);
	$nonetitle = ilya_lang_html_sub('main/no_questions_in_x', $categorytitlehtml);

} else {
	$sometitle = ilya_lang_html('main/hot_qs_title');
	$nonetitle = ilya_lang_html('main/no_questions_found');
}


// Prepare and return content for theme

return ilya_q_list_page_content(
	$questions, // questions
	ilya_opt('page_size_hot_qs'), // questions per page
	$start, // start offset
	$countslugs ? $categories[$categoryid]['qcount'] : ilya_opt('cache_qcount'), // total count
	$sometitle, // title if some questions
	$nonetitle, // title if no questions
	ILYA_ALLOW_UNINDEXED_QUERIES ? $categories : array(), // categories for navigation
	$categoryid, // selected category id
	true, // show question counts in category navigation
	ILYA_ALLOW_UNINDEXED_QUERIES ? 'hot/' : null, // prefix for links in category navigation (null if no navigation)
	ilya_opt('feed_for_hot') ? 'hot' : null, // prefix for RSS feed paths (null to hide)
	ilya_html_suggest_ask() // suggest what to do next
);
