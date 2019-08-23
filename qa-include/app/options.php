<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Getting and setting admin options (application level)


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

require_once ILYA__INCLUDE_DIR . 'db/options.php';

define('ILYA__PERMIT_ALL', 150);
define('ILYA__PERMIT_USERS', 120);
define('ILYA__PERMIT_CONFIRMED', 110);
define('ILYA__PERMIT_POINTS', 106);
define('ILYA__PERMIT_POINTS_CONFIRMED', 104);
define('ILYA__PERMIT_APPROVED', 103);
define('ILYA__PERMIT_APPROVED_POINTS', 102);
define('ILYA__PERMIT_EXPERTS', 100);
define('ILYA__PERMIT_EDITORS', 70);
define('ILYA__PERMIT_MODERATORS', 40);
define('ILYA__PERMIT_ADMINS', 20);
define('ILYA__PERMIT_SUPERS', 0);


/**
 * Return an array [name] => [value] of settings for each option in $names.
 * If any options are missing from the database, set them to their defaults
 * @param $names
 * @return array
 */
function ilya_get_options($names)
{
	global $ilya_options_cache, $ilya_options_loaded;

	// If any options not cached, retrieve them from database via standard pending mechanism

	if (!$ilya_options_loaded)
		ilya_preload_options();

	if (!$ilya_options_loaded) {
		require_once ILYA__INCLUDE_DIR . 'db/selects.php';

		ilya_load_options_results(array(
			ilya_db_get_pending_result('options'),
			ilya_db_get_pending_result('time'),
		));
	}

	// Pull out the options specifically requested here, and assign defaults

	$options = array();
	foreach ($names as $name) {
		if (!isset($ilya_options_cache[$name])) {
			$todatabase = true;

			switch ($name) { // don't write default to database if option was deprecated, or depends on site language (which could be changed)
				case 'custom_sidebar':
				case 'site_title':
				case 'email_privacy':
				case 'answer_needs_login':
				case 'ask_needs_login':
				case 'comment_needs_login':
				case 'db_time':
					$todatabase = false;
					break;
			}

			ilya_set_option($name, ilya_default_option($name), $todatabase);
		}

		$options[$name] = $ilya_options_cache[$name];
	}

	return $options;
}


/**
 * Return the value of option $name if it has already been loaded, otherwise return null
 * (used to prevent a database query if it's not essential for us to know the option value)
 * @param $name
 * @return
 */
function ilya_opt_if_loaded($name)
{
	global $ilya_options_cache;

	return @$ilya_options_cache[$name];
}


/**
 * Load all of the Q2A options from the database.
 * From Q2A 1.8 we always load the options in a separate query regardless of ILYA__OPTIMIZE_DISTANT_DB.
 */
function ilya_preload_options()
{
	global $ilya_options_loaded;

	if (!@$ilya_options_loaded) {
		$selectspecs = array(
			'options' => array(
				'columns' => array('title', 'content'),
				'source' => '^options',
				'arraykey' => 'title',
				'arrayvalue' => 'content',
			),

			'time' => array(
				'columns' => array('title' => "'db_time'", 'content' => 'UNIX_TIMESTAMP(NOW())'),
				'arraykey' => 'title',
				'arrayvalue' => 'content',
			),
		);

		// fetch options in a separate query before everything else
		ilya_load_options_results(ilya_db_multi_select($selectspecs));
	}
}


/**
 * Load the options from the $results of the database selectspecs defined in ilya_preload_options()
 * @param $results
 * @return mixed
 */
function ilya_load_options_results($results)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_options_cache, $ilya_options_loaded;

	foreach ($results as $result) {
		foreach ($result as $name => $value) {
			$ilya_options_cache[$name] = $value;
		}
	}

	$ilya_options_loaded = true;
}


/**
 * Set an option $name to $value (application level) in both cache and database, unless
 * $todatabase=false, in which case set it in the cache only
 * @param $name
 * @param $value
 * @param bool $todatabase
 * @return mixed
 */
