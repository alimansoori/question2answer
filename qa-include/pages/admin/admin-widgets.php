<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for admin page for editing widgets


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

if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

require_once ILYA__INCLUDE_DIR . 'app/admin.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';


// Get current list of widgets and determine the state of this admin page

$widgetid = ilya_post_text('edit');
if (!strlen($widgetid))
	$widgetid = ilya_get('edit');

list($widgets, $pages) = ilya_db_select_with_pending(
	ilya_db_widgets_selectspec(),
	ilya_db_pages_selectspec()
);

if (isset($widgetid)) {
	$editwidget = null;
	foreach ($widgets as $widget) {
		if ($widget['widgetid'] == $widgetid)
			$editwidget = $widget;
	}

} else {
	$editwidget = array('title' => ilya_post_text('title'));
	if (!isset($editwidget['title']))
		$editwidget['title'] = ilya_get('title');
}

$module = ilya_load_module('widget', @$editwidget['title']);

$widgetfound = isset($module);


// Check admin privileges (do late to allow one DB query)

if (!ilya_admin_check_privileges($ilya_content))
	return $ilya_content;


// Define an array of relevant templates we can use

$templatelangkeys = array(
	'question' => 'admin/question_pages',

	'qa' => 'main/recent_qs_as_title',
	'activity' => 'main/recent_activity_title',
	'questions' => 'admin/question_lists',
	'hot' => 'main/hot_qs_title',
	'unanswered' => 'main/unanswered_qs_title',

	'tags' => 'main/popular_tags',
	'categories' => 'misc/browse_categories',
	'users' => 'main/highest_users',
	'ask' => 'question/ask_title',

	'tag' => 'admin/tag_pages',
	'user' => 'admin/user_pages',
	'message' => 'misc/private_message_title',

	'search' => 'main/search_title',
	'feedback' => 'misc/feedback_title',

	'login' => 'users/login_title',
	'register' => 'users/register_title',
	'account' => 'profile/my_account_title',
	'favorites' => 'misc/my_favorites_title',
	'updates' => 'misc/recent_updates_title',

	'ip' => 'admin/ip_address_pages',
	'admin' => 'admin/admin_title',
);

$templateoptions = array();

if (isset($module) && method_exists($module, 'allow_template')) {
	foreach ($templatelangkeys as $template => $langkey) {
		if ($module->allow_template($template))
			$templateoptions[$template] = ilya_lang_html($langkey);
	}

	if ($module->allow_template('custom')) {
		$pagemodules = ilya_load_modules_with('page', 'match_request');
		foreach ($pages as $page) {
			// check if this is a page plugin by fetching all plugin classes and matching requests - currently quite convoluted!
			$isPagePlugin = false;
			foreach ($pagemodules as $pagemodule) {
				if ($pagemodule->match_request($page['tags'])) {
					$isPagePlugin = true;
				}
			}

			if ($isPagePlugin || !($page['flags'] & ILYA__PAGE_FLAGS_EXTERNAL))
				$templateoptions['custom-' . $page['pageid']] = ilya_html($page['title']);
		}

	}
}


// Process saving an old or new widget

$securityexpired = false;

if (ilya_clicked('docancel'))
	ilya_redirect('admin/layout');

