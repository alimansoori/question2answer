<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax mailing loop requests


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
require_once ILYA_INCLUDE_DIR . 'app/mailing.php';


$continue = false;

if (ilya_get_logged_in_level() >= ILYA_USER_LEVEL_ADMIN) {
	$starttime = time();

	ilya_mailing_perform_step();

	if ($starttime == time())
		sleep(1); // make sure at least one second has passed

	$message = ilya_mailing_progress_message();

	if (isset($message))
		$continue = true;
	else
		$message = ilya_lang('admin/mailing_complete');

} else
	$message = ilya_lang('admin/no_privileges');


echo "ILYA_AJAX_RESPONSE\n" . (int)$continue . "\n" . ilya_html($message);