function ilya_set_option($name, $value, $todatabase = true)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_options_cache;

	if ($todatabase && isset($value))
		ilya_db_set_option($name, $value);

	$ilya_options_cache[$name] = $value;
}


/**
 * Reset the options in $names to their defaults
 * @param $names
 * @return mixed
 */
function ilya_reset_options($names)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	foreach ($names as $name) {
		ilya_set_option($name, ilya_default_option($name));
	}
}


/**
 * Return the default value for option $name
 * @param $name
 * @return bool|mixed|string
 */
function ilya_default_option($name)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$fixed_defaults = array(
		'allow_anonymous_naming' => 1,
		'allow_change_usernames' => 1,
		'allow_close_questions' => 1,
		'allow_close_own_questions' => 1,
		'allow_multi_answers' => 1,
		'allow_private_messages' => 1,
		'allow_user_walls' => 1,
		'allow_self_answer' => 1,
		'allow_view_q_bots' => 1,
		'avatar_allow_gravatar' => 1,
		'avatar_allow_upload' => 1,
		'avatar_message_list_size' => 20,
		'avatar_profile_size' => 200,
		'avatar_q_list_size' => 0,
		'avatar_q_page_a_size' => 40,
		'avatar_q_page_c_size' => 20,
		'avatar_q_page_q_size' => 50,
		'avatar_store_size' => 400,
		'avatar_users_size' => 30,
		'caching_catwidget_time' => 30,
		'caching_driver' => 'filesystem',
		'caching_enabled' => 0,
		'caching_q_start' => 7,
		'caching_q_time' => 30,
		'caching_qlist_time' => 5,
		'captcha_on_anon_post' => 1,
		'captcha_on_feedback' => 1,
		'captcha_on_register' => 1,
		'captcha_on_reset_password' => 1,
		'captcha_on_unconfirmed' => 0,
		'columns_tags' => 3,
		'columns_users' => 2,
		'comment_on_as' => 1,
		'comment_on_qs' => 0,
		'confirm_user_emails' => 1,
		'do_ask_check_qs' => 0,
		'do_complete_tags' => 1,
		'do_count_q_views' => 1,
		'do_example_tags' => 1,
		'feed_for_activity' => 1,
		'feed_for_ilya' => 1,
		'feed_for_questions' => 1,
		'feed_for_unanswered' => 1,
		'feed_full_text' => 1,
		'feed_number_items' => 50,
		'feed_per_category' => 1,
		'feedback_enabled' => 1,
		'flagging_hide_after' => 5,
		'flagging_notify_every' => 2,
		'flagging_notify_first' => 1,
		'flagging_of_posts' => 1,
		'follow_on_as' => 1,
		'hot_weight_a_age' => 100,
		'hot_weight_answers' => 100,
		'hot_weight_q_age' => 100,
		'hot_weight_views' => 100,
		'hot_weight_votes' => 100,
		'mailing_per_minute' => 500,
		'match_ask_check_qs' => 3,
		'match_example_tags' => 3,
		'match_related_qs' => 3,
		'max_copy_user_updates' => 10,
		'max_len_q_title' => 120,
		'max_num_q_tags' => 5,
		'max_rate_ip_as' => 50,
		'max_rate_ip_cs' => 40,
		'max_rate_ip_flags' => 10,
		'max_rate_ip_logins' => 20,
		'max_rate_ip_messages' => 10,
		'max_rate_ip_qs' => 20,
		'max_rate_ip_registers' => 5,
		'max_rate_ip_uploads' => 20,
		'max_rate_ip_votes' => 600,
		'max_rate_user_as' => 25,
		'max_rate_user_cs' => 20,
		'max_rate_user_flags' => 5,
		'max_rate_user_messages' => 5,
		'max_rate_user_qs' => 10,
		'max_rate_user_uploads' => 10,
		'max_rate_user_votes' => 300,
		'max_store_user_updates' => 50,
		'min_len_a_content' => 12,
		'min_len_c_content' => 12,
		'min_len_q_content' => 0,
		'min_len_q_title' => 12,
		'min_num_q_tags' => 0,
		'minify_html' => 1,
		'moderate_notify_admin' => 1,
		'moderate_points_limit' => 150,
		'moderate_update_time' => 1,
		'nav_ask' => 1,
		'nav_ilya_not_home' => 1,
		'nav_questions' => 1,
		'nav_tags' => 1,
		'nav_unanswered' => 1,
		'nav_users' => 1,
		'neat_urls' => ILYA__URL_FORMAT_SAFEST,
		'notify_users_default' => 1,
		'page_size_activity' => 20,
		'page_size_ask_check_qs' => 5,
		'page_size_ask_tags' => 5,
		'page_size_home' => 20,
		'page_size_hot_qs' => 20,
		'page_size_pms' => 10,
		'page_size_q_as' => 10,
		'page_size_qs' => 20,
		'page_size_related_qs' => 5,
		'page_size_search' => 10,
		'page_size_tag_qs' => 20,
		'page_size_tags' => 30,
		'page_size_una_qs' => 20,
		'page_size_users' => 30,
		'page_size_wall' => 10,
		'pages_prev_next' => 3,
		'permit_anon_view_ips' => ILYA__PERMIT_EDITORS,
		'permit_close_q' => ILYA__PERMIT_EDITORS,
		'permit_delete_hidden' => ILYA__PERMIT_MODERATORS,
		'permit_edit_a' => ILYA__PERMIT_EXPERTS,
		'permit_edit_c' => ILYA__PERMIT_EDITORS,
		'permit_edit_q' => ILYA__PERMIT_EDITORS,
		'permit_edit_silent' => ILYA__PERMIT_MODERATORS,
		'permit_flag' => ILYA__PERMIT_CONFIRMED,
		'permit_hide_show' => ILYA__PERMIT_EDITORS,
		'permit_moderate' => ILYA__PERMIT_EXPERTS,
		'permit_post_wall' => ILYA__PERMIT_CONFIRMED,
		'permit_select_a' => ILYA__PERMIT_EXPERTS,
		'permit_view_q_page' => ILYA__PERMIT_ALL,
		'permit_view_new_users_page' => ILYA__PERMIT_EDITORS,
		'permit_view_special_users_page' => ILYA__PERMIT_MODERATORS,
		'permit_view_voters_flaggers' => ILYA__PERMIT_ADMINS,
		'permit_vote_a' => ILYA__PERMIT_USERS,
		'permit_vote_c' => ILYA__PERMIT_USERS,
		'permit_vote_down' => ILYA__PERMIT_USERS,
		'permit_vote_q' => ILYA__PERMIT_USERS,
		'points_a_selected' => 30,
		'points_a_voted_max_gain' => 20,
		'points_a_voted_max_loss' => 5,
		'points_base' => 100,
		'points_c_voted_max_gain' => 10,
		'points_c_voted_max_loss' => 3,
		'points_multiple' => 10,
		'points_per_c_voted_down' => 0,
		'points_per_c_voted_up' => 0,
		'points_post_a' => 4,
		'points_post_q' => 2,
		'points_q_voted_max_gain' => 10,
		'points_q_voted_max_loss' => 3,
		'points_select_a' => 3,
		'q_urls_title_length' => 50,
		'recalc_hotness_q_view' => 1,
		'show_a_c_links' => 1,
		'show_a_form_immediate' => 'if_no_as',
		'show_c_reply_buttons' => 1,
		'show_compact_numbers' => 1,
		'show_custom_welcome' => 0,
		'show_fewer_cs_count' => 5,
		'show_fewer_cs_from' => 10,
		'show_full_date_days' => 7,
		'show_message_history' => 1,
		'show_post_update_meta' => 1,
		'show_register_terms' => 0,
		'show_selected_first' => 1,
		'show_url_links' => 1,
		'show_user_points' => 1,
		'show_user_titles' => 1,
		'show_view_count_q_page' => 0,
		'show_view_counts' => 0,
		'show_when_created' => 1,
		'site_text_direction' => 'ltr',
		'site_theme' => 'SnowFlat',
		'smtp_port' => 25,
		'sort_answers_by' => 'created',
		'tags_or_categories' => 'tc',
		'use_microdata' => 1,
		'voting_on_as' => 1,
		'voting_on_cs' => 0,
		'voting_on_qs' => 1,
	);

	if (isset($fixed_defaults[$name])) {
		return $fixed_defaults[$name];
	}

	switch ($name) {
		case 'site_url':
			$protocol =
				(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
				(!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
				(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
					? 'https'
					: 'http';
			$value = $protocol . '://' . @$_SERVER['HTTP_HOST'] . strtr(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'), '\\', '/') . '/';
			break;

		case 'site_title':
			$value = ilya_default_site_title();
			break;

		case 'site_theme_mobile':
			$value = ilya_opt('site_theme');
			break;

		case 'from_email': // heuristic to remove short prefix (e.g. www. or ilya.)
			$parts = explode('.', @$_SERVER['HTTP_HOST']);

			if (count($parts) > 2 && strlen($parts[0]) < 5 && !is_numeric($parts[0]))
				unset($parts[0]);

			$value = 'no-reply@' . ((count($parts) > 1) ? implode('.', $parts) : 'example.com');
			break;

		case 'email_privacy':
			$value = ilya_lang_html('options/default_privacy');
			break;

		case 'show_custom_sidebar':
			$value = strlen(ilya_opt('custom_sidebar')) > 0;
			break;

		case 'show_custom_header':
			$value = strlen(ilya_opt('custom_header')) > 0;
			break;

		case 'show_custom_footer':
			$value = strlen(ilya_opt('custom_footer')) > 0;
			break;

		case 'show_custom_in_head':
			$value = strlen(ilya_opt('custom_in_head')) > 0;
			break;

		case 'register_terms':
			$value = ilya_lang_html_sub('options/default_terms', ilya_html(ilya_opt('site_title')));
			break;

		case 'block_bad_usernames':
			$value = ilya_lang_html('main/anonymous');
			break;

		case 'custom_sidebar':
			$value = ilya_lang_html_sub('options/default_sidebar', ilya_html(ilya_opt('site_title')));
			break;

		case 'editor_for_qs':
		case 'editor_for_as':
			require_once ILYA__INCLUDE_DIR . 'app/format.php';

			$value = '-'; // to match none by default, i.e. choose based on who is best at editing HTML
			ilya_load_editor('', 'html', $value);
			break;

		case 'permit_post_q': // convert from deprecated option if available
			$value = ilya_opt('ask_needs_login') ? ILYA__PERMIT_USERS : ILYA__PERMIT_ALL;
			break;

		case 'permit_post_a': // convert from deprecated option if available
			$value = ilya_opt('answer_needs_login') ? ILYA__PERMIT_USERS : ILYA__PERMIT_ALL;
			break;

		case 'permit_post_c': // convert from deprecated option if available
			$value = ilya_opt('comment_needs_login') ? ILYA__PERMIT_USERS : ILYA__PERMIT_ALL;
			break;

		case 'permit_retag_cat': // convert from previous option that used to contain it too
			$value = ilya_opt('permit_edit_q');
			break;

		case 'points_vote_up_q':
		case 'points_vote_down_q':
			$oldvalue = ilya_opt('points_vote_on_q');
			$value = is_numeric($oldvalue) ? $oldvalue : 1;
			break;

		case 'points_vote_up_a':
		case 'points_vote_down_a':
			$oldvalue = ilya_opt('points_vote_on_a');
			$value = is_numeric($oldvalue) ? $oldvalue : 1;
			break;

		case 'points_per_q_voted_up':
		case 'points_per_q_voted_down':
			$oldvalue = ilya_opt('points_per_q_voted');
			$value = is_numeric($oldvalue) ? $oldvalue : 1;
			break;

		case 'points_per_a_voted_up':
		case 'points_per_a_voted_down':
			$oldvalue = ilya_opt('points_per_a_voted');
			$value = is_numeric($oldvalue) ? $oldvalue : 2;
			break;

		case 'captcha_module':
			$captchamodules = ilya_list_modules('captcha');
			if (count($captchamodules))
				$value = reset($captchamodules);
			else
				$value = '';
			break;

		case 'mailing_from_name':
			$value = ilya_opt('site_title');
			break;

		case 'mailing_from_email':
			$value = ilya_opt('from_email');
			break;

		case 'mailing_subject':
			$value = ilya_lang_sub('options/default_subject', ilya_opt('site_title'));
			break;

		case 'mailing_body':
			$value = "\n\n\n--\n" . ilya_opt('site_title') . "\n" . ilya_opt('site_url');
			break;

		case 'form_security_salt':
			require_once ILYA__INCLUDE_DIR . 'util/string.php';
			$value = ilya_random_alphanum(32);
			break;

		default: // call option_default method in any registered modules
			$modules = ilya_load_all_modules_with('option_default');  // Loads all modules with the 'option_default' method

			foreach ($modules as $module) {
				$value = $module->option_default($name);
				if (strlen($value))
					return $value;
			}

			$value = '';
			break;
	}

	return $value;
}


/**
 * Return a heuristic guess at the name of the site from the HTTP HOST
 */
function ilya_default_site_title()
{
	$parts = explode('.', @$_SERVER['HTTP_HOST']);

	$longestpart = '';
	foreach ($parts as $part) {
		if (strlen($part) > strlen($longestpart))
			$longestpart = $part;
	}

	return ((strlen($longestpart) > 3) ? (ucfirst($longestpart) . ' ') : '') . ilya_lang('options/default_suffix');
}


/**
 * Return an array of defaults for the $options parameter passed to ilya_post_html_fields() and its ilk for posts of $basetype='Q'/'A'/'C'
 * Set $full to true if these posts will be viewed in full, i.e. on a question page rather than a question listing
 * @param $basetype
 * @param bool $full
 * @return array|mixed
 */
function ilya_post_html_defaults($basetype, $full = false)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'app/users.php';

	return array(
		'tagsview' => $basetype == 'Q' && ilya_using_tags(),
		'categoryview' => $basetype == 'Q' && ilya_using_categories(),
		'contentview' => $full,
		'voteview' => ilya_get_vote_view($basetype, $full),
		'flagsview' => ilya_opt('flagging_of_posts') && $full,
		'favoritedview' => true,
		'answersview' => $basetype == 'Q',
		'viewsview' => $basetype == 'Q' && ilya_opt('do_count_q_views') && ($full ? ilya_opt('show_view_count_q_page') : ilya_opt('show_view_counts')),
		'whatview' => true,
		'whatlink' => ilya_opt('show_a_c_links'),
		'whenview' => ilya_opt('show_when_created'),
		'ipview' => !ilya_user_permit_error('permit_anon_view_ips'),
		'whoview' => true,
		'avatarsize' => ilya_opt('avatar_q_list_size'),
		'pointsview' => ilya_opt('show_user_points'),
		'pointstitle' => ilya_opt('show_user_titles') ? ilya_get_points_to_titles() : array(),
		'updateview' => ilya_opt('show_post_update_meta'),
		'blockwordspreg' => ilya_get_block_words_preg(),
		'showurllinks' => ilya_opt('show_url_links'),
		'linksnewwindow' => ilya_opt('links_in_new_window'),
		'fulldatedays' => ilya_opt('show_full_date_days'),
	);
}


/**
 * Return an array of options for post $post to pass in the $options parameter to ilya_post_html_fields() and its ilk. Preferably,
 * call ilya_post_html_defaults() previously and pass its output in $defaults, to save excessive recalculation for each item in a
 * list. Set $full to true if these posts will be viewed in full, i.e. on a question page rather than a question listing.
 * @param $post
 * @param $defaults
 * @param bool $full
 * @return array|mixed|null
 */
function ilya_post_html_options($post, $defaults = null, $full = false)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	if (!isset($defaults))
		$defaults = ilya_post_html_defaults($post['basetype'], $full);

	$defaults['voteview'] = ilya_get_vote_view($post, $full);
	$defaults['ipview'] = !ilya_user_post_permit_error('permit_anon_view_ips', $post);

	return $defaults;
}


/**
 * Return an array of defaults for the $options parameter passed to ilya_message_html_fields()
 */
function ilya_message_html_defaults()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return array(
		'whenview' => ilya_opt('show_when_created'),
		'whoview' => true,
		'avatarsize' => ilya_opt('avatar_message_list_size'),
		'blockwordspreg' => ilya_get_block_words_preg(),
		'showurllinks' => ilya_opt('show_url_links'),
		'linksnewwindow' => ilya_opt('links_in_new_window'),
		'fulldatedays' => ilya_opt('show_full_date_days'),
	);
}


