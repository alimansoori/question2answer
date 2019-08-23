<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Server-side response to Ajax wall post requests


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

require_once ILYA__INCLUDE_DIR . 'app/messages.php';
require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'app/cookies.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';


$message = ilya_post_text('message');
$tohandle = ilya_post_text('handle');
$morelink = ilya_post_text('morelink');

$touseraccount = ilya_db_select_with_pending(ilya_db_user_account_selectspec($tohandle, false));
$loginuserid = ilya_get_logged_in_userid();

$errorhtml = ilya_wall_error_html($loginuserid, $touseraccount['userid'], $touseraccount['flags']);

if ($errorhtml || !strlen($message) || !ilya_check_form_security_code('wall-' . $tohandle, ilya_post_text('code'))) {
	echo "ILYA__AJAX_RESPONSE\n0"; // if there's an error, process in non-Ajax way
} else {
	$messageid = ilya_wall_add_post($loginuserid, ilya_get_logged_in_handle(), ilya_cookie_get(),
		$touseraccount['userid'], $touseraccount['handle'], $message, '');
	$touseraccount['wallposts']++; // won't have been updated

	$usermessages = ilya_db_select_with_pending(ilya_db_recent_messages_selectspec(null, null, $touseraccount['userid'], true, ilya_opt('page_size_wall')));
	$usermessages = ilya_wall_posts_add_rules($usermessages, 0);

	$themeclass = ilya_load_theme_class(ilya_get_site_theme(), 'wall', null, null);
	$themeclass->initialize();

	echo "ILYA__AJAX_RESPONSE\n1\n";

	echo 'm' . $messageid . "\n"; // element in list to be revealed

	foreach ($usermessages as $message) {
		$themeclass->message_item(ilya_wall_post_view($message));
	}

	if ($morelink && ($touseraccount['wallposts'] > count($usermessages)))
		$themeclass->message_item(ilya_wall_view_more_link($tohandle, count($usermessages)));
}
