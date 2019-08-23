<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for admin page for editing custom user titles


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'app/admin.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';


// Get current list of user titles and determine the state of this admin page

$oldpoints = ilya_post_text('edit');
if (!isset($oldpoints))
	$oldpoints = ilya_get('edit');

$pointstitle = ilya_get_points_to_titles();


// Check admin privileges (do late to allow one DB query)

if (!ilya_admin_check_privileges($ilya_content))
	return $ilya_content;


// Process saving an old or new user title

$securityexpired = false;

if (ilya_clicked('docancel'))
	ilya_redirect('admin/users');

elseif (ilya_clicked('dosavetitle')) {
	require_once QA_INCLUDE_DIR . 'util/string.php';

	if (!ilya_check_form_security_code('admin/usertitles', ilya_post_text('code')))
		$securityexpired = true;

	else {
		if (ilya_post_text('dodelete')) {
			unset($pointstitle[$oldpoints]);

		} else {
			$intitle = ilya_post_text('title');
			$inpoints = ilya_post_text('points');

			$errors = array();

			// Verify the title and points are legitimate

			if (!strlen($intitle))
				$errors['title'] = ilya_lang('main/field_required');

			if (!is_numeric($inpoints))
				$errors['points'] = ilya_lang('main/field_required');
			else {
				$inpoints = (int)$inpoints;

				if (isset($pointstitle[$inpoints]) && ((!strlen(@$oldpoints)) || ($inpoints != $oldpoints)))
					$errors['points'] = ilya_lang('admin/title_already_used');
			}

			// Perform appropriate action

			if (isset($pointstitle[$oldpoints])) { // changing existing user title
				$newpoints = isset($errors['points']) ? $oldpoints : $inpoints;
				$newtitle = isset($errors['title']) ? $pointstitle[$oldpoints] : $intitle;

				unset($pointstitle[$oldpoints]);
				$pointstitle[$newpoints] = $newtitle;

			} elseif (empty($errors)) // creating a new user title
				$pointstitle[$inpoints] = $intitle;
		}

		// Save the new option value

		krsort($pointstitle, SORT_NUMERIC);

		$option = '';
		foreach ($pointstitle as $points => $title)
			$option .= (strlen($option) ? ',' : '') . $points . ' ' . $title;

		ilya_set_option('points_to_titles', $option);

		if (empty($errors))
			ilya_redirect('admin/users');
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/admin_title') . ' - ' . ilya_lang_html('admin/users_title');
$ilya_content['error'] = $securityexpired ? ilya_lang_html('admin/form_security_expired') : ilya_admin_page_error();

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_path_html(ilya_request()) . '"',

	'style' => 'tall',

	'fields' => array(
		'title' => array(
			'tags' => 'name="title" id="title"',
			'label' => ilya_lang_html('admin/user_title'),
			'value' => ilya_html(isset($intitle) ? $intitle : @$pointstitle[$oldpoints]),
			'error' => ilya_html(@$errors['title']),
		),

		'delete' => array(
			'tags' => 'name="dodelete" id="dodelete"',
			'label' => ilya_lang_html('admin/delete_title'),
			'value' => 0,
			'type' => 'checkbox',
		),

		'points' => array(
			'id' => 'points_display',
			'tags' => 'name="points"',
			'label' => ilya_lang_html('admin/points_required'),
			'type' => 'number',
			'value' => ilya_html(isset($inpoints) ? $inpoints : @$oldpoints),
			'error' => ilya_html(@$errors['points']),
		),
	),

	'buttons' => array(
		'save' => array(
			'label' => ilya_lang_html(isset($pointstitle[$oldpoints]) ? 'main/save_button' : ('admin/add_title_button')),
		),

		'cancel' => array(
			'tags' => 'name="docancel"',
			'label' => ilya_lang_html('main/cancel_button'),
		),
	),

	'hidden' => array(
		'dosavetitle' => '1', // for IE
		'edit' => @$oldpoints,
		'code' => ilya_get_form_security_code('admin/usertitles'),
	),
);

if (isset($pointstitle[$oldpoints])) {
	ilya_set_display_rules($ilya_content, array(
		'points_display' => '!dodelete',
	));
} else {
	unset($ilya_content['form']['fields']['delete']);
}

$ilya_content['focusid'] = 'title';

$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();


return $ilya_content;
