<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for most question listing pages, plus custom pages and plugin pages


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


/**
 * Returns the $ilya_content structure for a question list page showing $questions retrieved from the
 * database. If $pagesize is not null, it sets the max number of questions to display. If $count is
 * not null, pagination is determined by $start and $count. The page title is $sometitle unless
 * there are no questions shown, in which case it's $nonetitle. $navcategories should contain the
 * categories retrived from the database using ilya_db_category_nav_selectspec(...) for $categoryid,
 * which is the current category shown. If $categorypathprefix is set, category navigation will be
 * shown, with per-category question counts if $categoryqcount is true. The nav links will have the
 * prefix $categorypathprefix and possible extra $categoryparams. If $feedpathprefix is set, the
 * page has an RSS feed whose URL uses that prefix. If there are no links to other pages, $suggest
 * is used to suggest what the user should do. The $pagelinkparams are passed through to
 * ilya_html_page_links(...) which creates links for page 2, 3, etc..
 * @param $questions
 * @param $pagesize
 * @param $start
 * @param $count
 * @param $sometitle
 * @param $nonetitle
 * @param $navcategories
 * @param $categoryid
 * @param $categoryqcount
 * @param $categorypathprefix
 * @param $feedpathprefix
 * @param $suggest
 * @param $pagelinkparams
 * @param $categoryparams
 * @param $dummy
 * @return array
 */
function ilya_q_list_page_content($questions, $pagesize, $start, $count, $sometitle, $nonetitle,
	$navcategories, $categoryid, $categoryqcount, $categorypathprefix, $feedpathprefix, $suggest,
	$pagelinkparams = null, $categoryparams = null, $dummy = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'app/format.php';
	require_once ILYA__INCLUDE_DIR . 'app/updates.php';
	require_once ILYA__INCLUDE_DIR . 'app/posts.php';

	$userid = ilya_get_logged_in_userid();


	// Chop down to size, get user information for display

	if (isset($pagesize)) {
		$questions = array_slice($questions, 0, $pagesize);
	}

	$usershtml = ilya_userids_handles_html(ilya_any_get_userids_handles($questions));


	// Prepare content for theme

	$ilya_content = ilya_content_prepare(true, array_keys(ilya_category_path($navcategories, $categoryid)));

	$ilya_content['q_list']['form'] = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'hidden' => array(
			'code' => ilya_get_form_security_code('vote'),
		),
	);

	$ilya_content['q_list']['qs'] = array();

	if (!empty($questions)) {
		$ilya_content['title'] = $sometitle;

		$defaults = ilya_post_html_defaults('Q');
		if (isset($categorypathprefix)) {
			$defaults['categorypathprefix'] = $categorypathprefix;
		}

		foreach ($questions as $question) {
			$fields = ilya_any_to_q_html_fields($question, $userid, ilya_cookie_get(), $usershtml, null, ilya_post_html_options($question, $defaults));

			if (ilya_post_is_closed($question)) {
				$fields['closed'] = array(
					'state' => ilya_lang_html('main/closed'),
				);
			}

			$ilya_content['q_list']['qs'][] = $fields;
		}
	} else {
		$ilya_content['title'] = $nonetitle;
	}

	if (isset($userid) && isset($categoryid)) {
		$favoritemap = ilya_get_favorite_non_qs_map();
		$categoryisfavorite = @$favoritemap['category'][$navcategories[$categoryid]['backpath']];

		$ilya_content['favorite'] = ilya_favorite_form(ILYA__ENTITY_CATEGORY, $categoryid, $categoryisfavorite,
			ilya_lang_sub($categoryisfavorite ? 'main/remove_x_favorites' : 'main/add_category_x_favorites', $navcategories[$categoryid]['title']));
	}

	if (isset($count) && isset($pagesize)) {
		$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $count, ilya_opt('pages_prev_next'), $pagelinkparams);
	}

	$ilya_content['canonical'] = ilya_get_canonical();

	if (empty($ilya_content['page_links'])) {
		$ilya_content['suggest_next'] = $suggest;
	}

	if (ilya_using_categories() && count($navcategories) && isset($categorypathprefix)) {
		$ilya_content['navigation']['cat'] = ilya_category_navigation($navcategories, $categoryid, $categorypathprefix, $categoryqcount, $categoryparams);
	}

	// set meta description on category pages
	if (!empty($navcategories[$categoryid]['content'])) {
		$ilya_content['description'] = ilya_html($navcategories[$categoryid]['content']);
	}

	if (isset($feedpathprefix) && (ilya_opt('feed_per_category') || !isset($categoryid))) {
		$ilya_content['feed'] = array(
			'url' => ilya_path_html(ilya_feed_request($feedpathprefix . (isset($categoryid) ? ('/' . ilya_category_path_request($navcategories, $categoryid)) : ''))),
			'label' => strip_tags($sometitle),
		);
	}

	return $ilya_content;
}


/**
 * Return the sub navigation structure common to question listing pages
 * @param $sort
 * @param $categoryslugs
 * @return array
 */
function ilya_qs_sub_navigation($sort, $categoryslugs)
{
	$request = 'questions';

	if (isset($categoryslugs)) {
		foreach ($categoryslugs as $slug) {
			$request .= '/' . $slug;
		}
	}

	$navigation = array(
		'recent' => array(
			'label' => ilya_lang('main/nav_most_recent'),
			'url' => ilya_path_html($request),
		),

		'hot' => array(
			'label' => ilya_lang('main/nav_hot'),
			'url' => ilya_path_html($request, array('sort' => 'hot')),
		),

		'votes' => array(
			'label' => ilya_lang('main/nav_most_votes'),
			'url' => ilya_path_html($request, array('sort' => 'votes')),
		),

		'answers' => array(
			'label' => ilya_lang('main/nav_most_answers'),
			'url' => ilya_path_html($request, array('sort' => 'answers')),
		),

		'views' => array(
			'label' => ilya_lang('main/nav_most_views'),
			'url' => ilya_path_html($request, array('sort' => 'views')),
		),
	);

	if (isset($navigation[$sort])) {
		$navigation[$sort]['selected'] = true;
	} else {
		$navigation['recent']['selected'] = true;
	}

	if (!ilya_opt('do_count_q_views')) {
		unset($navigation['views']);
	}

	return $navigation;
}


/**
 * Return the sub navigation structure common to unanswered pages
 * @param $by
 * @param $categoryslugs
 * @return array
 */
function ilya_unanswered_sub_navigation($by, $categoryslugs)
{
	$request = 'unanswered';

	if (isset($categoryslugs)) {
		foreach ($categoryslugs as $slug) {
			$request .= '/' . $slug;
		}
	}

	$navigation = array(
		'by-answers' => array(
			'label' => ilya_lang('main/nav_no_answer'),
			'url' => ilya_path_html($request),
		),

		'by-selected' => array(
			'label' => ilya_lang('main/nav_no_selected_answer'),
			'url' => ilya_path_html($request, array('by' => 'selected')),
		),

		'by-upvotes' => array(
			'label' => ilya_lang('main/nav_no_upvoted_answer'),
			'url' => ilya_path_html($request, array('by' => 'upvotes')),
		),
	);

	if (isset($navigation['by-' . $by])) {
		$navigation['by-' . $by]['selected'] = true;
	} else {
		$navigation['by-answers']['selected'] = true;
	}

	if (!ilya_opt('voting_on_as')) {
		unset($navigation['by-upvotes']);
	}

	return $navigation;
}
