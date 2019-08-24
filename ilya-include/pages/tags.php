<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for popular tags page


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


// Get popular tags

$start = ilya_get_start();
$userid = ilya_get_logged_in_userid();
$populartags = ilya_db_select_with_pending(
	ilya_db_popular_tags_selectspec($start, ilya_opt_if_loaded('page_size_tags'))
);

$tagcount = ilya_opt('cache_tagcount');
$pagesize = ilya_opt('page_size_tags');


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('main/popular_tags');

$ilya_content['ranking'] = array(
	'items' => array(),
	'rows' => ceil($pagesize / ilya_opt('columns_tags')),
	'type' => 'tags',
	'sort' => 'count',
);

if (count($populartags)) {
	$favoritemap = ilya_get_favorite_non_qs_map();

	$output = 0;
	foreach ($populartags as $word => $count) {
		$ilya_content['ranking']['items'][] = array(
			'label' => ilya_tag_html($word, false, @$favoritemap['tag'][ilya_strtolower($word)]),
			'count' => ilya_format_number($count, 0, true),
		);

		if ((++$output) >= $pagesize) {
			break;
		}
	}
} else {
	$ilya_content['title'] = ilya_lang_html('main/no_tags_found');
}

$ilya_content['canonical'] = ilya_get_canonical();

$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $tagcount, ilya_opt('pages_prev_next'));

if (empty($ilya_content['page_links'])) {
	$ilya_content['suggest_next'] = ilya_html_suggest_ask();
}


return $ilya_content;
