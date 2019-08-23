<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax favorite requests


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
require_once ILYA__INCLUDE_DIR . 'app/favorites.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';


$entitytype = ilya_post_text('entitytype');
$entityid = ilya_post_text('entityid');
$setfavorite = ilya_post_text('favorite');

$userid = ilya_get_logged_in_userid();

if (!ilya_check_form_security_code('favorite-' . $entitytype . '-' . $entityid, ilya_post_text('code'))) {
	echo "ILYA__AJAX_RESPONSE\n0\n" . ilya_lang('misc/form_security_reload');
} elseif (isset($userid)) {
	$cookieid = ilya_cookie_get();

	ilya_user_favorite_set($userid, ilya_get_logged_in_handle(), $cookieid, $entitytype, $entityid, $setfavorite);

	$favoriteform = ilya_favorite_form($entitytype, $entityid, $setfavorite, ilya_lang($setfavorite ? 'main/remove_favorites' : 'main/add_favorites'));

	$themeclass = ilya_load_theme_class(ilya_get_site_theme(), 'ajax-favorite', null, null);
	$themeclass->initialize();

	echo "ILYA__AJAX_RESPONSE\n1\n";

	$themeclass->favorite_inner_html($favoriteform);
}
