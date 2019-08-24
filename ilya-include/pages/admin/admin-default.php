<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for most admin pages which just contain options


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

if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

require_once ILYA_INCLUDE_DIR . 'db/admin.php';
require_once ILYA_INCLUDE_DIR . 'db/maxima.php';
require_once ILYA_INCLUDE_DIR . 'db/selects.php';
require_once ILYA_INCLUDE_DIR . 'app/options.php';
require_once ILYA_INCLUDE_DIR . 'app/admin.php';


// Pages handled by this controller: general, emails, users, layout, viewing, lists, posting, permissions, feeds, spam, caching, mailing

$adminsection = strtolower(ilya_request_part(1));


// Get list of categories and all options

$categories = ilya_db_select_with_pending(ilya_db_category_nav_selectspec(null, true));


// See if we need to redirect

if (empty($adminsection)) {
	$subnav = ilya_admin_sub_navigation();

	if (isset($subnav[@$_COOKIE['ilya_admin_last']]))
		ilya_redirect($_COOKIE['ilya_admin_last']);
	elseif (count($subnav)) {
		reset($subnav);
		ilya_redirect(key($subnav));
	}
}


// Check admin privileges (do late to allow one DB query)

if (!ilya_admin_check_privileges($ilya_content))
	return $ilya_content;


// For non-text options, lists of option types, minima and maxima

$optiontype = array(
	'avatar_message_list_size' => 'number',
	'avatar_profile_size' => 'number',
	'avatar_q_list_size' => 'number',
	'avatar_q_page_a_size' => 'number',
	'avatar_q_page_c_size' => 'number',
	'avatar_q_page_q_size' => 'number',
	'avatar_store_size' => 'number',
	'avatar_users_size' => 'number',
	'caching_catwidget_time' => 'number',
	'caching_q_start' => 'number',
	'caching_q_time' => 'number',
	'caching_qlist_time' => 'number',
	'columns_tags' => 'number',
	'columns_users' => 'number',
	'feed_number_items' => 'number',
	'flagging_hide_after' => 'number',
	'flagging_notify_every' => 'number',
	'flagging_notify_first' => 'number',
	'hot_weight_a_age' => 'number',
	'hot_weight_answers' => 'number',
	'hot_weight_q_age' => 'number',
	'hot_weight_views' => 'number',
	'hot_weight_votes' => 'number',
	'logo_height' => 'number-blank',
	'logo_width' => 'number-blank',
	'mailing_per_minute' => 'number',
	'max_len_q_title' => 'number',
	'max_num_q_tags' => 'number',
	'max_rate_ip_as' => 'number',
	'max_rate_ip_cs' => 'number',
	'max_rate_ip_flags' => 'number',
	'max_rate_ip_logins' => 'number',
	'max_rate_ip_messages' => 'number',
	'max_rate_ip_qs' => 'number',
	'max_rate_ip_registers' => 'number',
	'max_rate_ip_uploads' => 'number',
	'max_rate_ip_votes' => 'number',
	'max_rate_user_as' => 'number',
	'max_rate_user_cs' => 'number',
	'max_rate_user_flags' => 'number',
	'max_rate_user_messages' => 'number',
	'max_rate_user_qs' => 'number',
	'max_rate_user_uploads' => 'number',
	'max_rate_user_votes' => 'number',
	'min_len_a_content' => 'number',
	'min_len_c_content' => 'number',
	'min_len_q_content' => 'number',
	'min_len_q_title' => 'number',
	'min_num_q_tags' => 'number',
	'moderate_points_limit' => 'number',
	'page_size_activity' => 'number',
	'page_size_ask_check_qs' => 'number',
	'page_size_ask_tags' => 'number',
	'page_size_home' => 'number',
	'page_size_hot_qs' => 'number',
	'page_size_pms' => 'number',
	'page_size_q_as' => 'number',
	'page_size_qs' => 'number',
	'page_size_related_qs' => 'number',
	'page_size_search' => 'number',
	'page_size_tag_qs' => 'number',
	'page_size_tags' => 'number',
	'page_size_una_qs' => 'number',
	'page_size_users' => 'number',
	'page_size_wall' => 'number',
	'pages_prev_next' => 'number',
	'q_urls_title_length' => 'number',
	'show_fewer_cs_count' => 'number',
	'show_fewer_cs_from' => 'number',
	'show_full_date_days' => 'number',
	'smtp_port' => 'number',

	'allow_anonymous_naming' => 'checkbox',
	'allow_change_usernames' => 'checkbox',
	'allow_close_questions' => 'checkbox',
	'allow_close_own_questions' => 'checkbox',
	'allow_login_email_only' => 'checkbox',
	'allow_multi_answers' => 'checkbox',
	'allow_private_messages' => 'checkbox',
	'allow_user_walls' => 'checkbox',
	'allow_self_answer' => 'checkbox',
	'allow_view_q_bots' => 'checkbox',
	'avatar_allow_gravatar' => 'checkbox',
	'avatar_allow_upload' => 'checkbox',
	'avatar_default_show' => 'checkbox',
	'caching_enabled' => 'checkbox',
	'captcha_on_anon_post' => 'checkbox',
	'captcha_on_feedback' => 'checkbox',
	'captcha_on_register' => 'checkbox',
	'captcha_on_reset_password' => 'checkbox',
	'captcha_on_unapproved' => 'checkbox',
	'captcha_on_unconfirmed' => 'checkbox',
	'comment_on_as' => 'checkbox',
	'comment_on_qs' => 'checkbox',
	'confirm_user_emails' => 'checkbox',
	'confirm_user_required' => 'checkbox',
	'do_ask_check_qs' => 'checkbox',
	'do_close_on_select' => 'checkbox',
	'do_complete_tags' => 'checkbox',
	'do_count_q_views' => 'checkbox',
	'do_example_tags' => 'checkbox',
	'extra_field_active' => 'checkbox',
	'extra_field_display' => 'checkbox',
	'feed_for_activity' => 'checkbox',
	'feed_for_hot' => 'checkbox',
	'feed_for_ilya' => 'checkbox',
	'feed_for_questions' => 'checkbox',
	'feed_for_search' => 'checkbox',
	'feed_for_tag_qs' => 'checkbox',
	'feed_for_unanswered' => 'checkbox',
	'feed_full_text' => 'checkbox',
	'feed_per_category' => 'checkbox',
	'feedback_enabled' => 'checkbox',
	'flagging_of_posts' => 'checkbox',
	'follow_on_as' => 'checkbox',
	'links_in_new_window' => 'checkbox',
	'logo_show' => 'checkbox',
	'mailing_enabled' => 'checkbox',
	'moderate_anon_post' => 'checkbox',
	'moderate_by_points' => 'checkbox',
	'moderate_edited_again' => 'checkbox',
	'moderate_notify_admin' => 'checkbox',
	'moderate_unapproved' => 'checkbox',
	'moderate_unconfirmed' => 'checkbox',
	'moderate_users' => 'checkbox',
	'neat_urls' => 'checkbox',
	'notify_admin_q_post' => 'checkbox',
	'notify_users_default' => 'checkbox',
	'q_urls_remove_accents' => 'checkbox',
	'recalc_hotness_q_view' => 'checkbox',
	'register_notify_admin' => 'checkbox',
	'show_c_reply_buttons' => 'checkbox',
	'show_compact_numbers' => 'checkbox',
	'show_custom_answer' => 'checkbox',
	'show_custom_ask' => 'checkbox',
	'show_custom_comment' => 'checkbox',
	'show_custom_footer' => 'checkbox',
	'show_custom_header' => 'checkbox',
	'show_custom_home' => 'checkbox',
	'show_custom_in_head' => 'checkbox',
	'show_custom_register' => 'checkbox',
	'show_custom_sidebar' => 'checkbox',
	'show_custom_sidepanel' => 'checkbox',
	'show_custom_welcome' => 'checkbox',
	'show_home_description' => 'checkbox',
	'show_message_history' => 'checkbox',
	'show_notice_visitor' => 'checkbox',
	'show_notice_welcome' => 'checkbox',
	'show_post_update_meta' => 'checkbox',
	'show_register_terms' => 'checkbox',
	'show_selected_first' => 'checkbox',
	'show_url_links' => 'checkbox',
	'show_user_points' => 'checkbox',
	'show_user_titles' => 'checkbox',
	'show_view_counts' => 'checkbox',
	'show_view_count_q_page' => 'checkbox',
	'show_when_created' => 'checkbox',
	'site_maintenance' => 'checkbox',
	'smtp_active' => 'checkbox',
	'smtp_authenticate' => 'checkbox',
	'suspend_register_users' => 'checkbox',
	'tag_separator_comma' => 'checkbox',
	'use_microdata' => 'checkbox',
	'minify_html' => 'checkbox',
	'votes_separated' => 'checkbox',
	'voting_on_as' => 'checkbox',
	'voting_on_cs' => 'checkbox',
	'voting_on_q_page_only' => 'checkbox',
	'voting_on_qs' => 'checkbox',

	'smtp_password' => 'password',
);

