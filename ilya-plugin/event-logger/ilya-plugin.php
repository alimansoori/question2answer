<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	File: ilya-plugin/event-logger/ilya-plugin.php
	Description: Initiates event logger plugin


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
	Plugin Name: Event Logger
	Plugin URI:
	Plugin Description: Stores a record of user activity in the database and/or log files
	Plugin Version: 1.1
	Plugin Date: 2011-12-06
	Plugin Author: IlyaIdea
	Plugin Author URI: https://projekt.ir/
	Plugin License: GPLv2
	Plugin Minimum IlyaIdea Version: 1.5
	Plugin Update Check URI:
*/


if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


ilya_register_plugin_module('event', 'ilya-event-logger.php', 'ilya_event_logger', 'Event Logger');
