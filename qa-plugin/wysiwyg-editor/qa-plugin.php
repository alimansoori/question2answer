<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: ilya-plugin/wysiwyg-editor/ilya-plugin.php
	Description: Initiates WYSIWYG editor plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

/*
	Plugin Name: WYSIWYG Editor
	Plugin URI:
	Plugin Description: Wrapper for CKEditor WYSIWYG rich text editor
	Plugin Version: 1.1.1
	Plugin Date: 2011-12-06
	Plugin Author: Question2Answer
	Plugin Author URI: http://www.question2answer.org/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.3
	Plugin Update Check URI:
*/


if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


ilya_register_plugin_module('editor', 'ilya-wysiwyg-editor.php', 'ilya_wysiwyg_editor', 'WYSIWYG Editor');
ilya_register_plugin_module('page', 'ilya-wysiwyg-upload.php', 'ilya_wysiwyg_upload', 'WYSIWYG Upload');

ilya_register_plugin_module('page', 'ilya-wysiwyg-ajax.php', 'ilya_wysiwyg_ajax', 'WYSIWYG Editor AJAX handler');