$optionmaximum = array(
	'feed_number_items' => ILYA_DB_RETRIEVE_QS_AS,
	'max_len_q_title' => ILYA_DB_MAX_TITLE_LENGTH,
	'page_size_activity' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_ask_check_qs' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_ask_tags' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_home' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_hot_qs' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_pms' => ILYA_DB_RETRIEVE_MESSAGES,
	'page_size_qs' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_related_qs' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_search' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_tag_qs' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_tags' => ILYA_DB_RETRIEVE_TAGS,
	'page_size_una_qs' => ILYA_DB_RETRIEVE_QS_AS,
	'page_size_users' => ILYA_DB_RETRIEVE_USERS,
	'page_size_wall' => ILYA_DB_RETRIEVE_MESSAGES,
);

$optionminimum = array(
	'flagging_hide_after' => 2,
	'flagging_notify_every' => 1,
	'flagging_notify_first' => 1,
	'max_num_q_tags' => 2,
	'max_rate_ip_logins' => 1,
	'min_len_a_content' => 1,
	'min_len_c_content' => 1,
	'min_len_q_title' => 1,
	'page_size_activity' => 1,
	'page_size_ask_check_qs' => 3,
	'page_size_ask_tags' => 3,
	'page_size_home' => 1,
	'page_size_hot_qs' => 1,
	'page_size_pms' => 1,
	'page_size_q_as' => 1,
	'page_size_qs' => 1,
	'page_size_search' => 1,
	'page_size_tag_qs' => 1,
	'page_size_tags' => 1,
	'page_size_users' => 1,
	'page_size_wall' => 1,
);


// Define the options to show (and some other visual stuff) based on request

$formstyle = 'tall';
$checkboxtodisplay = null;

$maxpermitpost = max(ilya_opt('permit_post_q'), ilya_opt('permit_post_a'));
if (ilya_opt('comment_on_qs') || ilya_opt('comment_on_as'))
	$maxpermitpost = max($maxpermitpost, ilya_opt('permit_post_c'));

