<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for sub-page listing user's favorites of a certain type


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'app/format.php';
require_once QA_INCLUDE_DIR . 'app/favorites.php';


// Data for functions to run

$favswitch = array(
	'questions' => array(
		'page_opt' => 'page_size_qs',
		'fn_spec' => 'ilya_db_user_favorite_qs_selectspec',
		'fn_view' => 'ilya_favorite_q_list_view',
		'key' => 'q_list',
	),
	'users' => array(
		'page_opt' => 'page_size_users',
		'fn_spec' => 'ilya_db_user_favorite_users_selectspec',
		'fn_view' => 'ilya_favorite_users_view',
		'key' => 'ranking_users',
	),
	'tags' => array(
		'page_opt' => 'page_size_tags',
		'fn_spec' => 'ilya_db_user_favorite_tags_selectspec',
		'fn_view' => 'ilya_favorite_tags_view',
		'key' => 'ranking_tags',
	),
);


// Check that we're logged in

$userid = ilya_get_logged_in_userid();

if (!isset($userid))
	ilya_redirect('login');


// Get lists of favorites of this type

$favtype = ilya_request_part(1);
$start = ilya_get_start();

if (!array_key_exists($favtype, $favswitch) || ($favtype === 'users' && QA_FINAL_EXTERNAL_USERS))
	return include QA_INCLUDE_DIR . 'ilya-page-not-found.php';

extract($favswitch[$favtype]); // get switch variables

$pagesize = ilya_opt($page_opt);
list($totalItems, $items) = ilya_db_select_with_pending(
	ilya_db_selectspec_count($fn_spec($userid)),
	$fn_spec($userid, $pagesize, $start)
);

$count = $totalItems['count'];
$usershtml = ilya_userids_handles_html($items);


// Prepare and return content for theme

$ilya_content = ilya_content_prepare(true);

$ilya_content['title'] = ilya_lang_html('misc/my_favorites_title');

$ilya_content[$key] = $fn_view($items, $usershtml);


// Sub navigation for account pages and suggestion

$ilya_content['suggest_next'] = ilya_lang_html_sub('misc/suggest_favorites_add', '<span class="ilya-favorite-image">&nbsp;</span>');

$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $count, ilya_opt('pages_prev_next'));

$ilya_content['navigation']['sub'] = ilya_user_sub_navigation(ilya_get_logged_in_handle(), 'favorites', true);


return $ilya_content;
