<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for page listing recent updates for a user


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

require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';
require_once ILYA__INCLUDE_DIR . 'app/q-list.php';


// Check that we're logged in

$userid = ilya_get_logged_in_userid();

if (!isset($userid))
	ilya_redirect('login');


// Find out which updates to show

$forfavorites = ilya_get('show') != 'content';
$forcontent = ilya_get('show') != 'favorites';


// Get lists of recent updates for this user

$questions = ilya_db_select_with_pending(
	ilya_db_user_updates_selectspec($userid, $forfavorites, $forcontent)
);

if ($forfavorites) {
	if ($forcontent) {
		$sometitle = ilya_lang_html('misc/recent_updates_title');
		$nonetitle = ilya_lang_html('misc/no_recent_updates');

	} else {
		$sometitle = ilya_lang_html('misc/recent_updates_favorites');
		$nonetitle = ilya_lang_html('misc/no_updates_favorites');
	}

} else {
	$sometitle = ilya_lang_html('misc/recent_updates_content');
	$nonetitle = ilya_lang_html('misc/no_updates_content');
}


// Prepare and return content for theme

$ilya_content = ilya_q_list_page_content(
	ilya_any_sort_and_dedupe($questions),
	null, // questions per page
	0, // start offset
	null, // total count (null to hide page links)
	$sometitle, // title if some questions
	$nonetitle, // title if no questions
	array(), // categories for navigation
	null, // selected category id
	null, // show question counts in category navigation
	null, // prefix for links in category navigation
	null, // prefix for RSS feed paths (null to hide)
	$forfavorites ? strtr(ilya_lang_html('misc/suggest_update_favorites'), array(
		'^1' => '<a href="' . ilya_path_html('favorites') . '">',
		'^2' => '</a>',
	)) : null // suggest what to do next
);

$ilya_content['navigation']['sub'] = array(
	'all' => array(
		'label' => ilya_lang_html('misc/nav_all_my_updates'),
		'url' => ilya_path_html('updates'),
		'selected' => $forfavorites && $forcontent,
	),

	'favorites' => array(
		'label' => ilya_lang_html('misc/nav_my_favorites'),
		'url' => ilya_path_html('updates', array('show' => 'favorites')),
		'selected' => $forfavorites && !$forcontent,
	),

	'myposts' => array(
		'label' => ilya_lang_html('misc/nav_my_content'),
		'url' => ilya_path_html('updates', array('show' => 'content')),
		'selected' => $forcontent && !$forfavorites,
	),
);


return $ilya_content;
