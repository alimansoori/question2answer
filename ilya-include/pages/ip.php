<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for page showing recent activity for an IP address


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: https://projekt.ir/license.php
*/

if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';


$ip = ilya_request_part(1); // picked up from ilya-page.php
if (filter_var($ip, FILTER_VALIDATE_IP) === false)
	return include ILYA__INCLUDE_DIR . 'ilya-page-not-found.php';


// Find recently (hidden, queued or not) questions, answers, comments and edits for this IP

$userid = ilya_get_logged_in_userid();

list($qs, $qs_queued, $qs_hidden, $a_qs, $a_queued_qs, $a_hidden_qs, $c_qs, $c_queued_qs, $c_hidden_qs, $edit_qs) =
	ilya_db_select_with_pending(
		ilya_db_qs_selectspec($userid, 'created', 0, null, $ip, false),
		ilya_db_qs_selectspec($userid, 'created', 0, null, $ip, 'Q_QUEUED'),
		ilya_db_qs_selectspec($userid, 'created', 0, null, $ip, 'Q_HIDDEN', true),
		ilya_db_recent_a_qs_selectspec($userid, 0, null, $ip, false),
		ilya_db_recent_a_qs_selectspec($userid, 0, null, $ip, 'A_QUEUED'),
		ilya_db_recent_a_qs_selectspec($userid, 0, null, $ip, 'A_HIDDEN', true),
		ilya_db_recent_c_qs_selectspec($userid, 0, null, $ip, false),
		ilya_db_recent_c_qs_selectspec($userid, 0, null, $ip, 'C_QUEUED'),
		ilya_db_recent_c_qs_selectspec($userid, 0, null, $ip, 'C_HIDDEN', true),
		ilya_db_recent_edit_qs_selectspec($userid, 0, null, $ip, false)
	);


// Check we have permission to view this page, and whether we can block or unblock IPs

if (ilya_user_maximum_permit_error('permit_anon_view_ips')) {
	$ilya_content = ilya_content_prepare();
	$ilya_content['error'] = ilya_lang_html('users/no_permission');
	return $ilya_content;
}

$blockable = ilya_user_level_maximum() >= ILYA__USER_LEVEL_MODERATOR; // allow moderator in one category to block across all categories


// Perform blocking or unblocking operations as appropriate

if (ilya_clicked('doblock') || ilya_clicked('dounblock') || ilya_clicked('dohideall')) {
	if (!ilya_check_form_security_code('ip-' . $ip, ilya_post_text('code')))
		$pageerror = ilya_lang_html('misc/form_security_again');

	elseif ($blockable) {
		if (ilya_clicked('doblock')) {
			$oldblocked = ilya_opt('block_ips_write');
			ilya_set_option('block_ips_write', (strlen($oldblocked) ? ($oldblocked . ' , ') : '') . $ip);

			ilya_report_event('ip_block', $userid, ilya_get_logged_in_handle(), ilya_cookie_get(), array(
				'ip' => $ip,
			));

			ilya_redirect(ilya_request());
		}

		if (ilya_clicked('dounblock')) {
			require_once ILYA__INCLUDE_DIR . 'app/limits.php';

			$blockipclauses = ilya_block_ips_explode(ilya_opt('block_ips_write'));

			foreach ($blockipclauses as $key => $blockipclause) {
				if (ilya_block_ip_match($ip, $blockipclause))
					unset($blockipclauses[$key]);
			}

			ilya_set_option('block_ips_write', implode(' , ', $blockipclauses));

			ilya_report_event('ip_unblock', $userid, ilya_get_logged_in_handle(), ilya_cookie_get(), array(
				'ip' => $ip,
			));

			ilya_redirect(ilya_request());
		}

		if (ilya_clicked('dohideall') && !ilya_user_maximum_permit_error('permit_hide_show')) {
			// allow moderator in one category to hide posts across all categories if they are identified via IP page

			require_once ILYA__INCLUDE_DIR . 'db/admin.php';
			require_once ILYA__INCLUDE_DIR . 'app/posts.php';

			$postids = ilya_db_get_ip_visible_postids($ip);

			foreach ($postids as $postid)
				ilya_post_set_status($postid, ILYA__POST_STATUS_HIDDEN, $userid);

			ilya_redirect(ilya_request());
		}
	}
}


