<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	File: ilya-plugin/facebook-login/ilya-plugin.php
	Description: Initiates Facebook login plugin


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

/*
	Plugin Name: Facebook Login
	Plugin URI:
	Plugin Description: Allows users to log in via Facebook
	Plugin Version: 1.1.5
	Plugin Date: 2012-09-13
	Plugin Author: IlyaIdea
	Plugin Author URI: https://projekt.ir/
	Plugin License: GPLv2
	Plugin Minimum IlyaIdea Version: 1.5
	Plugin Minimum PHP Version: 5
	Plugin Update Check URI:
*/


if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


// login modules don't work with external user integration
if (!ILYA__FINAL_EXTERNAL_USERS) {
	ilya_register_plugin_module('login', 'ilya-facebook-login.php', 'ilya_facebook_login', 'Facebook Login');
	ilya_register_plugin_module('page', 'ilya-facebook-login-page.php', 'ilya_facebook_login_page', 'Facebook Login Page');
	ilya_register_plugin_layer('ilya-facebook-layer.php', 'Facebook Login Layer');
}
