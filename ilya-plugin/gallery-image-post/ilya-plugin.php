<?php
/*
	Plugin Name: Gallery image for post
	Plugin URI: 
	Plugin Description: Add gallery on question form
	Plugin Version: 1.7
	Plugin Date: 2015-02-04
	Plugin Author: sama55@CMSBOX
	Plugin Author URI: http://www.cmsbox.jp/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.6
	Plugin Update Check URI: 
*/
if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
ilya_register_plugin_phrases('ilya-gallery-lang-*.php', 'gallery_image');
ilya_register_plugin_module('module', 'ilya-gallery.php', 'ilya_gallery', 'Gallery image post');
ilya_register_plugin_module('event', 'ilya-gallery-event.php', 'ilya_gallery_event', 'Gallery image post');
ilya_register_plugin_layer('ilya-gallery-layer.php', 'Gallery image post');
ilya_register_plugin_module('filter', 'ilya-gallery-filter.php', 'ilya_gallery_filter', 'Gallery image post');
/*
	Omit PHP closing tag to help avoid accidental output
*/