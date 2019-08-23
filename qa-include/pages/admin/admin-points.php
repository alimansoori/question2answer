<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for admin page for settings about user points


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

require_once QA_INCLUDE_DIR . 'db/recalc.php';
require_once QA_INCLUDE_DIR . 'db/points.php';
require_once QA_INCLUDE_DIR . 'app/options.php';
require_once QA_INCLUDE_DIR . 'app/admin.php';
require_once QA_INCLUDE_DIR . 'util/sort.php';


// Check admin privileges

if (!ilya_admin_check_privileges($ilya_content)) {
	return $ilya_content;
}


// Process user actions

$securityexpired = false;
$recalculate = false;
$optionnames = ilya_db_points_option_names();

if (ilya_clicked('doshowdefaults')) {
	$options = array();

	foreach ($optionnames as $optionname) {
		$options[$optionname] = ilya_default_option($optionname);
	}
} else {
	if (ilya_clicked('dosaverecalc')) {
		if (!ilya_check_form_security_code('admin/points', ilya_post_text('code'))) {
			$securityexpired = true;
		} else {
			foreach ($optionnames as $optionname) {
				ilya_set_option($optionname, (int)ilya_post_text('option_' . $optionname));
			}

			if (!ilya_post_text('has_js')) {
				ilya_redirect('admin/recalc', array('dorecalcpoints' => 1));
			} else {
				$recalculate = true;
			}
		}
	}

	$options = ilya_get_options($optionnames);
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/admin_title') . ' - ' . ilya_lang_html('admin/points_title');
$ilya_content['error'] = $securityexpired ? ilya_lang_html('admin/form_security_expired') : ilya_admin_page_error();

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '" name="points_form" onsubmit="document.forms.points_form.has_js.value=1; return true;"',

	'style' => 'wide',

	'buttons' => array(
		'saverecalc' => array(
			'tags' => 'id="dosaverecalc"',
			'label' => ilya_lang_html('admin/save_recalc_button'),
		),
	),

	'hidden' => array(
		'dosaverecalc' => '1',
		'has_js' => '0',
		'code' => ilya_get_form_security_code('admin/points'),
	),
);


if (ilya_clicked('doshowdefaults')) {
	$ilya_content['form']['ok'] = ilya_lang_html('admin/points_defaults_shown');

	$ilya_content['form']['buttons']['cancel'] = array(
		'tags' => 'name="docancel"',
		'label' => ilya_lang_html('main/cancel_button'),
	);
} else {
	if ($recalculate) {
		$ilya_content['form']['ok'] = '<span id="recalc_ok"></span>';
		$ilya_content['form']['hidden']['code_recalc'] = ilya_get_form_security_code('admin/recalc');

		$ilya_content['script_rel'][] = 'ilya-content/ilya-admin.js?' . QA_VERSION;
		$ilya_content['script_var']['ilya_warning_recalc'] = ilya_lang('admin/stop_recalc_warning');

		$ilya_content['script_onloads'][] = array(
			"ilya_recalc_click('dorecalcpoints', document.getElementById('dosaverecalc'), null, 'recalc_ok');"
		);
	}

	$ilya_content['form']['buttons']['showdefaults'] = array(
		'tags' => 'name="doshowdefaults"',
		'label' => ilya_lang_html('admin/show_defaults_button'),
	);
}


foreach ($optionnames as $optionname) {
	$optionfield = array(
		'label' => ilya_lang_html('options/' . $optionname),
		'tags' => 'name="option_' . $optionname . '"',
		'value' => ilya_html($options[$optionname]),
		'type' => 'number',
		'note' => ilya_lang_html('admin/points'),
	);

	switch ($optionname) {
		case 'points_multiple':
			$prefix = '&#215;';
			unset($optionfield['note']);
			break;

		case 'points_per_q_voted_up':
		case 'points_per_a_voted_up':
		case 'points_per_c_voted_up':
		case 'points_q_voted_max_gain':
		case 'points_a_voted_max_gain':
		case 'points_c_voted_max_gain':
			$prefix = '+';
			break;

		case 'points_per_q_voted_down':
		case 'points_per_a_voted_down':
		case 'points_per_c_voted_down':
		case 'points_q_voted_max_loss':
		case 'points_a_voted_max_loss':
		case 'points_c_voted_max_loss':
			$prefix = '&ndash;';
			break;

		case 'points_base':
			$prefix = '+';
			break;

		default:
			$prefix = '<span style="visibility:hidden;">+</span>'; // for even alignment
			break;
	}

	$optionfield['prefix'] = '<span style="width:1em; display:inline-block; display:-moz-inline-stack;">' . $prefix . '</span>';

	$ilya_content['form']['fields'][$optionname] = $optionfield;
}

ilya_array_insert($ilya_content['form']['fields'], 'points_post_a', array('blank0' => array('type' => 'blank')));
ilya_array_insert($ilya_content['form']['fields'], 'points_per_c_voted_up', array('blank1' => array('type' => 'blank')));
ilya_array_insert($ilya_content['form']['fields'], 'points_vote_up_q', array('blank2' => array('type' => 'blank')));
ilya_array_insert($ilya_content['form']['fields'], 'points_multiple', array('blank3' => array('type' => 'blank')));


$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();


return $ilya_content;
