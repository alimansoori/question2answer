<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax requests to close a notice


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

require_once ILYA_INCLUDE_DIR . 'app/users.php';
require_once ILYA_INCLUDE_DIR . 'db/notices.php';
require_once ILYA_INCLUDE_DIR . 'db/users.php';


$noticeid = ilya_post_text('noticeid');

if (!ilya_check_form_security_code('notice-' . $noticeid, ilya_post_text('code')))
	echo "ILYA_AJAX_RESPONSE\n0\n" . ilya_lang('misc/form_security_reload');

else {
	if ($noticeid == 'visitor')
		setcookie('ilya_noticed', 1, time() + 86400 * 3650, '/', ILYA_COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true);

	else {
		$userid = ilya_get_logged_in_userid();

		if ($noticeid == 'welcome')
			ilya_db_user_set_flag($userid, ILYA_USER_FLAGS_WELCOME_NOTICE, false);
		else
			ilya_db_usernotice_delete($userid, $noticeid);
	}


	echo "ILYA_AJAX_RESPONSE\n1";
}
