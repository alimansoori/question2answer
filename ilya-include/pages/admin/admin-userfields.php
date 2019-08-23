<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for admin page for editing custom user fields


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


// Get current list of user fields and determine the state of this admin page

$fieldid = ilya_post_text('edit');
if (!isset($fieldid))
	$fieldid = ilya_get('edit');

$userfields = ilya_db_select_with_pending(ilya_db_userfields_selectspec());

$editfield = null;
foreach ($userfields as $userfield) {
	if ($userfield['fieldid'] == $fieldid)
		$editfield = $userfield;
}


// Check admin privileges (do late to allow one DB query)

if (!ilya_admin_check_privileges($ilya_content))
	return $ilya_content;


// Process saving an old or new user field

$securityexpired = false;

if (ilya_clicked('docancel'))
	ilya_redirect('admin/users');

elseif (ilya_clicked('dosavefield')) {
	require_once ILYA__INCLUDE_DIR . 'db/admin.php';
	require_once ILYA__INCLUDE_DIR . 'util/string.php';

	if (!ilya_check_form_security_code('admin/userfields', ilya_post_text('code')))
		$securityexpired = true;

	else {
		if (ilya_post_text('dodelete')) {
			ilya_db_userfield_delete($editfield['fieldid']);
			ilya_redirect('admin/users');

		} else {
			$inname = ilya_post_text('name');
			$intype = ilya_post_text('type');
			$inonregister = (int)ilya_post_text('onregister');
			$inflags = $intype | ($inonregister ? ILYA__FIELD_FLAGS_ON_REGISTER : 0);
			$inposition = ilya_post_text('position');
			$inpermit = (int)ilya_post_text('permit');

			$errors = array();

			// Verify the name is legitimate

			if (ilya_strlen($inname) > ILYA__DB_MAX_PROFILE_TITLE_LENGTH)
				$errors['name'] = ilya_lang_sub('main/max_length_x', ILYA__DB_MAX_PROFILE_TITLE_LENGTH);

			// Perform appropriate database action

			if (isset($editfield['fieldid'])) { // changing existing user field
				ilya_db_userfield_set_fields($editfield['fieldid'], isset($errors['name']) ? $editfield['content'] : $inname, $inflags, $inpermit);
				ilya_db_userfield_move($editfield['fieldid'], $inposition);

				if (empty($errors))
					ilya_redirect('admin/users');

				else {
					$userfields = ilya_db_select_with_pending(ilya_db_userfields_selectspec()); // reload after changes
					foreach ($userfields as $userfield)
						if ($userfield['fieldid'] == $editfield['fieldid'])
							$editfield = $userfield;
				}

			} elseif (empty($errors)) { // creating a new user field
				for ($attempt = 0; $attempt < 1000; $attempt++) {
					$suffix = $attempt ? ('-' . (1 + $attempt)) : '';
					$newtag = ilya_substr(implode('-', ilya_string_to_words($inname)), 0, ILYA__DB_MAX_PROFILE_TITLE_LENGTH - strlen($suffix)) . $suffix;
					$uniquetag = true;

					foreach ($userfields as $userfield) {
						if (ilya_strtolower(trim($newtag)) == ilya_strtolower(trim($userfield['title'])))
							$uniquetag = false;
					}

					if ($uniquetag) {
						$fieldid = ilya_db_userfield_create($newtag, $inname, $inflags, $inpermit);
						ilya_db_userfield_move($fieldid, $inposition);
						ilya_redirect('admin/users');
					}
				}

				ilya_fatal_error('Could not create a unique database tag');
			}
		}
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/admin_title') . ' - ' . ilya_lang_html('admin/users_title');
$ilya_content['error'] = $securityexpired ? ilya_lang_html('admin/form_security_expired') : ilya_admin_page_error();

$positionoptions = array();
$previous = null;
$passedself = false;

foreach ($userfields as $userfield) {
	if (isset($previous))
		$positionhtml = ilya_lang_html_sub('admin/after_x', ilya_html(ilya_user_userfield_label($passedself ? $userfield : $previous)));
	else
		$positionhtml = ilya_lang_html('admin/first');

	$positionoptions[$userfield['position']] = $positionhtml;

	if ($userfield['fieldid'] == @$editfield['fieldid'])
		$passedself = true;

	$previous = $userfield;
}

if (isset($editfield['position']))
	$positionvalue = $positionoptions[$editfield['position']];
else {
	$positionvalue = isset($previous) ? ilya_lang_html_sub('admin/after_x', ilya_html(ilya_user_userfield_label($previous))) : ilya_lang_html('admin/first');
	$positionoptions[1 + @max(array_keys($positionoptions))] = $positionvalue;
}

$typeoptions = array(
	0 => ilya_lang_html('admin/field_single_line'),
	ILYA__FIELD_FLAGS_MULTI_LINE => ilya_lang_html('admin/field_multi_line'),
	ILYA__FIELD_FLAGS_LINK_URL => ilya_lang_html('admin/field_link_url'),
);

$permitoptions = ilya_admin_permit_options(ILYA__PERMIT_ALL, ILYA__PERMIT_ADMINS, false, false);
$permitvalue = @$permitoptions[isset($inpermit) ? $inpermit : $editfield['permit']];

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_path_html(ilya_request()) . '"',

	'style' => 'tall',

	'fields' => array(
		'name' => array(
			'tags' => 'name="name" id="name"',
			'label' => ilya_lang_html('admin/field_name'),
			'value' => ilya_html(isset($inname) ? $inname : ilya_user_userfield_label($editfield)),
			'error' => ilya_html(@$errors['name']),
		),

		'delete' => array(
			'tags' => 'name="dodelete" id="dodelete"',
			'label' => ilya_lang_html('admin/delete_field'),
			'value' => 0,
			'type' => 'checkbox',
		),

		'type' => array(
			'id' => 'type_display',
			'tags' => 'name="type"',
			'label' => ilya_lang_html('admin/field_type'),
			'type' => 'select',
			'options' => $typeoptions,
			'value' => @$typeoptions[isset($intype) ? $intype : (@$editfield['flags'] & (ILYA__FIELD_FLAGS_MULTI_LINE | ILYA__FIELD_FLAGS_LINK_URL))],
		),

		'permit' => array(
			'id' => 'permit_display',
			'tags' => 'name="permit"',
			'label' => ilya_lang_html('admin/permit_to_view'),
			'type' => 'select',
			'options' => $permitoptions,
			'value' => $permitvalue,
		),

		'position' => array(
			'id' => 'position_display',
			'tags' => 'name="position"',
			'label' => ilya_lang_html('admin/position'),
			'type' => 'select',
			'options' => $positionoptions,
			'value' => $positionvalue,
		),

		'onregister' => array(
			'id' => 'register_display',
			'tags' => 'name="onregister"',
			'label' => ilya_lang_html('admin/show_on_register_form'),
			'type' => 'checkbox',
			'value' => isset($inonregister) ? $inonregister : (@$editfield['flags'] & ILYA__FIELD_FLAGS_ON_REGISTER),
		),
	),

	'buttons' => array(
		'save' => array(
			'label' => ilya_lang_html(isset($editfield['fieldid']) ? 'main/save_button' : ('admin/add_field_button')),
		),

		'cancel' => array(
			'tags' => 'name="docancel"',
			'label' => ilya_lang_html('main/cancel_button'),
		),
	),

	'hidden' => array(
		'dosavefield' => '1', // for IE
		'edit' => @$editfield['fieldid'],
		'code' => ilya_get_form_security_code('admin/userfields'),
	),
);

if (isset($editfield['fieldid'])) {
	ilya_set_display_rules($ilya_content, array(
		'type_display' => '!dodelete',
		'position_display' => '!dodelete',
		'register_display' => '!dodelete',
		'permit_display' => '!dodelete',
	));
} else {
	unset($ilya_content['form']['fields']['delete']);
}

$ilya_content['focusid'] = 'name';

$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();


return $ilya_content;
