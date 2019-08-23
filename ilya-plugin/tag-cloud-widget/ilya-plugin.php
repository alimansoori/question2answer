<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	File: ilya-plugin/tag-cloud-widget/ilya-plugin.php
	Description: Initiates tag cloud widget plugin


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
	Plugin Name: Tag Cloud Widget
	Plugin URI:
	Plugin Description: Provides a list of tags with size indicating popularity
	Plugin Version: 1.0.1
	Plugin Date: 2011-12-06
	Plugin Author: IlyaIdea
	Plugin Author URI: https://projekt.ir/
	Plugin License: GPLv2
	Plugin Minimum IlyaIdea Version: 1.4
	Plugin Update Check URI:
*/


if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


ilya_register_plugin_module('widget', 'ilya-tag-cloud.php', 'ilya_tag_cloud', 'Tag Cloud');
