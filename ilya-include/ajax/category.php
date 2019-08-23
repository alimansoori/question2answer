<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax category information requests


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

require_once ILYA__INCLUDE_DIR . 'db/selects.php';


$categoryid = ilya_post_text('categoryid');
if (!strlen($categoryid))
	$categoryid = null;

list($fullcategory, $categories) = ilya_db_select_with_pending(
	ilya_db_full_category_selectspec($categoryid, true),
	ilya_db_category_sub_selectspec($categoryid)
);

echo "ILYA__AJAX_RESPONSE\n1\n";

echo ilya_html(strtr(@$fullcategory['content'], "\r\n", '  ')); // category description

foreach ($categories as $category) {
	// subcategory information
	echo "\n" . $category['categoryid'] . '/' . $category['title'];
}
