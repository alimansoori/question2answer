<?php
/*
	Plugin Name: Extra Question Field
	Plugin URI: 
	Plugin Description: Add extra field on question form
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
ilya_register_plugin_phrases('ilya-eqf-lang-*.php', 'extra_field');
ilya_register_plugin_module('module', 'ilya-eqf.php', 'ilya_eqf', 'Extra Question Field');
ilya_register_plugin_module('event', 'ilya-eqf-event.php', 'ilya_eqf_event', 'Extra Question Field');
ilya_register_plugin_layer('ilya-eqf-layer.php', 'Extra Question Field');
ilya_register_plugin_module('filter', 'ilya-eqf-filter.php', 'ilya_eqf_filter', 'Extra Question Field');
/*
	Omit PHP closing tag to help avoid accidental output
*/