/**
 * Return $voteview parameter to pass to ilya_post_html_fields() in /ilya-include/app/format.php.
 * @param array|string $postorbasetype The post, or for compatibility just a basetype, i.e. 'Q', 'A' or 'C'
 * @param bool $full Whether full post is shown
 * @param bool $enabledif Whether to do checks for voting buttons (i.e. will always disable voting if false)
 * @return bool|string Possible values:
 *   updown, updown-disabled-page, updown-disabled-level, updown-uponly-level, updown-disabled-approve, updown-uponly-approve
 *   net, net-disabled-page, net-disabled-level, net-uponly-level, net-disabled-approve, net-uponly-approve
 */
function ilya_get_vote_view($postorbasetype, $full = false, $enabledif = true)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	// The 'level' and 'approve' permission errors are taken care of by disabling the voting buttons.
	// Others are reported to the user after they click, in ilya_vote_error_html(...)

	// deal with dual-use parameter
	if (is_array($postorbasetype)) {
		$basetype = $postorbasetype['basetype'];
		$post = $postorbasetype;
	} else {
		$basetype = $postorbasetype;
		$post = null;
	}

	$disabledsuffix = '';

	switch($basetype)
	{
		case 'Q':
			$view = ilya_opt('voting_on_qs');
			$permitOpt = 'permit_vote_q';
			break;
		case 'A':
			$view = ilya_opt('voting_on_as');
			$permitOpt = 'permit_vote_a';
			break;
		case 'C':
			$view = ilya_opt('voting_on_cs');
			$permitOpt = 'permit_vote_c';
			break;
		default:
			$view = false;
			break;
	}

	if (!$view) {
		return false;
	}

	if (!$enabledif || ($basetype == 'Q' && !$full && ilya_opt('voting_on_q_page_only'))) {
		$disabledsuffix = '-disabled-page';
	}
	else {
		$permiterror = isset($post) ? ilya_user_post_permit_error($permitOpt, $post) : ilya_user_permit_error($permitOpt);

		if ($permiterror == 'level')
			$disabledsuffix = '-disabled-level';
		elseif ($permiterror == 'approve')
			$disabledsuffix = '-disabled-approve';
		else {
			$permiterrordown = isset($post) ? ilya_user_post_permit_error('permit_vote_down', $post) : ilya_user_permit_error('permit_vote_down');

			if ($permiterrordown == 'level')
				$disabledsuffix = '-uponly-level';
			elseif ($permiterrordown == 'approve')
				$disabledsuffix = '-uponly-approve';
		}
	}

	return (ilya_opt('votes_separated') ? 'updown' : 'net') . $disabledsuffix;
}


