<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for page not found (error 404)


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

if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../');
	exit;
}

require_once ILYA_INCLUDE_DIR . 'app/format.php';


header('HTTP/1.0 404 Not Found');

ilya_set_template('not-found');

$ilya_content = ilya_content_prepare();
$ilya_content['error'] = ilya_lang_html('main/page_not_found');
$ilya_content['suggest_next'] = ilya_html_suggest_qs_tags(ilya_using_tags());


return $ilya_content;