switch ($adminsection) {
	case 'general':
		$subtitle = 'admin/general_title';
		$showoptions = array('site_title', 'site_url', 'neat_urls', 'site_language', 'site_theme', 'site_theme_mobile', 'site_text_direction', 'tags_or_categories', 'site_maintenance');
		break;

	case 'emails':
		$subtitle = 'admin/emails_title';
		$showoptions = array(
			'from_email', 'feedback_email', 'notify_admin_q_post', 'feedback_enabled', 'email_privacy',
			'smtp_active', 'smtp_address', 'smtp_port', 'smtp_secure', 'smtp_authenticate', 'smtp_username', 'smtp_password'
		);

		$checkboxtodisplay = array(
			'smtp_address' => 'option_smtp_active',
			'smtp_port' => 'option_smtp_active',
			'smtp_secure' => 'option_smtp_active',
			'smtp_authenticate' => 'option_smtp_active',
			'smtp_username' => 'option_smtp_active && option_smtp_authenticate',
			'smtp_password' => 'option_smtp_active && option_smtp_authenticate',
		);
		break;

	case 'users':
		$subtitle = 'admin/users_title';

		$showoptions = array('show_notice_visitor', 'notice_visitor');

		if (!ILYA_FINAL_EXTERNAL_USERS) {
			require_once ILYA_INCLUDE_DIR . 'util/image.php';

			array_push($showoptions, 'show_custom_register', 'custom_register', 'show_register_terms', 'register_terms', 'show_notice_welcome', 'notice_welcome', 'show_custom_welcome', 'custom_welcome',
				'', 'allow_login_email_only', 'allow_change_usernames', 'register_notify_admin', 'suspend_register_users', '', 'block_bad_usernames',
				'', 'allow_private_messages', 'show_message_history', 'page_size_pms', 'allow_user_walls', 'page_size_wall',
				'', 'avatar_allow_gravatar');

			if (ilya_has_gd_image())
				array_push($showoptions, 'avatar_allow_upload', 'avatar_store_size', 'avatar_default_show');
		}

		$showoptions[] = '';

		if (!ILYA_FINAL_EXTERNAL_USERS)
			$showoptions[] = 'avatar_profile_size';

		array_push($showoptions, 'avatar_users_size', 'avatar_q_page_q_size', 'avatar_q_page_a_size', 'avatar_q_page_c_size',
			'avatar_q_list_size', 'avatar_message_list_size');

		$checkboxtodisplay = array(
			'custom_register' => 'option_show_custom_register',
			'register_terms' => 'option_show_register_terms',
			'custom_welcome' => 'option_show_custom_welcome',
			'notice_welcome' => 'option_show_notice_welcome',
			'notice_visitor' => 'option_show_notice_visitor',
			'show_message_history' => 'option_allow_private_messages',
			'avatar_store_size' => 'option_avatar_allow_upload',
			'avatar_default_show' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
		);

		if (!ILYA_FINAL_EXTERNAL_USERS) {
			$checkboxtodisplay = array_merge($checkboxtodisplay, array(
				'page_size_pms' => 'option_allow_private_messages && option_show_message_history',
				'page_size_wall' => 'option_allow_user_walls',
				'avatar_profile_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
				'avatar_users_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
				'avatar_q_page_q_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
				'avatar_q_page_a_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
				'avatar_q_page_c_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
				'avatar_q_list_size' => 'option_avatar_allow_gravatar || option_avatar_allow_upload',
				'avatar_message_list_size' => '(option_avatar_allow_gravatar || option_avatar_allow_upload) && (option_allow_private_messages || option_allow_user_walls)',
			));
		}

		$formstyle = 'wide';
		break;

	case 'layout':
		$subtitle = 'admin/layout_title';
		$showoptions = array('logo_show', 'logo_url', 'logo_width', 'logo_height', '', 'show_custom_sidebar', 'custom_sidebar', 'show_custom_sidepanel', 'custom_sidepanel', 'show_custom_header', 'custom_header', 'show_custom_footer', 'custom_footer', 'show_custom_in_head', 'custom_in_head', 'show_custom_home', 'custom_home_heading', 'custom_home_content', 'show_home_description', 'home_description', '');

		$checkboxtodisplay = array(
			'logo_url' => 'option_logo_show',
			'logo_width' => 'option_logo_show',
			'logo_height' => 'option_logo_show',
			'custom_sidebar' => 'option_show_custom_sidebar',
			'custom_sidepanel' => 'option_show_custom_sidepanel',
			'custom_header' => 'option_show_custom_header',
			'custom_footer' => 'option_show_custom_footer',
			'custom_in_head' => 'option_show_custom_in_head',
			'custom_home_heading' => 'option_show_custom_home',
			'custom_home_content' => 'option_show_custom_home',
			'home_description' => 'option_show_home_description',
		);
		break;

	case 'viewing':
		$subtitle = 'admin/viewing_title';
		$showoptions = array(
			'q_urls_title_length', 'q_urls_remove_accents', 'do_count_q_views', 'show_view_counts', 'show_view_count_q_page', 'recalc_hotness_q_view', '',
			'voting_on_qs', 'voting_on_q_page_only', 'voting_on_as', 'voting_on_cs', 'votes_separated', '',
			'show_url_links', 'links_in_new_window', 'show_when_created', 'show_full_date_days'
		);

		if (count(ilya_get_points_to_titles())) {
			$showoptions[] = 'show_user_titles';
		}

		array_push($showoptions,
			'show_user_points', 'show_post_update_meta', 'show_compact_numbers', 'use_microdata', 'minify_html', '',
			'sort_answers_by', 'show_selected_first', 'page_size_q_as', 'show_a_form_immediate'
		);

		if (ilya_opt('comment_on_qs') || ilya_opt('comment_on_as')) {
			array_push($showoptions, 'show_fewer_cs_from', 'show_fewer_cs_count', 'show_c_reply_buttons');
		}

		$showoptions[] = '';

		$widgets = ilya_db_single_select(ilya_db_widgets_selectspec());

		foreach ($widgets as $widget) {
			if ($widget['title'] == 'Related Questions') {
				array_push($showoptions, 'match_related_qs', 'page_size_related_qs', '');
				break;
			}
		}

		$showoptions[] = 'pages_prev_next';

		$formstyle = 'wide';

		$checkboxtodisplay = array(
			'show_view_counts' => 'option_do_count_q_views',
			'show_view_count_q_page' => 'option_do_count_q_views',
			'recalc_hotness_q_view' => 'option_do_count_q_views',
			'votes_separated' => 'option_voting_on_qs || option_voting_on_as',
			'voting_on_q_page_only' => 'option_voting_on_qs',
			'show_full_date_days' => 'option_show_when_created',
		);
		break;

	case 'lists':
		$subtitle = 'admin/lists_title';

		$showoptions = array('page_size_home', 'page_size_activity', 'page_size_qs', 'page_size_hot_qs', 'page_size_una_qs');

		if (ilya_using_tags())
			$showoptions[] = 'page_size_tag_qs';

		$showoptions[] = '';

		if (ilya_using_tags())
			array_push($showoptions, 'page_size_tags', 'columns_tags');

		array_push($showoptions, 'page_size_users', 'columns_users', '');

		$searchmodules = ilya_load_modules_with('search', 'process_search');

		if (count($searchmodules))
			$showoptions[] = 'search_module';

		$showoptions[] = 'page_size_search';

		array_push($showoptions, '', 'admin/hotness_factors', 'hot_weight_q_age', 'hot_weight_a_age', 'hot_weight_answers', 'hot_weight_votes');

		if (ilya_opt('do_count_q_views'))
			$showoptions[] = 'hot_weight_views';

		$formstyle = 'wide';

		break;

	case 'posting':
		$getoptions = ilya_get_options(array('tags_or_categories'));

		$subtitle = 'admin/posting_title';

		$showoptions = array('do_close_on_select', 'allow_close_questions', 'allow_close_own_questions', 'allow_self_answer', 'allow_multi_answers', 'follow_on_as', 'comment_on_qs', 'comment_on_as', 'allow_anonymous_naming', '');

		if (count(ilya_list_modules('editor')) > 1)
			array_push($showoptions, 'editor_for_qs', 'editor_for_as', 'editor_for_cs', '');

		array_push($showoptions, 'show_custom_ask', 'custom_ask', 'extra_field_active', 'extra_field_prompt', 'extra_field_display', 'extra_field_label', 'show_custom_answer', 'custom_answer', 'show_custom_comment', 'custom_comment', '');

		array_push($showoptions, 'min_len_q_title', 'max_len_q_title', 'min_len_q_content');

		if (ilya_using_tags())
			array_push($showoptions, 'min_num_q_tags', 'max_num_q_tags', 'tag_separator_comma');

		array_push($showoptions, 'min_len_a_content', 'min_len_c_content', 'notify_users_default');

		array_push($showoptions, '', 'block_bad_words', '', 'do_ask_check_qs', 'match_ask_check_qs', 'page_size_ask_check_qs', '');

		if (ilya_using_tags())
			array_push($showoptions, 'do_example_tags', 'match_example_tags', 'do_complete_tags', 'page_size_ask_tags');

		$formstyle = 'wide';

		$checkboxtodisplay = array(
			'allow_close_own_questions' => 'option_allow_close_questions',
			'editor_for_cs' => 'option_comment_on_qs || option_comment_on_as',
			'custom_ask' => 'option_show_custom_ask',
			'extra_field_prompt' => 'option_extra_field_active',
			'extra_field_display' => 'option_extra_field_active',
			'extra_field_label' => 'option_extra_field_active && option_extra_field_display',
			'extra_field_label_hidden' => '!option_extra_field_display',
			'extra_field_label_shown' => 'option_extra_field_display',
			'custom_answer' => 'option_show_custom_answer',
			'show_custom_comment' => 'option_comment_on_qs || option_comment_on_as',
			'custom_comment' => 'option_show_custom_comment && (option_comment_on_qs || option_comment_on_as)',
			'min_len_c_content' => 'option_comment_on_qs || option_comment_on_as',
			'match_ask_check_qs' => 'option_do_ask_check_qs',
			'page_size_ask_check_qs' => 'option_do_ask_check_qs',
			'match_example_tags' => 'option_do_example_tags',
			'page_size_ask_tags' => 'option_do_example_tags || option_do_complete_tags',
		);
		break;

	case 'permissions':
		$subtitle = 'admin/permissions_title';

		$permitoptions = ilya_get_permit_options();

		$showoptions = array();
		$checkboxtodisplay = array();

		foreach ($permitoptions as $permitoption) {
			$showoptions[] = $permitoption;

			if ($permitoption == 'permit_view_q_page') {
				$showoptions[] = 'allow_view_q_bots';
				$checkboxtodisplay['allow_view_q_bots'] = 'option_permit_view_q_page<' . ilya_js(ILYA_PERMIT_ALL);

			} else {
				$showoptions[] = $permitoption . '_points';
				$checkboxtodisplay[$permitoption . '_points'] = '(option_' . $permitoption . '==' . ilya_js(ILYA_PERMIT_POINTS) .
					')||(option_' . $permitoption . '==' . ilya_js(ILYA_PERMIT_POINTS_CONFIRMED) . ')||(option_' . $permitoption . '==' . ilya_js(ILYA_PERMIT_APPROVED_POINTS) . ')';
			}
		}

		$formstyle = 'wide';
		break;

	case 'feeds':
		$subtitle = 'admin/feeds_title';

		$showoptions = array('feed_for_questions', 'feed_for_ilya', 'feed_for_activity');

		array_push($showoptions, 'feed_for_hot', 'feed_for_unanswered');

		if (ilya_using_tags())
			$showoptions[] = 'feed_for_tag_qs';

		if (ilya_using_categories())
			$showoptions[] = 'feed_per_category';

		array_push($showoptions, 'feed_for_search', '', 'feed_number_items', 'feed_full_text');

		$formstyle = 'wide';

		$checkboxtodisplay = array(
			'feed_per_category' => 'option_feed_for_ilya || option_feed_for_questions || option_feed_for_unanswered || option_feed_for_activity',
		);
		break;

	case 'spam':
		$subtitle = 'admin/spam_title';

		$showoptions = array();

		$getoptions = ilya_get_options(array('feedback_enabled', 'permit_post_q', 'permit_post_a', 'permit_post_c'));

		if (!ILYA_FINAL_EXTERNAL_USERS)
			array_push($showoptions, 'confirm_user_emails', 'confirm_user_required', 'moderate_users', '');

		$captchamodules = ilya_list_modules('captcha');

		if (count($captchamodules)) {
			if (!ILYA_FINAL_EXTERNAL_USERS)
				array_push($showoptions, 'captcha_on_register', 'captcha_on_reset_password');

			if ($maxpermitpost > ILYA_PERMIT_USERS)
				$showoptions[] = 'captcha_on_anon_post';

			if ($maxpermitpost > ILYA_PERMIT_APPROVED)
				$showoptions[] = 'captcha_on_unapproved';

			if ($maxpermitpost > ILYA_PERMIT_CONFIRMED)
				$showoptions[] = 'captcha_on_unconfirmed';

			if ($getoptions['feedback_enabled'])
				$showoptions[] = 'captcha_on_feedback';

			$showoptions[] = 'captcha_module';
		}

		$showoptions[] = '';

		if ($maxpermitpost > ILYA_PERMIT_USERS)
			$showoptions[] = 'moderate_anon_post';

		if ($maxpermitpost > ILYA_PERMIT_APPROVED)
			$showoptions[] = 'moderate_unapproved';

		if ($maxpermitpost > ILYA_PERMIT_CONFIRMED)
			$showoptions[] = 'moderate_unconfirmed';

		if ($maxpermitpost > ILYA_PERMIT_EXPERTS)
			array_push($showoptions, 'moderate_by_points', 'moderate_points_limit', 'moderate_edited_again', 'moderate_notify_admin', 'moderate_update_time', '');

		array_push($showoptions, 'flagging_of_posts', 'flagging_notify_first', 'flagging_notify_every', 'flagging_hide_after', '');

		array_push($showoptions, 'block_ips_write', '');

		if (!ILYA_FINAL_EXTERNAL_USERS)
			array_push($showoptions, 'max_rate_ip_registers', 'max_rate_ip_logins', '');

		array_push($showoptions, 'max_rate_ip_qs', 'max_rate_user_qs', 'max_rate_ip_as', 'max_rate_user_as');

		if (ilya_opt('comment_on_qs') || ilya_opt('comment_on_as'))
			array_push($showoptions, 'max_rate_ip_cs', 'max_rate_user_cs');

		$showoptions[] = '';

		if (ilya_opt('voting_on_qs') || ilya_opt('voting_on_as') || ilya_opt('voting_on_cs'))
			array_push($showoptions, 'max_rate_ip_votes', 'max_rate_user_votes');

		array_push($showoptions, 'max_rate_ip_flags', 'max_rate_user_flags', 'max_rate_ip_uploads', 'max_rate_user_uploads');

		if (ilya_opt('allow_private_messages') || ilya_opt('allow_user_walls'))
			array_push($showoptions, 'max_rate_ip_messages', 'max_rate_user_messages');

		$formstyle = 'wide';

		$checkboxtodisplay = array(
			'confirm_user_required' => 'option_confirm_user_emails',
			'captcha_on_unapproved' => 'option_moderate_users',
			'captcha_on_unconfirmed' => 'option_confirm_user_emails && !(option_moderate_users && option_captcha_on_unapproved)',
			'captcha_module' => 'option_captcha_on_register || option_captcha_on_anon_post || (option_confirm_user_emails && option_captcha_on_unconfirmed) || (option_moderate_users && option_captcha_on_unapproved) || option_captcha_on_reset_password || option_captcha_on_feedback',
			'moderate_unapproved' => 'option_moderate_users',
			'moderate_unconfirmed' => 'option_confirm_user_emails && !(option_moderate_users && option_moderate_unapproved)',
			'moderate_points_limit' => 'option_moderate_by_points',
			'moderate_points_label_off' => '!option_moderate_by_points',
			'moderate_points_label_on' => 'option_moderate_by_points',
			'moderate_edited_again' => 'option_moderate_anon_post || (option_confirm_user_emails && option_moderate_unconfirmed) || (option_moderate_users && option_moderate_unapproved) || option_moderate_by_points',
			'flagging_hide_after' => 'option_flagging_of_posts',
			'flagging_notify_every' => 'option_flagging_of_posts',
			'flagging_notify_first' => 'option_flagging_of_posts',
			'max_rate_ip_flags' => 'option_flagging_of_posts',
			'max_rate_user_flags' => 'option_flagging_of_posts',
		);

		$checkboxtodisplay['moderate_notify_admin'] = $checkboxtodisplay['moderate_edited_again'];
		$checkboxtodisplay['moderate_update_time'] = $checkboxtodisplay['moderate_edited_again'];
		break;

	case 'caching':
		$subtitle = 'admin/caching_title';
		$formstyle = 'wide';

		$showoptions = array('caching_enabled', 'caching_driver', 'caching_q_start', 'caching_q_time', 'caching_catwidget_time');

		break;

	case 'mailing':
		require_once ILYA_INCLUDE_DIR . 'app/mailing.php';

		$subtitle = 'admin/mailing_title';

		$showoptions = array('mailing_enabled', 'mailing_from_name', 'mailing_from_email', 'mailing_subject', 'mailing_body', 'mailing_per_minute');
		break;

	default:
		$pagemodules = ilya_load_modules_with('page', 'match_request');
		$request = ilya_request();

		foreach ($pagemodules as $pagemodule) {
			if ($pagemodule->match_request($request))
				return $pagemodule->process_request($request);
		}

		return include ILYA_INCLUDE_DIR . 'ilya-page-not-found.php';
		break;
}


