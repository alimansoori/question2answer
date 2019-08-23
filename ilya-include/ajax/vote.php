<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax voting requests


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

require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'app/cookies.php';
require_once ILYA__INCLUDE_DIR . 'app/votes.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';
require_once ILYA__INCLUDE_DIR . 'app/options.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';


$postid = ilya_post_text('postid');
$vote = ilya_post_text('vote');
$code = ilya_post_text('code');

$userid = ilya_get_logged_in_userid();
$cookieid = ilya_cookie_get();

if (!ilya_check_form_security_code('vote', $code)) {
	$voteerror = ilya_lang_html('misc/form_security_reload');
} else {
	$post = ilya_db_select_with_pending(ilya_db_full_post_selectspec($userid, $postid));
	$voteerror = ilya_vote_error_html($post, $vote, $userid, ilya_request());
}

if ($voteerror === false) {
	ilya_vote_set($post, $userid, ilya_get_logged_in_handle(), $cookieid, $vote);

	$post = ilya_db_select_with_pending(ilya_db_full_post_selectspec($userid, $postid));

	$fields = ilya_post_html_fields($post, $userid, $cookieid, array(), null, array(
		'voteview' => ilya_get_vote_view($post, true), // behave as if on question page since the vote succeeded
	));

	$themeclass = ilya_load_theme_class(ilya_get_site_theme(), 'voting', null, null);
	$themeclass->initialize();

	echo "ILYA__AJAX_RESPONSE\n1\n";
	$themeclass->voting_inner_html($fields);

	return;

}

echo "ILYA__AJAX_RESPONSE\n0\n" . $voteerror;