elseif (ilya_clicked('dosavewidget')) {
	require_once ILYA__INCLUDE_DIR . 'db/admin.php';

	if (!ilya_check_form_security_code('admin/widgets', ilya_post_text('code')))
		$securityexpired = true;

	else {
		if (ilya_post_text('dodelete')) {
			ilya_db_widget_delete($editwidget['widgetid']);
			ilya_redirect('admin/layout');

		} else {
			if ($widgetfound) {
				$intitle = ilya_post_text('title');
				$inposition = ilya_post_text('position');
				$intemplates = array();

				if (ilya_post_text('template_all'))
					$intemplates[] = 'all';

				foreach (array_keys($templateoptions) as $template) {
					if (ilya_post_text('template_' . $template))
						$intemplates[] = $template;
				}

				$intags = implode(',', $intemplates);

				// Perform appropriate database action

				if (isset($editwidget['widgetid'])) { // changing existing widget
					$widgetid = $editwidget['widgetid'];
					ilya_db_widget_set_fields($widgetid, $intags);

				} else
					$widgetid = ilya_db_widget_create($intitle, $intags);

				ilya_db_widget_move($widgetid, substr($inposition, 0, 2), substr($inposition, 2));
			}

			ilya_redirect('admin/layout');
		}
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/admin_title') . ' - ' . ilya_lang_html('admin/layout_title');
$ilya_content['error'] = $securityexpired ? ilya_lang_html('admin/form_security_expired') : ilya_admin_page_error();

$positionoptions = array();

$placeoptionhtml = ilya_admin_place_options();

$regioncodes = array(
	'F' => 'full',
	'M' => 'main',
	'S' => 'side',
);

foreach ($placeoptionhtml as $place => $optionhtml) {
	$region = $regioncodes[substr($place, 0, 1)];

	$widgetallowed = method_exists($module, 'allow_region') && $module->allow_region($region);

	if ($widgetallowed) {
		foreach ($widgets as $widget) {
			if ($widget['place'] == $place && $widget['title'] == $editwidget['title'] && $widget['widgetid'] !== @$editwidget['widgetid'])
				$widgetallowed = false; // don't allow two instances of same widget in same place
		}
	}

	if ($widgetallowed) {
		$previous = null;
		$passedself = false;
		$maxposition = 0;

		foreach ($widgets as $widget) {
			if ($widget['place'] == $place) {
				$positionhtml = $optionhtml;

				if (isset($previous))
					$positionhtml .= ' - ' . ilya_lang_html_sub('admin/after_x', ilya_html($passedself ? $widget['title'] : $previous['title']));

				if ($widget['widgetid'] == @$editwidget['widgetid'])
					$passedself = true;

				$maxposition = max($maxposition, $widget['position']);
				$positionoptions[$place . $widget['position']] = $positionhtml;

				$previous = $widget;
			}
		}

		if (!isset($editwidget['widgetid']) || $place != @$editwidget['place']) {
			$positionhtml = $optionhtml;

			if (isset($previous))
				$positionhtml .= ' - ' . ilya_lang_html_sub('admin/after_x', $previous['title']);

			$positionoptions[$place . (isset($previous) ? (1 + $maxposition) : 1)] = $positionhtml;
		}
	}
}

$positionvalue = @$positionoptions[$editwidget['place'] . $editwidget['position']];

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_path_html(ilya_request()) . '"',

	'style' => 'tall',

	'fields' => array(
		'title' => array(
			'label' => ilya_lang_html('admin/widget_name') . ' &nbsp; ' . ilya_html($editwidget['title']),
			'type' => 'static',
			'tight' => true,
		),

		'position' => array(
			'id' => 'position_display',
			'tags' => 'name="position"',
			'label' => ilya_lang_html('admin/position'),
			'type' => 'select',
			'options' => $positionoptions,
			'value' => $positionvalue,
		),

		'delete' => array(
			'tags' => 'name="dodelete" id="dodelete"',
			'label' => ilya_lang_html('admin/delete_widget_position'),
			'value' => 0,
			'type' => 'checkbox',
		),

		'all' => array(
			'id' => 'all_display',
			'label' => ilya_lang_html('admin/widget_all_pages'),
			'type' => 'checkbox',
			'tags' => 'name="template_all" id="template_all"',
			'value' => is_numeric(strpos(',' . @$editwidget['tags'] . ',', ',all,')),
		),

		'templates' => array(
			'id' => 'templates_display',
			'label' => ilya_lang_html('admin/widget_pages_explanation'),
			'type' => 'custom',
			'html' => '',
		),
	),

	'buttons' => array(
		'save' => array(
			'label' => ilya_lang_html(isset($editwidget['widgetid']) ? 'main/save_button' : ('admin/add_widget_button')),
		),

		'cancel' => array(
			'tags' => 'name="docancel"',
			'label' => ilya_lang_html('main/cancel_button'),
		),
	),

	'hidden' => array(
		'dosavewidget' => '1', // for IE
		'edit' => @$editwidget['widgetid'],
		'title' => @$editwidget['title'],
		'code' => ilya_get_form_security_code('admin/widgets'),
	),
);

foreach ($templateoptions as $template => $optionhtml) {
	$ilya_content['form']['fields']['templates']['html'] .=
		'<input type="checkbox" name="template_' . ilya_html($template) . '"' .
		(is_numeric(strpos(',' . @$editwidget['tags'] . ',', ',' . $template . ',')) ? ' checked' : '') .
		'/> ' . $optionhtml . '<br/>';
}

if (isset($editwidget['widgetid'])) {
	ilya_set_display_rules($ilya_content, array(
		'templates_display' => '!(dodelete||template_all)',
		'all_display' => '!dodelete',
	));

} else {
	unset($ilya_content['form']['fields']['delete']);
	ilya_set_display_rules($ilya_content, array(
		'templates_display' => '!template_all',
	));
}

if (!$widgetfound) {
	unset($ilya_content['form']['fields']['title']['tight']);
	$ilya_content['form']['fields']['title']['error'] = ilya_lang_html('admin/widget_not_available');
	unset($ilya_content['form']['fields']['position']);
	unset($ilya_content['form']['fields']['all']);
	unset($ilya_content['form']['fields']['templates']);
	if (!isset($editwidget['widgetid']))
		unset($ilya_content['form']['buttons']['save']);

} elseif (!count($positionoptions)) {
	unset($ilya_content['form']['fields']['title']['tight']);
	$ilya_content['form']['fields']['title']['error'] = ilya_lang_html('admin/widget_no_positions');
	unset($ilya_content['form']['fields']['position']);
	unset($ilya_content['form']['fields']['all']);
	unset($ilya_content['form']['fields']['templates']);
	unset($ilya_content['form']['buttons']['save']);
}

$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();


return $ilya_content;