// Filter out blanks to get list of valid options

$getoptions = array();
foreach ($showoptions as $optionname) {
	if (strlen($optionname) && (strpos($optionname, '/') === false)) // empties represent spacers in forms
		$getoptions[] = $optionname;
}


// Process user actions

$errors = array();

$recalchotness = false;
$startmailing = false;
$securityexpired = false;

$formokhtml = null;

// If the post_max_size is exceeded then the $_POST array is empty so no field processing can be done
if (ilya_post_limit_exceeded())
	$errors['avatar_default_show'] = ilya_lang('main/file_upload_limit_exceeded');
else {
	if (ilya_clicked('doresetoptions')) {
		if (!ilya_check_form_security_code('admin/' . $adminsection, ilya_post_text('code')))
			$securityexpired = true;

		else {
			ilya_reset_options($getoptions);
			$formokhtml = ilya_lang_html('admin/options_reset');
		}
	} elseif (ilya_clicked('dosaveoptions')) {
		if (!ilya_check_form_security_code('admin/' . $adminsection, ilya_post_text('code')))
			$securityexpired = true;

		else {
			foreach ($getoptions as $optionname) {
				$optionvalue = ilya_post_text('option_' . $optionname);

				if (@$optiontype[$optionname] == 'number' || @$optiontype[$optionname] == 'checkbox' ||
					(@$optiontype[$optionname] == 'number-blank' && strlen($optionvalue))
				)
					$optionvalue = (int)$optionvalue;

				if (isset($optionmaximum[$optionname]))
					$optionvalue = min($optionmaximum[$optionname], $optionvalue);

				if (isset($optionminimum[$optionname]))
					$optionvalue = max($optionminimum[$optionname], $optionvalue);

				switch ($optionname) {
					case 'site_url':
						if (substr($optionvalue, -1) != '/') // seems to be a very common mistake and will mess up URLs
							$optionvalue .= '/';
						break;

					case 'hot_weight_views':
					case 'hot_weight_answers':
					case 'hot_weight_votes':
					case 'hot_weight_q_age':
					case 'hot_weight_a_age':
						if (ilya_opt($optionname) != $optionvalue)
							$recalchotness = true;
						break;

					case 'block_ips_write':
						require_once ILYA_INCLUDE_DIR . 'app/limits.php';
						$optionvalue = implode(' , ', ilya_block_ips_explode($optionvalue));
						break;

					case 'block_bad_words':
					case 'block_bad_usernames':
						require_once ILYA_INCLUDE_DIR . 'util/string.php';
						$optionvalue = implode(' , ', ilya_block_words_explode($optionvalue));
						break;
				}

				ilya_set_option($optionname, $optionvalue);
			}

			$formokhtml = ilya_lang_html('admin/options_saved');

			// Uploading default avatar
			if (is_array(@$_FILES['avatar_default_file'])) {
				$avatarfileerror = $_FILES['avatar_default_file']['error'];

				// Note if $_FILES['avatar_default_file']['error'] === 1 then upload_max_filesize has been exceeded
				if ($avatarfileerror === 1) {
					$errors['avatar_default_show'] = ilya_lang('main/file_upload_limit_exceeded');
				} elseif ($avatarfileerror === 0 && $_FILES['avatar_default_file']['size'] > 0) {
					require_once ILYA_INCLUDE_DIR . 'util/image.php';

					$oldblobid = ilya_opt('avatar_default_blobid');

					$toobig = ilya_image_file_too_big($_FILES['avatar_default_file']['tmp_name'], ilya_opt('avatar_store_size'));

					if ($toobig) {
						$errors['avatar_default_show'] = ilya_lang_sub('main/image_too_big_x_pc', (int)($toobig * 100));
					} else {
						$imagedata = ilya_image_constrain_data(file_get_contents($_FILES['avatar_default_file']['tmp_name']), $width, $height, ilya_opt('avatar_store_size'));

						if (isset($imagedata)) {
							require_once ILYA_INCLUDE_DIR . 'app/blobs.php';

							$newblobid = ilya_create_blob($imagedata, 'jpeg');

							if (isset($newblobid)) {
								ilya_set_option('avatar_default_blobid', $newblobid);
								ilya_set_option('avatar_default_width', $width);
								ilya_set_option('avatar_default_height', $height);
								ilya_set_option('avatar_default_show', 1);
							}

							if (strlen($oldblobid))
								ilya_delete_blob($oldblobid);
						} else {
							$errors['avatar_default_show'] = ilya_lang_sub('main/image_not_read', implode(', ', ilya_gd_image_formats()));
						}
					}
				}
			}
		}
	}
}


// Mailings management

if ($adminsection == 'mailing') {
	if (ilya_clicked('domailingtest') || ilya_clicked('domailingstart') || ilya_clicked('domailingresume') || ilya_clicked('domailingcancel')) {
		if (!ilya_check_form_security_code('admin/' . $adminsection, ilya_post_text('code'))) {
			$securityexpired = true;
		} else {
			if (ilya_clicked('domailingtest')) {
				$email = ilya_get_logged_in_email();

				if (ilya_mailing_send_one(ilya_get_logged_in_userid(), ilya_get_logged_in_handle(), $email, ilya_get_logged_in_user_field('emailcode')))
					$formokhtml = ilya_lang_html_sub('admin/test_sent_to_x', ilya_html($email));
				else
					$formokhtml = ilya_lang_html('main/general_error');
			}

			if (ilya_clicked('domailingstart')) {
				ilya_mailing_start();
				$startmailing = true;
			}

			if (ilya_clicked('domailingresume'))
				$startmailing = true;

			if (ilya_clicked('domailingcancel'))
				ilya_mailing_stop();
		}
	}

	$mailingprogress = ilya_mailing_progress_message();

	if (isset($mailingprogress)) {
		$formokhtml = ilya_html($mailingprogress);

		$checkboxtodisplay = array(
			'mailing_enabled' => '0',
		);

	} else {
		$checkboxtodisplay = array(
			'mailing_from_name' => 'option_mailing_enabled',
			'mailing_from_email' => 'option_mailing_enabled',
			'mailing_subject' => 'option_mailing_enabled',
			'mailing_body' => 'option_mailing_enabled',
			'mailing_per_minute' => 'option_mailing_enabled',
			'domailingtest' => 'option_mailing_enabled',
			'domailingstart' => 'option_mailing_enabled',
		);
	}
}


// Get the actual options

$options = ilya_get_options($getoptions);


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/admin_title') . ' - ' . ilya_lang_html($subtitle);
$ilya_content['error'] = $securityexpired ? ilya_lang_html('admin/form_security_expired') : ilya_admin_page_error();

$ilya_content['script_rel'][] = 'ilya-content/ilya-admin.js?' . ILYA_VERSION;

