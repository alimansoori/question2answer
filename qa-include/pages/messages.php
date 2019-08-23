<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for private messaging page


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
	header('Location: ../../');
	exit;
}

require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';
require_once ILYA__INCLUDE_DIR . 'app/limits.php';

$loginUserId = ilya_get_logged_in_userid();
$loginUserHandle = ilya_get_logged_in_handle();


// Check which box we're showing (inbox/sent), we're not using Q2A's single-sign on integration and that we're logged in

$req = ilya_request_part(1);
if ($req === null)
	$showOutbox = false;
elseif ($req === 'sent')
	$showOutbox = true;
else
	return include ILYA__INCLUDE_DIR . 'ilya-page-not-found.php';

if (ILYA__FINAL_EXTERNAL_USERS)
	ilya_fatal_error('User accounts are handled by external code');

if (!isset($loginUserId)) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_insert_login_links(ilya_lang_html('misc/message_must_login'), ilya_request());
	return $ilya_content;
}

if (!ilya_opt('allow_private_messages') || !ilya_opt('show_message_history'))
	return include ILYA__INCLUDE_DIR . 'ilya-page-not-found.php';


// Find the messages for this user

$start = ilya_get_start();
$pagesize = ilya_opt('page_size_pms');

// get number of messages then actual messages for this page
$func = $showOutbox ? 'ilya_db_messages_outbox_selectspec' : 'ilya_db_messages_inbox_selectspec';
$pmSpecCount = ilya_db_selectspec_count($func('private', $loginUserId, true));
$pmSpec = $func('private', $loginUserId, true, $start, $pagesize);

list($numMessages, $userMessages) = ilya_db_select_with_pending($pmSpecCount, $pmSpec);
$count = $numMessages['count'];


// Prepare content for theme

$ilya_content = ilya_content_prepare();
$ilya_content['title'] = ilya_lang_html($showOutbox ? 'misc/pm_outbox_title' : 'misc/pm_inbox_title');

$ilya_content['custom'] =
	'<div style="text-align:center">' .
		($showOutbox ? '<a href="' . ilya_path_html('messages') . '">' . ilya_lang_html('misc/inbox') . '</a>' : ilya_lang_html('misc/inbox')) .
		' - ' .
		($showOutbox ? ilya_lang_html('misc/outbox') : '<a href="' . ilya_path_html('messages/sent') . '">' . ilya_lang_html('misc/outbox') . '</a>') .
	'</div>';

$ilya_content['message_list'] = array(
	'tags' => 'id="privatemessages"',
	'messages' => array(),
	'form' => array(
		'tags' => 'name="pmessage" method="post" action="' . ilya_self_html() . '"',
		'style' => 'tall',
		'hidden' => array(
			'ilya_click' => '', // for simulating clicks in Javascript
			'handle' => ilya_html($loginUserHandle),
			'start' => ilya_html($start),
			'code' => ilya_get_form_security_code('pm-' . $loginUserHandle),
		),
	),
);

$htmlDefaults = ilya_message_html_defaults();
if ($showOutbox)
	$htmlDefaults['towhomview'] = true;

foreach ($userMessages as $message) {
	$msgFormat = ilya_message_html_fields($message, $htmlDefaults);
	$replyHandle = $showOutbox ? $message['tohandle'] : $message['fromhandle'];
	$replyId = $showOutbox ? $message['touserid'] : $message['fromuserid'];

	$msgFormat['form'] = array(
		'style' => 'light',
		'buttons' => array(),
	);

	if (!empty($replyHandle) && $replyId != $loginUserId) {
		$msgFormat['form']['buttons']['reply'] = array(
			'tags' => 'onclick="window.location.href=\'' . ilya_path_html('message/' . $replyHandle) . '\';return false"',
			'label' => ilya_lang_html('question/reply_button'),
		);
	}

	$msgFormat['form']['buttons']['delete'] = array(
		'tags' => 'name="m' . ilya_html($message['messageid']) . '_dodelete" onclick="return ilya_pm_click(' . ilya_js($message['messageid']) . ', this, ' . ilya_js($showOutbox ? 'outbox' : 'inbox') . ');"',
		'label' => ilya_lang_html('question/delete_button'),
		'popup' => ilya_lang_html('profile/delete_pm_popup'),
	);

	$ilya_content['message_list']['messages'][] = $msgFormat;
}

$ilya_content['page_links'] = ilya_html_page_links(ilya_request(), $start, $pagesize, $count, ilya_opt('pages_prev_next'));

$ilya_content['navigation']['sub'] = ilya_user_sub_navigation($loginUserHandle, 'messages', true);

return $ilya_content;
