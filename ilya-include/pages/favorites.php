<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for page listing user's favorites


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
require_once ILYA__INCLUDE_DIR . 'app/favorites.php';


// Check that we're logged in

$userid = ilya_get_logged_in_userid();

if (!isset($userid))
	ilya_redirect('login');


// Get lists of favorites for this user

$pagesize_qs = ilya_opt('page_size_qs');
$pagesize_users = ilya_opt('page_size_users');
$pagesize_tags = ilya_opt('page_size_tags');

list($numQs, $questions, $numUsers, $users, $numTags, $tags, $categories) = ilya_db_select_with_pending(
	ilya_db_selectspec_count(ilya_db_user_favorite_qs_selectspec($userid)),
	ilya_db_user_favorite_qs_selectspec($userid, $pagesize_qs),

	ILYA__FINAL_EXTERNAL_USERS ? null : ilya_db_selectspec_count(ilya_db_user_favorite_users_selectspec($userid)),
	ILYA__FINAL_EXTERNAL_USERS ? null : ilya_db_user_favorite_users_selectspec($userid, $pagesize_users),

	ilya_db_selectspec_count(ilya_db_user_favorite_tags_selectspec($userid)),
	ilya_db_user_favorite_tags_selectspec($userid, $pagesize_tags),

	ilya_db_user_favorite_categories_selectspec($userid)
);

$usershtml = ilya_userids_handles_html(ILYA__FINAL_EXTERNAL_USERS ? $questions : array_merge($questions, $users));


// Prepare and return content for theme

$ilya_content = ilya_content_prepare(true);

$ilya_content['title'] = ilya_lang_html('misc/my_favorites_title');


// Favorite questions

$ilya_content['q_list'] = ilya_favorite_q_list_view($questions, $usershtml);
$ilya_content['q_list']['title'] = count($questions) ? ilya_lang_html('main/nav_qs') : ilya_lang_html('misc/no_favorite_qs');
if ($numQs['count'] > count($questions)) {
	$url = ilya_path_html('favorites/questions', array('start' => $pagesize_qs));
	$ilya_content['q_list']['footer'] = '<p class="ilya-link-next"><a href="' . $url . '">' . ilya_lang_html('misc/more_favorite_qs') . '</a></p>';
}


// Favorite users

if (!ILYA__FINAL_EXTERNAL_USERS) {
	$ilya_content['ranking_users'] = ilya_favorite_users_view($users, $usershtml);
	$ilya_content['ranking_users']['title'] = count($users) ? ilya_lang_html('main/nav_users') : ilya_lang_html('misc/no_favorite_users');
	if ($numUsers['count'] > count($users)) {
		$url = ilya_path_html('favorites/users', array('start' => $pagesize_users));
		$ilya_content['ranking_users']['footer'] = '<p class="ilya-link-next"><a href="' . $url . '">' . ilya_lang_html('misc/more_favorite_users') . '</a></p>';
	}
}


// Favorite tags

if (ilya_using_tags()) {
	$ilya_content['ranking_tags'] = ilya_favorite_tags_view($tags);
	$ilya_content['ranking_tags']['title'] = count($tags) ? ilya_lang_html('main/nav_tags') : ilya_lang_html('misc/no_favorite_tags');
	if ($numTags['count'] > count($tags)) {
		$url = ilya_path_html('favorites/tags', array('start' => $pagesize_tags));
		$ilya_content['ranking_tags']['footer'] = '<p class="ilya-link-next"><a href="' . $url . '">' . ilya_lang_html('misc/more_favorite_tags') . '</a></p>';
	}
}


// Favorite categories (no pagination)

if (ilya_using_categories()) {
	$ilya_content['nav_list_categories'] = ilya_favorite_categories_view($categories);
	$ilya_content['nav_list_categories']['title'] = count($categories) ? ilya_lang_html('main/nav_categories') : ilya_lang_html('misc/no_favorite_categories');
}


// Sub navigation for account pages and suggestion

$ilya_content['suggest_next'] = ilya_lang_html_sub('misc/suggest_favorites_add', '<span class="ilya-favorite-image">&nbsp;</span>');

$ilya_content['navigation']['sub'] = ilya_user_sub_navigation(ilya_get_logged_in_handle(), 'favorites', true);


return $ilya_content;