$ilya_content['form'] = array(
	'ok' => $formokhtml,

	'tags' => 'method="post" action="' . ilya_self_html() . '" name="admin_form" onsubmit="document.forms.admin_form.has_js.value=1; return true;"',

	'style' => $formstyle,

	'fields' => array(),

	'buttons' => array(
		'save' => array(
			'tags' => 'id="dosaveoptions"',
			'label' => ilya_lang_html('admin/save_options_button'),
		),

		'reset' => array(
			'tags' => 'name="doresetoptions" onclick="return confirm(' . ilya_js(ilya_lang_html('admin/reset_options_confirm')) . ');"',
			'label' => ilya_lang_html('admin/reset_options_button'),
		),
	),

	'hidden' => array(
		'dosaveoptions' => '1', // for IE
		'has_js' => '0',
		'code' => ilya_get_form_security_code('admin/' . $adminsection),
	),
);

if ($recalchotness) {
	$ilya_content['form']['ok'] = '<span id="recalc_ok"></span>';
	$ilya_content['form']['hidden']['code_recalc'] = ilya_get_form_security_code('admin/recalc');

	$ilya_content['script_var']['ilya_warning_recalc'] = ilya_lang('admin/stop_recalc_warning');

	$ilya_content['script_onloads'][] = array(
		"ilya_recalc_click('dorecountposts', document.getElementById('dosaveoptions'), null, 'recalc_ok');"
	);

} elseif ($startmailing) {
	if (ilya_post_text('has_js')) {
		$ilya_content['form']['ok'] = '<span id="mailing_ok">' . ilya_html($mailingprogress) . '</span>';

		$ilya_content['script_onloads'][] = array(
			"ilya_mailing_start('mailing_ok', 'domailingpause');"
		);

	} else { // rudimentary non-Javascript version of mass mailing loop
		echo '<code>';

		while (true) {
			ilya_mailing_perform_step();

			$message = ilya_mailing_progress_message();

			if (!isset($message))
				break;

			echo ilya_html($message) . str_repeat('    ', 1024) . "<br>\n";

			flush();
			sleep(1);
		}

		echo ilya_lang_html('admin/mailing_complete').'</code><p><a href="'.ilya_path_html('admin/mailing').'">'.ilya_lang_html('admin/admin_title').' - '.ilya_lang_html('admin/mailing_title').'</a>';

		ilya_exit();
	}
}


function ilya_optionfield_make_select(&$optionfield, $options, $value, $default)
{
	$optionfield['type'] = 'select';
	$optionfield['options'] = $options;
	$optionfield['value'] = isset($options[ilya_html($value)]) ? $options[ilya_html($value)] : @$options[$default];
}

$indented = false;

