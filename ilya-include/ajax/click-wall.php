<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax single clicks on wall posts


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

require_once ILYA__INCLUDE_DIR . 'app/messages.php';
require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'app/cookies.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';


$tohandle = ilya_post_text('handle');
$start = (int)ilya_post_text('start');

$usermessages = ilya_db_select_with_pending(ilya_db_recent_messages_selectspec(null, null, $tohandle, false, null, $start));
$usermessages = ilya_wall_posts_add_rules($usermessages, $start);

foreach ($usermessages as $message) {
	if (ilya_clicked('m' . $message['messageid'] . '_dodelete') && $message['deleteable']) {
		if (ilya_check_form_security_code('wall-' . $tohandle, ilya_post_text('code'))) {
			ilya_wall_delete_post(ilya_get_logged_in_userid(), ilya_get_logged_in_handle(), ilya_cookie_get(), $message);
			echo "ILYA__AJAX_RESPONSE\n1\n";
			return;
		}
	}
}

echo "ILYA__AJAX_RESPONSE\n0\n";
