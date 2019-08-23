<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Wrapper functions and utilities for captcha modules


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
	header('Location: ../../');
	exit;
}


/**
 * Return whether a captcha module has been selected and it indicates that it is fully set up to go.
 */
function ilya_captcha_available()
{
	$module = ilya_load_module('captcha', ilya_opt('captcha_module'));

	return isset($module) && (!method_exists($module, 'allow_captcha') || $module->allow_captcha());
}


/**
 * Return an HTML string explaining $captchareason (from ilya_user_captcha_reason()) to the user about why they are seeing a captcha
 * @param $captchareason
 * @return mixed|null|string
 */
function ilya_captcha_reason_note($captchareason)
{
	$notehtml = null;

	switch ($captchareason) {
		case 'login':
			$notehtml = ilya_insert_login_links(ilya_lang_html('misc/captcha_login_fix'));
			break;

		case 'confirm':
			$notehtml = ilya_insert_login_links(ilya_lang_html('misc/captcha_confirm_fix'));
			break;

		case 'approve':
			$notehtml = ilya_lang_html('misc/captcha_approve_fix');
			break;
	}

	return $notehtml;
}


/**
 * Prepare $ilya_content for showing a captcha, adding the element to $fields, given previous $errors, and a $note to display.
 * Returns JavaScript required to load CAPTCHA when field is shown by user (e.g. clicking comment button).
 * @param $ilya_content
 * @param $fields
 * @param $errors
 * @param $note
 * @return string
 */
function ilya_set_up_captcha_field(&$ilya_content, &$fields, $errors, $note = null)
{
	if (!ilya_captcha_available())
		return '';

	$captcha = ilya_load_module('captcha', ilya_opt('captcha_module'));

	// workaround for reCAPTCHA, to load multiple instances via JS
	$count = @++$ilya_content['ilya_captcha_count'];

	if ($count > 1) {
		// use blank captcha in order to load via JS
		$html = '';
	} else {
		// first captcha is always loaded explicitly
		$ilya_content['script_var']['ilya_captcha_in'] = 'ilya_captcha_div_1';
		$html = $captcha->form_html($ilya_content, @$errors['captcha']);
	}

	$fields['captcha'] = array(
		'type' => 'custom',
		'label' => ilya_lang_html('misc/captcha_label'),
		'html' => '<div id="ilya_captcha_div_' . $count . '">' . $html . '</div>',
		'error' => @array_key_exists('captcha', $errors) ? ilya_lang_html('misc/captcha_error') : null,
		'note' => $note,
	);

	return "if (!document.getElementById('ilya_captcha_div_" . $count . "').hasChildNodes()) { recaptcha_load('ilya_captcha_div_" . $count . "'); }";
}


/**
 * Check if captcha is submitted correctly, and if not, set $errors['captcha'] to a descriptive string.
 * @param $errors
 * @return bool
 */
function ilya_captcha_validate_post(&$errors)
{
	if (ilya_captcha_available()) {
		$captcha = ilya_load_module('captcha', ilya_opt('captcha_module'));

		if (!$captcha->validate_post($error)) {
			$errors['captcha'] = $error;
			return false;
		}
	}

	return true;
}