// Combine sets of questions and get information for users

$questions = ilya_any_sort_by_date(array_merge($qs, $qs_queued, $qs_hidden, $a_qs, $a_queued_qs, $a_hidden_qs, $c_qs, $c_queued_qs, $c_hidden_qs, $edit_qs));

$usershtml = ilya_userids_handles_html(ilya_any_get_userids_handles($questions));

$hostname = gethostbyaddr($ip);


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html_sub('main/ip_address_x', ilya_html($ip));
$ilya_content['error'] = @$pageerror;

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '"',

	'style' => 'wide',

	'fields' => array(
		'host' => array(
			'type' => 'static',
			'label' => ilya_lang_html('misc/host_name'),
			'value' => ilya_html($hostname),
		),
	),

	'hidden' => array(
		'code' => ilya_get_form_security_code('ip-' . $ip),
	),
);


if ($blockable) {
	require_once ILYA__INCLUDE_DIR . 'app/limits.php';

	$blockipclauses = ilya_block_ips_explode(ilya_opt('block_ips_write'));
	$matchclauses = array();

	foreach ($blockipclauses as $blockipclause) {
		if (ilya_block_ip_match($ip, $blockipclause))
			$matchclauses[] = $blockipclause;
	}

	if (count($matchclauses)) {
		$ilya_content['form']['fields']['status'] = array(
			'type' => 'static',
			'label' => ilya_lang_html('misc/matches_blocked_ips'),
			'value' => ilya_html(implode("\n", $matchclauses), true),
		);

		$ilya_content['form']['buttons']['unblock'] = array(
			'tags' => 'name="dounblock"',
			'label' => ilya_lang_html('misc/unblock_ip_button'),
		);

		if (count($questions) && !ilya_user_maximum_permit_error('permit_hide_show'))
			$ilya_content['form']['buttons']['hideall'] = array(
				'tags' => 'name="dohideall" onclick="ilya_show_waiting_after(this, false);"',
				'label' => ilya_lang_html('misc/hide_all_ip_button'),
			);

	} else {
		$ilya_content['form']['buttons']['block'] = array(
			'tags' => 'name="doblock"',
			'label' => ilya_lang_html('misc/block_ip_button'),
		);
	}
}


$ilya_content['q_list']['qs'] = array();

if (count($questions)) {
	$ilya_content['q_list']['title'] = ilya_lang_html_sub('misc/recent_activity_from_x', ilya_html($ip));

	foreach ($questions as $question) {
		$htmloptions = ilya_post_html_options($question);
		$htmloptions['tagsview'] = false;
		$htmloptions['voteview'] = false;
		$htmloptions['ipview'] = false;
		$htmloptions['answersview'] = false;
		$htmloptions['viewsview'] = false;
		$htmloptions['updateview'] = false;

		$htmlfields = ilya_any_to_q_html_fields($question, $userid, ilya_cookie_get(), $usershtml, null, $htmloptions);

		if (isset($htmlfields['what_url'])) // link directly to relevant content
			$htmlfields['url'] = $htmlfields['what_url'];

		$hasother = isset($question['opostid']);

		if ($question[$hasother ? 'ohidden' : 'hidden'] && !isset($question[$hasother ? 'oupdatetype' : 'updatetype'])) {
			$htmlfields['what_2'] = ilya_lang_html('main/hidden');

			if (@$htmloptions['whenview']) {
				$updated = @$question[$hasother ? 'oupdated' : 'updated'];
				if (isset($updated))
					$htmlfields['when_2'] = ilya_when_to_html($updated, @$htmloptions['fulldatedays']);
			}
		}

		$ilya_content['q_list']['qs'][] = $htmlfields;
	}

} else
	$ilya_content['q_list']['title'] = ilya_lang_html_sub('misc/no_activity_from_x', ilya_html($ip));


return $ilya_content;
