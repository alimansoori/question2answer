<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax single clicks on posts in admin section


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

require_once ILYA_INCLUDE_DIR . 'app/admin.php';
require_once ILYA_INCLUDE_DIR . 'app/users.php';
require_once ILYA_INCLUDE_DIR . 'app/cookies.php';


$entityid = ilya_post_text('entityid');
$action = ilya_post_text('action');

if (!ilya_check_form_security_code('admin/click', ilya_post_text('code')))
	echo "ILYA_AJAX_RESPONSE\n0\n" . ilya_lang('misc/form_security_reload');
elseif (ilya_admin_single_click($entityid, $action)) // permission check happens in here
	echo "ILYA_AJAX_RESPONSE\n1\n";
else
	echo "ILYA_AJAX_RESPONSE\n0\n" . ilya_lang('main/general_error');
