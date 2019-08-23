<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for page listing categories


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

require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';


$categoryslugs = ilya_request_parts(1);
$countslugs = count($categoryslugs);


// Get information about appropriate categories and redirect to questions page if category has no sub-categories

$userid = ilya_get_logged_in_userid();
list($categories, $categoryid, $favoritecats) = ilya_db_select_with_pending(
	ilya_db_category_nav_selectspec($categoryslugs, false, false, true),
	$countslugs ? ilya_db_slugs_to_category_id_selectspec($categoryslugs) : null,
	isset($userid) ? ilya_db_user_favorite_categories_selectspec($userid) : null
);

if ($countslugs && !isset($categoryid)) {
	return include ILYA__INCLUDE_DIR . 'ilya-page-not-found.php';
}


// Function for recursive display of categories

function ilya_category_nav_to_browse(&$navigation, $categories, $categoryid, $favoritemap)
{
	foreach ($navigation as $key => $navlink) {
		$category = $categories[$navlink['categoryid']];

		if (!$category['childcount']) {
			unset($navigation[$key]['url']);
		} elseif ($navlink['selected']) {
			$navigation[$key]['state'] = 'open';
			$navigation[$key]['url'] = ilya_path_html('categories/' . ilya_category_path_request($categories, $category['parentid']));
		} else
			$navigation[$key]['state'] = 'closed';

		if (@$favoritemap[$navlink['categoryid']]) {
			$navigation[$key]['favorited'] = true;
		}

		$navigation[$key]['note'] =
			' - <a href="'.ilya_path_html('questions/'.implode('/', array_reverse(explode('/', $category['backpath'])))).'">'.( ($category['qcount']==1)
				? ilya_lang_html_sub('main/1_question', '1', '1')
				: ilya_lang_html_sub('main/x_questions', number_format($category['qcount']))
			).'</a>';

		if (strlen($category['content']))
			$navigation[$key]['note'] .= ilya_html(' - ' . $category['content']);

		if (isset($navlink['subnav']))
			ilya_category_nav_to_browse($navigation[$key]['subnav'], $categories, $categoryid, $favoritemap);
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare(false, array_keys(ilya_category_path($categories, $categoryid)));

$ilya_content['title'] = ilya_lang_html('misc/browse_categories');

if (count($categories)) {
	$navigation = ilya_category_navigation($categories, $categoryid, 'categories/', false);

	unset($navigation['all']);

	$favoritemap = array();
	if (isset($favoritecats)) {
		foreach ($favoritecats as $category) {
			$favoritemap[$category['categoryid']] = true;
		}
	}

	ilya_category_nav_to_browse($navigation, $categories, $categoryid, $favoritemap);

	$ilya_content['nav_list'] = array(
		'nav' => $navigation,
		'type' => 'browse-cat',
	);

} else {
	$ilya_content['title'] = ilya_lang_html('main/no_categories_found');
	$ilya_content['suggest_next'] = ilya_html_suggest_qs_tags(ilya_using_tags());
}


return $ilya_content;