foreach ($showoptions as $optionname) {
	if (empty($optionname)) {
		$indented = false;

		$ilya_content['form']['fields'][] = array(
			'type' => 'blank'
		);

	} elseif (strpos($optionname, '/') !== false) {
		$ilya_content['form']['fields'][] = array(
			'type' => 'static',
			'label' => ilya_lang_html($optionname),
		);

		$indented = true;

	} else {
		$type = @$optiontype[$optionname];
		if ($type == 'number-blank')
			$type = 'number';

		$value = $options[$optionname];

		$optionfield = array(
			'id' => $optionname,
			'label' => ($indented ? '&ndash; ' : '') . ilya_lang_html('options/' . $optionname),
			'tags' => 'name="option_' . $optionname . '" id="option_' . $optionname . '"',
			'value' => ilya_html($value),
			'type' => $type,
			'error' => ilya_html(@$errors[$optionname]),
		);

		if (isset($optionmaximum[$optionname]))
			$optionfield['note'] = ilya_lang_html_sub('admin/maximum_x', $optionmaximum[$optionname]);

		$feedrequest = null;
		$feedisexample = false;

		switch ($optionname) { // special treatment for certain options
			case 'site_language':
				require_once ILYA_INCLUDE_DIR . 'util/string.php';

				ilya_optionfield_make_select($optionfield, ilya_admin_language_options(), $value, '');

				$optionfield['suffix'] = strtr(ilya_lang_html('admin/check_language_suffix'), array(
					'^1' => '<a href="' . ilya_html(ilya_path_to_root() . 'ilya-include/ilya-check-lang.php') . '">',
					'^2' => '</a>',
				));

				if (!ilya_has_multibyte())
					$optionfield['error'] = ilya_lang_html('admin/no_multibyte');
				break;

			case 'neat_urls':
				$neatoptions = array();

				$rawoptions = array(
					ILYA_URL_FORMAT_NEAT,
					ILYA_URL_FORMAT_INDEX,
					ILYA_URL_FORMAT_PARAM,
					ILYA_URL_FORMAT_PARAMS,
					ILYA_URL_FORMAT_SAFEST,
				);

				foreach ($rawoptions as $rawoption) {
					$neatoptions[$rawoption] =
						'<iframe src="' . ilya_path_html('url/test/' . ILYA_URL_TEST_STRING, array('dummy' => '', 'param' => ILYA_URL_TEST_STRING), null, $rawoption) . '" width="20" height="16" style="vertical-align:middle; border:0" scrolling="no"></iframe>&nbsp;' .
						'<small>' .
						ilya_html(urldecode(ilya_path('123/why-do-birds-sing', null, '/', $rawoption))) .
						(($rawoption == ILYA_URL_FORMAT_NEAT) ? strtr(ilya_lang_html('admin/neat_urls_note'), array(
							'^1' => '<a href="https://projekt.ir/htaccess.php" target="_blank">',
							'^2' => '</a>',
						)) : '') .
						'</small>';
				}

				ilya_optionfield_make_select($optionfield, $neatoptions, $value, ILYA_URL_FORMAT_SAFEST);

				$optionfield['type'] = 'select-radio';
				$optionfield['note'] = ilya_lang_html_sub('admin/url_format_note', '<span style=" ' . ilya_admin_url_test_html() . '/span>');
				break;

			case 'site_theme':
			case 'site_theme_mobile':
				$themeoptions = ilya_admin_theme_options();
				if (!isset($themeoptions[$value]))
					$value = 'Classic'; // check here because we also need $value for ilya_addon_metadata()

				ilya_optionfield_make_select($optionfield, $themeoptions, $value, 'Classic');

				$metadataUtil = new ILYA_Util_Metadata();
				$themedirectory = ILYA_THEME_DIR . $value;
				$metadata = $metadataUtil->fetchFromAddonPath($themedirectory);
				if (empty($metadata)) {
					// limit theme parsing to first 8kB
					$contents = @file_get_contents($themedirectory . '/ilya-styles.css', false, null, 0, 8192);
					$metadata = ilya_addon_metadata($contents, 'Theme');
				}

				if (strlen(@$metadata['version']))
					$namehtml = 'v' . ilya_html($metadata['version']);
				else
					$namehtml = '';

				if (strlen(@$metadata['uri'])) {
					if (!strlen($namehtml))
						$namehtml = ilya_html($value);

					$namehtml = '<a href="' . ilya_html($metadata['uri']) . '">' . $namehtml . '</a>';
				}

				$authorhtml = '';
				if (strlen(@$metadata['author'])) {
					$authorhtml = ilya_html($metadata['author']);

					if (strlen(@$metadata['author_uri']))
						$authorhtml = '<a href="' . ilya_html($metadata['author_uri']) . '">' . $authorhtml . '</a>';

					$authorhtml = ilya_lang_html_sub('main/by_x', $authorhtml);

				}

				$updatehtml = '';
				if (strlen(@$metadata['version']) && strlen(@$metadata['update_uri'])) {
					$elementid = 'version_check_' . $optionname;

					$updatehtml = '(<span id="' . $elementid . '">...</span>)';

					$ilya_content['script_onloads'][] = array(
						"ilya_version_check(" . ilya_js($metadata['update_uri']) . ", " . ilya_js($metadata['version'], true) . ", " . ilya_js($elementid) . ", false);"
					);

				}

				$optionfield['suffix'] = $namehtml . ' ' . $authorhtml . ' ' . $updatehtml;
				break;

			case 'site_text_direction':
				$directions = array('ltr' => 'LTR', 'rtl' => 'RTL');
				ilya_optionfield_make_select($optionfield, $directions, $value, 'ltr');
				break;

			case 'tags_or_categories':
				ilya_optionfield_make_select($optionfield, array(
					'' => ilya_lang_html('admin/no_classification'),
					't' => ilya_lang_html('admin/tags'),
					'c' => ilya_lang_html('admin/categories'),
					'tc' => ilya_lang_html('admin/tags_and_categories'),
				), $value, 'tc');

				$optionfield['error'] = '';

				if (ilya_opt('cache_tagcount') && !ilya_using_tags())
					$optionfield['error'] .= ilya_lang_html('admin/tags_not_shown') . ' ';

				if (!ilya_using_categories()) {
					foreach ($categories as $category) {
						if ($category['qcount']) {
							$optionfield['error'] .= ilya_lang_html('admin/categories_not_shown');
							break;
						}
					}
				}
				break;

			case 'smtp_secure':
				ilya_optionfield_make_select($optionfield, array(
					'' => ilya_lang_html('options/smtp_secure_none'),
					'ssl' => 'SSL',
					'tls' => 'TLS',
				), $value, '');
				break;

			case 'custom_sidebar':
			case 'custom_sidepanel':
			case 'custom_header':
			case 'custom_footer':
			case 'custom_in_head':
			case 'home_description':
				unset($optionfield['label']);
				$optionfield['rows'] = 6;
				break;

			case 'custom_home_content':
				$optionfield['rows'] = 16;
				break;

			case 'show_custom_register':
			case 'show_register_terms':
			case 'show_custom_welcome':
			case 'show_notice_welcome':
			case 'show_notice_visitor':
				$optionfield['style'] = 'tall';
				break;

			case 'custom_register':
			case 'register_terms':
			case 'custom_welcome':
			case 'notice_welcome':
			case 'notice_visitor':
				unset($optionfield['label']);
				$optionfield['style'] = 'tall';
				$optionfield['rows'] = 3;
				break;

			case 'avatar_allow_gravatar':
				$optionfield['label'] = strtr($optionfield['label'], array(
					'^1' => '<a href="http://www.gravatar.com/" target="_blank">',
					'^2' => '</a>',
				));

				if (!ilya_has_gd_image()) {
					$optionfield['style'] = 'tall';
					$optionfield['error'] = ilya_lang_html('admin/no_image_gd');
				}
				break;

			case 'avatar_store_size':
			case 'avatar_profile_size':
			case 'avatar_users_size':
			case 'avatar_q_page_q_size':
			case 'avatar_q_page_a_size':
			case 'avatar_q_page_c_size':
			case 'avatar_q_list_size':
			case 'avatar_message_list_size':
				$optionfield['note'] = ilya_lang_html('admin/pixels');
				break;

			case 'avatar_default_show':
				$ilya_content['form']['tags'] .= 'enctype="multipart/form-data"';
				$optionfield['label'] .= ' <span style="margin:2px 0; display:inline-block;">' .
					ilya_get_avatar_blob_html(ilya_opt('avatar_default_blobid'), ilya_opt('avatar_default_width'), ilya_opt('avatar_default_height'), 32) .
					'</span> <input name="avatar_default_file" type="file" style="width:16em;">';
				break;

			case 'logo_width':
			case 'logo_height':
				$optionfield['suffix'] = ilya_lang_html('admin/pixels');
				break;

			case 'pages_prev_next':
				ilya_optionfield_make_select($optionfield, array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5), $value, 3);
				break;

			case 'columns_tags':
			case 'columns_users':
				ilya_optionfield_make_select($optionfield, array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5), $value, 2);
				break;

			case 'min_len_q_title':
			case 'q_urls_title_length':
			case 'min_len_q_content':
			case 'min_len_a_content':
			case 'min_len_c_content':
				$optionfield['note'] = ilya_lang_html('admin/characters');
				break;

			case 'recalc_hotness_q_view':
				$optionfield['note'] = '<span class="ilya-form-wide-help" title="' . ilya_lang_html('admin/recalc_hotness_q_view_note') . '">?</span>';
				break;

			case 'min_num_q_tags':
			case 'max_num_q_tags':
				$optionfield['note'] = ilya_lang_html_sub('main/x_tags', ''); // this to avoid language checking error: a_lang('main/1_tag')
				break;

			case 'show_full_date_days':
				$optionfield['note'] = ilya_lang_html_sub('main/x_days', '');
				break;

			case 'sort_answers_by':
				ilya_optionfield_make_select($optionfield, array(
					'created' => ilya_lang_html('options/sort_time'),
					'votes' => ilya_lang_html('options/sort_votes'),
				), $value, 'created');
				break;

			case 'page_size_q_as':
				$optionfield['note'] = ilya_lang_html_sub('main/x_answers', '');
				break;

			case 'show_a_form_immediate':
				ilya_optionfield_make_select($optionfield, array(
					'always' => ilya_lang_html('options/show_always'),
					'if_no_as' => ilya_lang_html('options/show_if_no_as'),
					'never' => ilya_lang_html('options/show_never'),
				), $value, 'if_no_as');
				break;

			case 'show_fewer_cs_from':
			case 'show_fewer_cs_count':
				$optionfield['note'] = ilya_lang_html_sub('main/x_comments', '');
				break;

			case 'match_related_qs':
			case 'match_ask_check_qs':
			case 'match_example_tags':
				ilya_optionfield_make_select($optionfield, ilya_admin_match_options(), $value, 3);
				break;

			case 'block_bad_words':
			case 'block_bad_usernames':
				$optionfield['style'] = 'tall';
				$optionfield['rows'] = 4;
				$optionfield['note'] = ilya_lang_html('admin/block_words_note');
				break;

			case 'editor_for_qs':
			case 'editor_for_as':
			case 'editor_for_cs':
				$editors = ilya_list_modules('editor');

				$selectoptions = array();

				foreach ($editors as $editor) {
					$selectoptions[ilya_html($editor)] = strlen($editor) ? ilya_html($editor) : ilya_lang_html('admin/basic_editor');

					if ($editor == $value) {
						$module = ilya_load_module('editor', $editor);

						if (method_exists($module, 'admin_form')) {
							$optionfield['note'] = '<a href="' . ilya_admin_module_options_path('editor', $editor) . '">' . ilya_lang_html('admin/options') . '</a>';
						}
					}
				}

				ilya_optionfield_make_select($optionfield, $selectoptions, $value, '');
				break;

			case 'show_custom_ask':
			case 'extra_field_active':
			case 'show_custom_answer':
			case 'show_custom_comment':
				$optionfield['style'] = 'tall';
				break;

			case 'custom_ask':
			case 'custom_answer':
			case 'custom_comment':
				$optionfield['style'] = 'tall';
				unset($optionfield['label']);
				$optionfield['rows'] = 3;
				break;

			case 'extra_field_display':
				$optionfield['style'] = 'tall';
				$optionfield['label'] = '<span id="extra_field_label_hidden" style="display:none;">' . $optionfield['label'] . '</span><span id="extra_field_label_shown">' . ilya_lang_html('options/extra_field_display_label') . '</span>';
				break;

			case 'extra_field_prompt':
			case 'extra_field_label':
				$optionfield['style'] = 'tall';
				unset($optionfield['label']);
				break;

			case 'search_module':
				foreach ($searchmodules as $modulename => $module) {
					$selectoptions[ilya_html($modulename)] = strlen($modulename) ? ilya_html($modulename) : ilya_lang_html('options/option_default');

					if ($modulename == $value && method_exists($module, 'admin_form')) {
						$optionfield['note'] = '<a href="' . ilya_admin_module_options_path('search', $modulename) . '">' . ilya_lang_html('admin/options') . '</a>';
					}
				}

				ilya_optionfield_make_select($optionfield, $selectoptions, $value, '');
				break;

			case 'hot_weight_q_age':
			case 'hot_weight_a_age':
			case 'hot_weight_answers':
			case 'hot_weight_votes':
			case 'hot_weight_views':
				$optionfield['note'] = '/ 100';
				break;

			case 'moderate_by_points':
				$optionfield['label'] = '<span id="moderate_points_label_off" style="display:none;">' . $optionfield['label'] . '</span><span id="moderate_points_label_on">' . ilya_lang_html('options/moderate_points_limit') . '</span>';
				break;

			case 'moderate_points_limit':
				unset($optionfield['label']);
				$optionfield['note'] = ilya_lang_html('admin/points');
				break;

			case 'flagging_hide_after':
			case 'flagging_notify_every':
			case 'flagging_notify_first':
				$optionfield['note'] = ilya_lang_html_sub('main/x_flags', '');
				break;

			case 'block_ips_write':
				$optionfield['style'] = 'tall';
				$optionfield['rows'] = 4;
				$optionfield['note'] = ilya_lang_html('admin/block_ips_note');
				break;

			case 'allow_view_q_bots':
				$optionfield['note'] = $optionfield['label'];
				unset($optionfield['label']);
				break;

			case 'permit_view_q_page':
			case 'permit_view_new_users_page':
			case 'permit_view_special_users_page':
			case 'permit_post_q':
			case 'permit_post_a':
			case 'permit_post_c':
			case 'permit_vote_q':
			case 'permit_vote_a':
			case 'permit_vote_c':
			case 'permit_vote_down':
			case 'permit_edit_q':
			case 'permit_retag_cat':
			case 'permit_edit_a':
			case 'permit_edit_c':
			case 'permit_edit_silent':
			case 'permit_flag':
			case 'permit_close_q':
			case 'permit_select_a':
			case 'permit_hide_show':
			case 'permit_moderate':
			case 'permit_delete_hidden':
			case 'permit_anon_view_ips':
			case 'permit_view_voters_flaggers':
			case 'permit_post_wall':
				$dopoints = true;

				if ($optionname == 'permit_retag_cat')
					$optionfield['label'] = ilya_lang_html(ilya_using_categories() ? 'profile/permit_recat' : 'profile/permit_retag') . ':';
				else
					$optionfield['label'] = ilya_lang_html('profile/' . $optionname) . ':';

				if (in_array($optionname, array('permit_view_q_page', 'permit_view_new_users_page', 'permit_view_special_users_page', 'permit_post_q', 'permit_post_a', 'permit_post_c', 'permit_anon_view_ips')))
					$widest = ILYA_PERMIT_ALL;
				elseif ($optionname == 'permit_close_q' || $optionname == 'permit_select_a' || $optionname == 'permit_moderate' || $optionname == 'permit_hide_show')
					$widest = ILYA_PERMIT_POINTS;
				elseif ($optionname == 'permit_delete_hidden')
					$widest = ILYA_PERMIT_EDITORS;
				elseif ($optionname == 'permit_view_voters_flaggers' || $optionname == 'permit_edit_silent')
					$widest = ILYA_PERMIT_EXPERTS;
				else
					$widest = ILYA_PERMIT_USERS;

				if ($optionname == 'permit_view_q_page') {
					$narrowest = ILYA_PERMIT_APPROVED;
					$dopoints = false;
				} elseif ($optionname == 'permit_view_special_users_page' || $optionname == 'permit_view_new_users_page') {
					$narrowest = ILYA_PERMIT_SUPERS;
					$dopoints = false;
				} elseif ($optionname == 'permit_edit_c' || $optionname == 'permit_close_q' || $optionname == 'permit_select_a' || $optionname == 'permit_moderate' || $optionname == 'permit_hide_show' || $optionname == 'permit_anon_view_ips')
					$narrowest = ILYA_PERMIT_MODERATORS;
				elseif ($optionname == 'permit_post_c' || $optionname == 'permit_edit_q' || $optionname == 'permit_retag_cat' || $optionname == 'permit_edit_a' || $optionname == 'permit_flag')
					$narrowest = ILYA_PERMIT_EDITORS;
				elseif ($optionname == 'permit_vote_q' || $optionname == 'permit_vote_a' || $optionname == 'permit_vote_c' || $optionname == 'permit_post_wall')
					$narrowest = ILYA_PERMIT_APPROVED_POINTS;
				elseif ($optionname == 'permit_delete_hidden' || $optionname == 'permit_edit_silent')
					$narrowest = ILYA_PERMIT_ADMINS;
				elseif ($optionname == 'permit_view_voters_flaggers')
					$narrowest = ILYA_PERMIT_SUPERS;
				else
					$narrowest = ILYA_PERMIT_EXPERTS;

				$permitoptions = ilya_admin_permit_options($widest, $narrowest, (!ILYA_FINAL_EXTERNAL_USERS) && ilya_opt('confirm_user_emails'), $dopoints);

				if (count($permitoptions) > 1) {
					ilya_optionfield_make_select($optionfield, $permitoptions, $value,
						($value == ILYA_PERMIT_CONFIRMED) ? ILYA_PERMIT_USERS : min(array_keys($permitoptions)));
				} else {
					$optionfield['type'] = 'static';
					$optionfield['value'] = reset($permitoptions);
				}
				break;

			case 'permit_post_q_points':
			case 'permit_post_a_points':
			case 'permit_post_c_points':
			case 'permit_vote_q_points':
			case 'permit_vote_a_points':
			case 'permit_vote_c_points':
			case 'permit_vote_down_points':
			case 'permit_flag_points':
			case 'permit_edit_q_points':
			case 'permit_retag_cat_points':
			case 'permit_edit_a_points':
			case 'permit_edit_c_points':
			case 'permit_close_q_points':
			case 'permit_select_a_points':
			case 'permit_hide_show_points':
			case 'permit_moderate_points':
			case 'permit_delete_hidden_points':
			case 'permit_anon_view_ips_points':
			case 'permit_post_wall_points':
				unset($optionfield['label']);
				$optionfield['type'] = 'number';
				$optionfield['prefix'] = ilya_lang_html('admin/users_must_have') . '&nbsp;';
				$optionfield['note'] = ilya_lang_html('admin/points');
				break;

			case 'feed_for_ilya':
				$feedrequest = 'ilya';
				break;

			case 'feed_for_questions':
				$feedrequest = 'questions';
				break;

			case 'feed_for_hot':
				$feedrequest = 'hot';
				break;

			case 'feed_for_unanswered':
				$feedrequest = 'unanswered';
				break;

			case 'feed_for_activity':
				$feedrequest = 'activity';
				break;

			case 'feed_per_category':
				if (count($categories)) {
					$category = reset($categories);
					$categoryslug = $category['tags'];

				} else
					$categoryslug = 'example-category';

				if (ilya_opt('feed_for_ilya'))
					$feedrequest = 'ilya';
				elseif (ilya_opt('feed_for_questions'))
					$feedrequest = 'questions';
				else
					$feedrequest = 'activity';

				$feedrequest .= '/' . $categoryslug;
				$feedisexample = true;
				break;

			case 'feed_for_tag_qs':
				$populartags = ilya_db_select_with_pending(ilya_db_popular_tags_selectspec(0, 1));

				if (count($populartags)) {
					reset($populartags);
					$feedrequest = 'tag/' . key($populartags);
				} else
					$feedrequest = 'tag/singing';

				$feedisexample = true;
				break;

			case 'feed_for_search':
				$feedrequest = 'search/why do birds sing';
				$feedisexample = true;
				break;

			case 'moderate_users':
				$optionfield['note'] = '<a href="' . ilya_path_html('admin/users', null, null, null, 'profile_fields') . '">' . ilya_lang_html('admin/registration_fields') . '</a>';
				break;

			case 'captcha_module':
				$captchaoptions = array();

				foreach ($captchamodules as $modulename) {
					$captchaoptions[ilya_html($modulename)] = ilya_html($modulename);

					if ($modulename == $value) {
						$module = ilya_load_module('captcha', $modulename);

						if (method_exists($module, 'admin_form')) {
							$optionfield['note'] = '<a href="' . ilya_admin_module_options_path('captcha', $modulename) . '">' . ilya_lang_html('admin/options') . '</a>';
						}
					}
				}

				ilya_optionfield_make_select($optionfield, $captchaoptions, $value, '');
				break;

			case 'moderate_update_time':
				ilya_optionfield_make_select($optionfield, array(
					'0' => ilya_lang_html('options/time_written'),
					'1' => ilya_lang_html('options/time_approved'),
				), $value, '0');
				break;

			case 'max_rate_ip_as':
			case 'max_rate_ip_cs':
			case 'max_rate_ip_flags':
			case 'max_rate_ip_logins':
			case 'max_rate_ip_messages':
			case 'max_rate_ip_qs':
			case 'max_rate_ip_registers':
			case 'max_rate_ip_uploads':
			case 'max_rate_ip_votes':
				$optionfield['note'] = ilya_lang_html('admin/per_ip_hour');
				break;

			case 'max_rate_user_as':
			case 'max_rate_user_cs':
			case 'max_rate_user_flags':
			case 'max_rate_user_messages':
			case 'max_rate_user_qs':
			case 'max_rate_user_uploads':
			case 'max_rate_user_votes':
				unset($optionfield['label']);
				$optionfield['note'] = ilya_lang_html('admin/per_user_hour');
				break;

			case 'mailing_per_minute':
				$optionfield['suffix'] = ilya_lang_html('admin/emails_per_minute');
				break;

			case 'caching_driver':
				ilya_optionfield_make_select($optionfield, array(
					'filesystem' => ilya_lang_html('options/caching_filesystem'),
					'memcached' => ilya_lang_html('options/caching_memcached'),
				), $value, 'filesystem');
				break;

			case 'caching_q_time':
			case 'caching_qlist_time':
			case 'caching_catwidget_time':
				$optionfield['note'] = ilya_lang_html_sub('main/x_minutes', '');
				break;
			case 'caching_q_start':
				$optionfield['note'] = ilya_lang_html_sub('main/x_days', '');
				break;
		}

		if (isset($feedrequest) && $value) {
			$optionfield['note'] = '<a href="' . ilya_path_html(ilya_feed_request($feedrequest)) . '">' . ilya_lang_html($feedisexample ? 'admin/feed_link_example' : 'admin/feed_link') . '</a>';
		}

		$ilya_content['form']['fields'][$optionname] = $optionfield;
	}
}


