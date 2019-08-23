<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for unsubscribe page (unsubscribe link is sent in mass mailings)


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

require_once QA_INCLUDE_DIR . 'db/users.php';


// Check we're not using single-sign on integration

if (QA_FINAL_EXTERNAL_USERS)
	ilya_fatal_error('User login is handled by external code');


// Check the code and unsubscribe the user if appropriate

// check if already unsubscribed
$unsubscribed = (bool) (ilya_get_logged_in_flags() & QA_USER_FLAGS_NO_MAILINGS);
$loggedInUserId = ilya_get_logged_in_userid();
$isLoggedIn = $loggedInUserId !== null;

if (ilya_clicked('dounsubscribe')) {
	if (!ilya_check_form_security_code('unsubscribe', ilya_post_text('formcode'))) {
		$pageError = ilya_lang_html('misc/form_security_again');

	} else {
		if ($isLoggedIn) {
			// logged in users can unsubscribe right away
			ilya_db_user_set_flag($loggedInUserId, QA_USER_FLAGS_NO_MAILINGS, true);
			$unsubscribed = true;

		} else {
			// logged out users require valid code (from email link)
			$incode = trim(ilya_post_text('code'));
			$inhandle = ilya_post_text('handle');

			if (!empty($inhandle)) {
				$userinfo = ilya_db_select_with_pending(ilya_db_user_account_selectspec($inhandle, false));

				if (strtolower(trim(@$userinfo['emailcode'])) == strtolower($incode)) {
					ilya_db_user_set_flag($userinfo['userid'], QA_USER_FLAGS_NO_MAILINGS, true);
					$unsubscribed = true;
				}
			}

			if (!$unsubscribed) {
				$pageError = ilya_insert_login_links(ilya_lang_html('users/unsubscribe_wrong_log_in'), 'unsubscribe');
			}
		}
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('users/unsubscribe_title');

if ($unsubscribed) {
	$ilya_content['success'] = strtr(ilya_lang_html('users/unsubscribe_complete'), array(
		'^0' => ilya_html(ilya_opt('site_title')),
		'^1' => '<a href="' . ilya_path_html('account') . '">',
		'^2' => '</a>',
	));

} elseif (!empty($pageError)) {
	$ilya_content['error'] = $pageError;

} else {
	$contentForm = array(
		'tags' => 'method="post" action="' . ilya_path_html('unsubscribe') . '"',

		'style' => 'wide',

		'fields' => array(),

		'buttons' => array(
			'send' => array(
				'tags' => 'name="dounsubscribe"',
				'label' => ilya_lang_html('users/unsubscribe_title'),
			),
		),

		'hidden' => array(
			'formcode' => ilya_get_form_security_code('unsubscribe'),
		),
	);

	if ($isLoggedIn) {
		// user is logged in: show button to confirm unsubscribe
		$contentForm['fields']['email'] = array(
			'type' => 'static',
			'label' => ilya_lang_html('users/email_label'),
			'value' => ilya_html(ilya_get_logged_in_email()),
		);

	} else {
		// user is not logged in: show form with email address
		$incode = trim(ilya_get('c'));
		$inhandle = ilya_get('u');

		if (empty($incode) || empty($inhandle)) {
			$ilya_content['error'] = ilya_insert_login_links(ilya_lang_html('users/unsubscribe_wrong_log_in'), 'account');
			$contentForm = null;
		} else {
			$contentForm['fields']['handle'] = array(
				'type' => 'static',
				'label' => ilya_lang_html('users/handle_label'),
				'value' => ilya_html($inhandle),
			);
			$contentForm['hidden']['code'] = ilya_html($incode);
			$contentForm['hidden']['handle'] = ilya_html($inhandle);
		}
	}

	if ($contentForm) {
		$ilya_content['form'] = $contentForm;
	}
}

return $ilya_content;
