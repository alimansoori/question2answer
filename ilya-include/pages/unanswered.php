<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for page listing recent questions without upvoted/selected/any answers


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
require_once ILYA_INCLUDE_DIR . 'app/q-list.php';


// Get list of unanswered questions, allow per-category if ILYA_ALLOW_UNINDEXED_QUERIES set in ilya-config.php

if (ILYA_ALLOW_UNINDEXED_QUERIES)
	$categoryslugs = ilya_request_parts(1);
else
	$categoryslugs = null;

$countslugs = @count($categoryslugs);
$by = ilya_get('by');
$start = ilya_get_start();
$userid = ilya_get_logged_in_userid();

switch ($by) {
	case 'selected':
		$selectby = 'selchildid';
		break;

	case 'upvotes':
		$selectby = 'amaxvote';
		break;

	default:
		$selectby = 'acount';
		break;
}

list($questions, $categories, $categoryid) = ilya_db_select_with_pending(
	ilya_db_unanswered_qs_selectspec($userid, $selectby, $start, $categoryslugs, false, false, ilya_opt_if_loaded('page_size_una_qs')),
	ILYA_ALLOW_UNINDEXED_QUERIES ? ilya_db_category_nav_selectspec($categoryslugs, false, false, true) : null,
	$countslugs ? ilya_db_slugs_to_category_id_selectspec($categoryslugs) : null
);

if ($countslugs) {
	if (!isset($categoryid))
		return include ILYA_INCLUDE_DIR . 'ilya-page-not-found.php';

	$categorytitlehtml = ilya_html($categories[$categoryid]['title']);
}

$feedpathprefix = null;
$linkparams = array('by' => $by);

switch ($by) {
	case 'selected':
		if ($countslugs) {
			$sometitle = ilya_lang_html_sub('main/unselected_qs_in_x', $categorytitlehtml);
			$nonetitle = ilya_lang_html_sub('main/no_una_questions_in_x', $categorytitlehtml);

		} else {
			$sometitle = ilya_lang_html('main/unselected_qs_title');
			$nonetitle = ilya_lang_html('main/no_unselected_qs_found');
			$count = ilya_opt('cache_unselqcount');
		}
		break;

	case 'upvotes':
		if ($countslugs) {
			$sometitle = ilya_lang_html_sub('main/unupvoteda_qs_in_x', $categorytitlehtml);
			$nonetitle = ilya_lang_html_sub('main/no_una_questions_in_x', $categorytitlehtml);

		} else {
			$sometitle = ilya_lang_html('main/unupvoteda_qs_title');
			$nonetitle = ilya_lang_html('main/no_unupvoteda_qs_found');
			$count = ilya_opt('cache_unupaqcount');
		}
		break;

	default:
		$feedpathprefix = ilya_opt('feed_for_unanswered') ? 'unanswered' : null;
		$linkparams = array();

		if ($countslugs) {
			$sometitle = ilya_lang_html_sub('main/unanswered_qs_in_x', $categorytitlehtml);
			$nonetitle = ilya_lang_html_sub('main/no_una_questions_in_x', $categorytitlehtml);

		} else {
			$sometitle = ilya_lang_html('main/unanswered_qs_title');
			$nonetitle = ilya_lang_html('main/no_una_questions_found');
			$count = ilya_opt('cache_unaqcount');
		}
		break;
}


// Prepare and return content for theme

$ilya_content = ilya_q_list_page_content(
	$questions, // questions
	ilya_opt('page_size_una_qs'), // questions per page
	$start, // start offset
	@$count, // total count
	$sometitle, // title if some questions
	$nonetitle, // title if no questions
	ILYA_ALLOW_UNINDEXED_QUERIES ? $categories : array(), // categories for navigation (null if not shown on this page)
	ILYA_ALLOW_UNINDEXED_QUERIES ? $categoryid : null, // selected category id (null if not relevant)
	false, // show question counts in category navigation
	ILYA_ALLOW_UNINDEXED_QUERIES ? 'unanswered/' : null, // prefix for links in category navigation (null if no navigation)
	$feedpathprefix, // prefix for RSS feed paths (null to hide)
	ilya_html_suggest_qs_tags(ilya_using_tags()), // suggest what to do next
	$linkparams, // extra parameters for page links
	$linkparams // category nav params
);

$ilya_content['navigation']['sub'] = ilya_unanswered_sub_navigation($by, $categoryslugs);


return $ilya_content;