/**
 * Returns true if the home page has been customized, either due to admin setting, or $ILYA__CONST_PATH_MAP
 */
function ilya_has_custom_home()
{
	return ilya_opt('show_custom_home') || (array_search('', ilya_get_request_map()) !== false);
}


/**
 * Return whether the option is set to classify questions by tags
 */
function ilya_using_tags()
{
	return strpos(ilya_opt('tags_or_categories'), 't') !== false;
}


/**
 * Return whether the option is set to classify questions by categories
 */
function ilya_using_categories()
{
	return strpos(ilya_opt('tags_or_categories'), 'c') !== false;
}


/**
 * Return the regular expression fragment to match the blocked words options set in the database
 */
function ilya_get_block_words_preg()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_blockwordspreg, $ilya_blockwordspreg_set;

	if (!@$ilya_blockwordspreg_set) {
		$blockwordstring = ilya_opt('block_bad_words');

		if (strlen($blockwordstring)) {
			require_once ILYA__INCLUDE_DIR . 'util/string.php';
			$ilya_blockwordspreg = ilya_block_words_to_preg($blockwordstring);

		} else
			$ilya_blockwordspreg = null;

		$ilya_blockwordspreg_set = true;
	}

	return $ilya_blockwordspreg;
}


/**
 * Return an array of [points] => [user title] from the 'points_to_titles' option, to pass to ilya_get_points_title_html()
 */