// Extra items for specific pages

switch ($adminsection) {
	case 'users':
		require_once ILYA_INCLUDE_DIR . 'app/format.php';

		if (!ILYA_FINAL_EXTERNAL_USERS) {
			$userfields = ilya_db_single_select(ilya_db_userfields_selectspec());

			$listhtml = '';

			foreach ($userfields as $userfield) {
				$listhtml .= '<li><b>' . ilya_html(ilya_user_userfield_label($userfield)) . '</b>';

				$listhtml .= strtr(ilya_lang_html('admin/edit_field'), array(
					'^1' => '<a href="' . ilya_path_html('admin/userfields', array('edit' => $userfield['fieldid'])) . '">',
					'^2' => '</a>',
				));

				$listhtml .= '</li>';
			}

			$listhtml .= '<li><b><a href="' . ilya_path_html('admin/userfields') . '">' . ilya_lang_html('admin/add_new_field') . '</a></b></li>';

			$ilya_content['form']['fields'][] = array('type' => 'blank');

			$ilya_content['form']['fields']['userfields'] = array(
				'label' => ilya_lang_html('admin/profile_fields'),
				'id' => 'profile_fields',
				'style' => 'tall',
				'type' => 'custom',
				'html' => strlen($listhtml) ? '<ul style="margin-bottom:0;">' . $listhtml . '</ul>' : null,
			);
		}

		$ilya_content['form']['fields'][] = array('type' => 'blank');

		$pointstitle = ilya_get_points_to_titles();

		$listhtml = '';

		foreach ($pointstitle as $points => $title) {
			$listhtml .= '<li><b>' . $title . '</b> - ' . (($points == 1) ? ilya_lang_html_sub('main/1_point', '1', '1')
					: ilya_lang_html_sub('main/x_points', ilya_html(ilya_format_number($points))));

			$listhtml .= strtr(ilya_lang_html('admin/edit_title'), array(
				'^1' => '<a href="' . ilya_path_html('admin/usertitles', array('edit' => $points)) . '">',
				'^2' => '</a>',
			));

			$listhtml .= '</li>';
		}

		$listhtml .= '<li><b><a href="' . ilya_path_html('admin/usertitles') . '">' . ilya_lang_html('admin/add_new_title') . '</a></b></li>';

		$ilya_content['form']['fields']['usertitles'] = array(
			'label' => ilya_lang_html('admin/user_titles'),
			'style' => 'tall',
			'type' => 'custom',
			'html' => strlen($listhtml) ? '<ul style="margin-bottom:0;">' . $listhtml . '</ul>' : null,
		);
		break;

	case 'layout':
		$listhtml = '';

		$widgetmodules = ilya_load_modules_with('widget', 'allow_template');

		foreach ($widgetmodules as $tryname => $trywidget) {
			if (method_exists($trywidget, 'allow_region')) {
				$listhtml .= '<li><b>' . ilya_html($tryname) . '</b>';

				$listhtml .= strtr(ilya_lang_html('admin/add_widget_link'), array(
					'^1' => '<a href="' . ilya_path_html('admin/layoutwidgets', array('title' => $tryname)) . '">',
					'^2' => '</a>',
				));

				if (method_exists($trywidget, 'admin_form'))
					$listhtml .= strtr(ilya_lang_html('admin/widget_global_options'), array(
						'^1' => '<a href="' . ilya_admin_module_options_path('widget', $tryname) . '">',
						'^2' => '</a>',
					));

				$listhtml .= '</li>';
			}
		}

		if (strlen($listhtml)) {
			$ilya_content['form']['fields']['plugins'] = array(
				'label' => ilya_lang_html('admin/widgets_explanation'),
				'style' => 'tall',
				'type' => 'custom',
				'html' => '<ul style="margin-bottom:0;">' . $listhtml . '</ul>',
			);
		}

		$widgets = ilya_db_single_select(ilya_db_widgets_selectspec());

		$listhtml = '';

		$placeoptions = ilya_admin_place_options();

		foreach ($widgets as $widget) {
			$listhtml .= '<li><b>' . ilya_html($widget['title']) . '</b> - ' .
				'<a href="' . ilya_path_html('admin/layoutwidgets', array('edit' => $widget['widgetid'])) . '">' .
				@$placeoptions[$widget['place']] . '</a>';

			$listhtml .= '</li>';
		}

		if (strlen($listhtml)) {
			$ilya_content['form']['fields']['widgets'] = array(
				'label' => ilya_lang_html('admin/active_widgets_explanation'),
				'type' => 'custom',
				'html' => '<ul style="margin-bottom:0;">' . $listhtml . '</ul>',
			);
		}

		break;

	case 'permissions':
		$ilya_content['form']['fields']['permit_block'] = array(
			'type' => 'static',
			'label' => ilya_lang_html('options/permit_block'),
			'value' => ilya_lang_html('options/permit_moderators'),
		);

		if (!ILYA_FINAL_EXTERNAL_USERS) {
			$ilya_content['form']['fields']['permit_approve_users'] = array(
				'type' => 'static',
				'label' => ilya_lang_html('options/permit_approve_users'),
				'value' => ilya_lang_html('options/permit_moderators'),
			);

			$ilya_content['form']['fields']['permit_create_experts'] = array(
				'type' => 'static',
				'label' => ilya_lang_html('options/permit_create_experts'),
				'value' => ilya_lang_html('options/permit_moderators'),
			);

			$ilya_content['form']['fields']['permit_see_emails'] = array(
				'type' => 'static',
				'label' => ilya_lang_html('options/permit_see_emails'),
				'value' => ilya_lang_html('options/permit_admins'),
			);

			$ilya_content['form']['fields']['permit_delete_users'] = array(
				'type' => 'static',
				'label' => ilya_lang_html('options/permit_delete_users'),
				'value' => ilya_lang_html('options/permit_admins'),
			);

			$ilya_content['form']['fields']['permit_create_eds_mods'] = array(
				'type' => 'static',
				'label' => ilya_lang_html('options/permit_create_eds_mods'),
				'value' => ilya_lang_html('options/permit_admins'),
			);

			$ilya_content['form']['fields']['permit_create_admins'] = array(
				'type' => 'static',
				'label' => ilya_lang_html('options/permit_create_admins'),
				'value' => ilya_lang_html('options/permit_supers'),
			);
		}

		break;

	case 'mailing':
		require_once ILYA_INCLUDE_DIR . 'util/sort.php';

		if (isset($mailingprogress)) {
			unset($ilya_content['form']['buttons']['save']);
			unset($ilya_content['form']['buttons']['reset']);

			if ($startmailing) {
				unset($ilya_content['form']['hidden']['dosaveoptions']);

				foreach ($showoptions as $optionname)
					$ilya_content['form']['fields'][$optionname]['type'] = 'static';

				$ilya_content['form']['fields']['mailing_body']['value'] = ilya_html(ilya_opt('mailing_body'), true);

				$ilya_content['form']['buttons']['stop'] = array(
					'tags' => 'name="domailingpause" id="domailingpause"',
					'label' => ilya_lang_html('admin/pause_mailing_button'),
				);

			} else {
				$ilya_content['form']['buttons']['resume'] = array(
					'tags' => 'name="domailingresume"',
					'label' => ilya_lang_html('admin/resume_mailing_button'),
				);

				$ilya_content['form']['buttons']['cancel'] = array(
					'tags' => 'name="domailingcancel"',
					'label' => ilya_lang_html('admin/cancel_mailing_button'),
				);
			}
		} else {
			$ilya_content['form']['buttons']['spacer'] = array();

			$ilya_content['form']['buttons']['test'] = array(
				'tags' => 'name="domailingtest" id="domailingtest"',
				'label' => ilya_lang_html('admin/send_test_button'),
			);

			$ilya_content['form']['buttons']['start'] = array(
				'tags' => 'name="domailingstart" id="domailingstart"',
				'label' => ilya_lang_html('admin/start_mailing_button'),
			);
		}

		if (!$startmailing) {
			$ilya_content['form']['fields']['mailing_enabled']['note'] = ilya_lang_html('admin/mailing_explanation');
			$ilya_content['form']['fields']['mailing_body']['rows'] = 12;
			$ilya_content['form']['fields']['mailing_body']['note'] = ilya_lang_html('admin/mailing_unsubscribe');
		}
		break;

	case 'caching':
		$cacheDriver = ILYA_Storage_CacheFactory::getCacheDriver();
		$ilya_content['error'] = $cacheDriver->getError();
		$cacheStats = $cacheDriver->getStats();

		$ilya_content['form_2'] = array(
			'tags' => 'method="post" action="' . ilya_path_html('admin/recalc') . '"',

			'title' => ilya_lang_html('admin/caching_cleanup'),

			'style' => 'wide',

			'fields' => array(
				'cache_files' => array(
					'type' => 'static',
					'label' => ilya_lang_html('admin/caching_num_items'),
					'value' => ilya_html(ilya_format_number($cacheStats['files'])),
				),
				'cache_size' => array(
					'type' => 'static',
					'label' => ilya_lang_html('admin/caching_space_used'),
					'value' => ilya_html(ilya_format_number($cacheStats['size'] / 1048576, 1) . ' MB'),
				),
			),

			'buttons' => array(
				'delete_expired' => array(
					'label' => ilya_lang_html('admin/caching_delete_expired'),
					'tags' => 'name="docachetrim" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/delete_stop')) . ', \'cachetrim_note\');"',
					'note' => '<span id="cachetrim_note"></span>',
				),
				'delete_all' => array(
					'label' => ilya_lang_html('admin/caching_delete_all'),
					'tags' => 'name="docacheclear" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/delete_stop')) . ', \'cacheclear_note\');"',
					'note' => '<span id="cacheclear_note"></span>',
				),
			),

			'hidden' => array(
				'code' => ilya_get_form_security_code('admin/recalc'),
			),
		);
		break;
}


if (isset($checkboxtodisplay))
	ilya_set_display_rules($ilya_content, $checkboxtodisplay);

$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();


return $ilya_content;
