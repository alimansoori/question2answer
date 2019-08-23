<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Handles favoriting and unfavoriting (application level)


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
 * Set an entity to be favorited or removed from favorites. Handles event reporting.
 *
 * @param int $userid ID of user assigned to the favorite
 * @param string $handle Username of user
 * @param string $cookieid Cookie ID of user
 * @param string $entitytype Entity type code (one of ILYA__ENTITY_* constants)
 * @param string $entityid ID of the entity being favorited (e.g. postid for questions)
 * @param bool $favorite Whether to add favorite (true) or remove favorite (false)
 */
function ilya_user_favorite_set($userid, $handle, $cookieid, $entitytype, $entityid, $favorite)
{
	require_once ILYA__INCLUDE_DIR . 'db/favorites.php';
	require_once ILYA__INCLUDE_DIR . 'app/limits.php';
	require_once ILYA__INCLUDE_DIR . 'app/updates.php';

	// Make sure the user is not favoriting themselves
	if ($entitytype == ILYA__ENTITY_USER && $userid == $entityid) {
		return;
	}

	if ($favorite)
		ilya_db_favorite_create($userid, $entitytype, $entityid);
	else
		ilya_db_favorite_delete($userid, $entitytype, $entityid);

	switch ($entitytype) {
		case ILYA__ENTITY_QUESTION:
			$action = $favorite ? 'q_favorite' : 'q_unfavorite';
			$params = array('postid' => $entityid);
			break;

		case ILYA__ENTITY_USER:
			$action = $favorite ? 'u_favorite' : 'u_unfavorite';
			$params = array('userid' => $entityid);
			break;

		case ILYA__ENTITY_TAG:
			$action = $favorite ? 'tag_favorite' : 'tag_unfavorite';
			$params = array('wordid' => $entityid);
			break;

		case ILYA__ENTITY_CATEGORY:
			$action = $favorite ? 'cat_favorite' : 'cat_unfavorite';
			$params = array('categoryid' => $entityid);
			break;

		default:
			ilya_fatal_error('Favorite type not recognized');
			break;
	}

	ilya_report_event($action, $userid, $handle, $cookieid, $params);
}


/**
 * Returns content to set in $ilya_content['q_list'] for a user's favorite $questions. Pre-generated
 * user HTML in $usershtml.
 * @param $questions
 * @param $usershtml
 * @return array
 */
function ilya_favorite_q_list_view($questions, $usershtml)
{
	$q_list = array(
		'qs' => array(),
	);

	if (count($questions) === 0)
		return $q_list;

	$q_list['form'] = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',
		'hidden' => array(
			'code' => ilya_get_form_security_code('vote'),
		),
	);

	$defaults = ilya_post_html_defaults('Q');

	foreach ($questions as $question) {
		$q_list['qs'][] = ilya_post_html_fields($question, ilya_get_logged_in_userid(), ilya_cookie_get(),
			$usershtml, null, ilya_post_html_options($question, $defaults));
	}

	return $q_list;
}


/**
 * Returns content to set in $ilya_content['ranking_users'] for a user's favorite $users. Pre-generated
 * user HTML in $usershtml.
 * @param $users
 * @param $usershtml
 * @return array|null
 */
function ilya_favorite_users_view($users, $usershtml)
{
	if (ILYA__FINAL_EXTERNAL_USERS)
		return null;

	require_once ILYA__INCLUDE_DIR . 'app/users.php';
	require_once ILYA__INCLUDE_DIR . 'app/format.php';

	$ranking = array(
		'items' => array(),
		'rows' => ceil(count($users) / ilya_opt('columns_users')),
		'type' => 'users',
	);

	foreach ($users as $user) {
		$avatarhtml = ilya_get_user_avatar_html($user['flags'], $user['email'], $user['handle'],
			$user['avatarblobid'], $user['avatarwidth'], $user['avatarheight'], ilya_opt('avatar_users_size'), true);

		$ranking['items'][] = array(
			'avatar' => $avatarhtml,
			'label' => $usershtml[$user['userid']],
			'score' => ilya_html(ilya_format_number($user['points'], 0, true)),
			'raw' => $user,
		);
	}

	return $ranking;
}


/**
 * Returns content to set in $ilya_content['ranking_tags'] for a user's favorite $tags.
 * @param $tags
 * @return array
 */
function ilya_favorite_tags_view($tags)
{
	require_once ILYA__INCLUDE_DIR . 'app/format.php';

	$ranking = array(
		'items' => array(),
		'rows' => ceil(count($tags) / ilya_opt('columns_tags')),
		'type' => 'tags',
	);

	foreach ($tags as $tag) {
		$ranking['items'][] = array(
			'label' => ilya_tag_html($tag['word'], false, true),
			'count' => ilya_html(ilya_format_number($tag['tagcount'], 0, true)),
		);
	}

	return $ranking;
}


/**
 * Returns content to set in $ilya_content['nav_list_categories'] for a user's favorite $categories.
 * @param $categories
 * @return array
 */
function ilya_favorite_categories_view($categories)
{
	require_once ILYA__INCLUDE_DIR . 'app/format.php';

	$nav_list_categories = array(
		'nav' => array(),
		'type' => 'browse-cat',
	);

	foreach ($categories as $category) {
		$cat_url = ilya_path_html('questions/' . implode('/', array_reverse(explode('/', $category['backpath']))));
		$cat_anchor = $category['qcount'] == 1
			? ilya_lang_html_sub('main/1_question', '1', '1')
			: ilya_lang_html_sub('main/x_questions', ilya_format_number($category['qcount'], 0, true));
		$cat_descr = strlen($category['content']) ? ilya_html(' - ' . $category['content']) : '';

		$nav_list_categories['nav'][$category['categoryid']] = array(
			'label' => ilya_html($category['title']),
			'state' => 'open',
			'favorited' => true,
			'note' => ' - <a href="' . $cat_url . '">' . $cat_anchor . '</a>' . $cat_descr,
		);
	}

	return $nav_list_categories;
}