function ilya_get_points_to_titles()
{
	global $ilya_points_title_cache;

	if (!is_array($ilya_points_title_cache)) {
		$ilya_points_title_cache = array();

		$pairs = explode(',', ilya_opt('points_to_titles'));
		foreach ($pairs as $pair) {
			$spacepos = strpos($pair, ' ');
			if (is_numeric($spacepos)) {
				$points = trim(substr($pair, 0, $spacepos));
				$title = trim(substr($pair, $spacepos));

				if (is_numeric($points) && strlen($title))
					$ilya_points_title_cache[(int)$points] = $title;
			}
		}

		krsort($ilya_points_title_cache, SORT_NUMERIC);
	}

	return $ilya_points_title_cache;
}


/**
 * Return an array of relevant permissions settings, based on other options
 */
function ilya_get_permit_options()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$permits = array('permit_view_q_page', 'permit_post_q', 'permit_post_a');

	if (ilya_opt('comment_on_qs') || ilya_opt('comment_on_as'))
		$permits[] = 'permit_post_c';

	if (ilya_opt('voting_on_qs'))
		$permits[] = 'permit_vote_q';

	if (ilya_opt('voting_on_as'))
		$permits[] = 'permit_vote_a';

	if (ilya_opt('voting_on_cs'))
		$permits[] = 'permit_vote_c';

	if (ilya_opt('voting_on_qs') || ilya_opt('voting_on_as') || ilya_opt('voting_on_cs'))
		$permits[] = 'permit_vote_down';

	if (ilya_using_tags() || ilya_using_categories())
		$permits[] = 'permit_retag_cat';

	array_push($permits, 'permit_edit_q', 'permit_edit_a');

	if (ilya_opt('comment_on_qs') || ilya_opt('comment_on_as'))
		$permits[] = 'permit_edit_c';

	$permits[] = 'permit_edit_silent';

	if (ilya_opt('allow_close_questions'))
		$permits[] = 'permit_close_q';

	array_push($permits, 'permit_select_a', 'permit_anon_view_ips');

	if (ilya_opt('voting_on_qs') || ilya_opt('voting_on_as') || ilya_opt('voting_on_cs') || ilya_opt('flagging_of_posts'))
		$permits[] = 'permit_view_voters_flaggers';

	if (ilya_opt('flagging_of_posts'))
		$permits[] = 'permit_flag';

	$permits[] = 'permit_moderate';

	array_push($permits, 'permit_hide_show', 'permit_delete_hidden');

	if (ilya_opt('allow_user_walls'))
		$permits[] = 'permit_post_wall';

	array_push($permits, 'permit_view_new_users_page', 'permit_view_special_users_page');

	return $permits;
}
