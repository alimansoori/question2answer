<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Routing and utility functions for page requests


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

require_once ILYA__INCLUDE_DIR . 'app/cookies.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';
require_once ILYA__INCLUDE_DIR . 'app/users.php';
require_once ILYA__INCLUDE_DIR . 'app/options.php';
require_once ILYA__INCLUDE_DIR . 'db/selects.php';


/**
 * Queue any pending requests which are required independent of which page will be shown
 */
function ilya_page_queue_pending()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	ilya_preload_options();
	$loginuserid = ilya_get_logged_in_userid();

	if (isset($loginuserid)) {
		if (!ILYA__FINAL_EXTERNAL_USERS)
			ilya_db_queue_pending_select('loggedinuser', ilya_db_user_account_selectspec($loginuserid, true));

		ilya_db_queue_pending_select('notices', ilya_db_user_notices_selectspec($loginuserid));
		ilya_db_queue_pending_select('favoritenonqs', ilya_db_user_favorite_non_qs_selectspec($loginuserid));
		ilya_db_queue_pending_select('userlimits', ilya_db_user_limits_selectspec($loginuserid));
		ilya_db_queue_pending_select('userlevels', ilya_db_user_levels_selectspec($loginuserid, true));
	}

	ilya_db_queue_pending_select('iplimits', ilya_db_ip_limits_selectspec(ilya_remote_ip_address()));
	ilya_db_queue_pending_select('navpages', ilya_db_pages_selectspec(array('B', 'M', 'O', 'F')));
	ilya_db_queue_pending_select('widgets', ilya_db_widgets_selectspec());
}


/**
 * Check the page state parameter and then remove it from the $_GET array
 */
function ilya_load_state()
{
	global $ilya_state;

	$ilya_state = ilya_get('state');
	unset($_GET['state']); // to prevent being passed through on forms
}


/**
 * If no user is logged in, call through to the login modules to see if they want to log someone in
 */
function ilya_check_login_modules()
{
	if (!ILYA__FINAL_EXTERNAL_USERS && !ilya_is_logged_in()) {
		$loginmodules = ilya_load_modules_with('login', 'check_login');

		foreach ($loginmodules as $loginmodule) {
			$loginmodule->check_login();
			if (ilya_is_logged_in()) // stop and reload page if it worked
				ilya_redirect(ilya_request(), $_GET);
		}
	}
}


/**
 * React to any of the common buttons on a page for voting, favorites and closing a notice
 * If the user has Javascript on, these should come through Ajax rather than here.
 */
function ilya_check_page_clicks()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_page_error_html;

	if (ilya_is_http_post()) {
		foreach ($_POST as $field => $value) {
			if (strpos($field, 'vote_') === 0) { // voting...
				@list($dummy, $postid, $vote, $anchor) = explode('_', $field);

				if (isset($postid) && isset($vote)) {
					if (!ilya_check_form_security_code('vote', ilya_post_text('code')))
						$ilya_page_error_html = ilya_lang_html('misc/form_security_again');

					else {
						require_once ILYA__INCLUDE_DIR . 'app/votes.php';
						require_once ILYA__INCLUDE_DIR . 'db/selects.php';

						$userid = ilya_get_logged_in_userid();

						$post = ilya_db_select_with_pending(ilya_db_full_post_selectspec($userid, $postid));
						$ilya_page_error_html = ilya_vote_error_html($post, $vote, $userid, ilya_request());

						if (!$ilya_page_error_html) {
							ilya_vote_set($post, $userid, ilya_get_logged_in_handle(), ilya_cookie_get(), $vote);
							ilya_redirect(ilya_request(), $_GET, null, null, $anchor);
						}
						break;
					}
				}

			} elseif (strpos($field, 'favorite_') === 0) { // favorites...
				@list($dummy, $entitytype, $entityid, $favorite) = explode('_', $field);

				if (isset($entitytype) && isset($entityid) && isset($favorite)) {
					if (!ilya_check_form_security_code('favorite-' . $entitytype . '-' . $entityid, ilya_post_text('code')))
						$ilya_page_error_html = ilya_lang_html('misc/form_security_again');

					else {
						require_once ILYA__INCLUDE_DIR . 'app/favorites.php';

						ilya_user_favorite_set(ilya_get_logged_in_userid(), ilya_get_logged_in_handle(), ilya_cookie_get(), $entitytype, $entityid, $favorite);
						ilya_redirect(ilya_request(), $_GET);
					}
				}

			} elseif (strpos($field, 'notice_') === 0) { // notices...
				@list($dummy, $noticeid) = explode('_', $field);

				if (isset($noticeid)) {
					if (!ilya_check_form_security_code('notice-' . $noticeid, ilya_post_text('code')))
						$ilya_page_error_html = ilya_lang_html('misc/form_security_again');

					else {
						if ($noticeid == 'visitor')
							setcookie('ilya_noticed', 1, time() + 86400 * 3650, '/', ILYA__COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true);

						elseif ($noticeid == 'welcome') {
							require_once ILYA__INCLUDE_DIR . 'db/users.php';
							ilya_db_user_set_flag(ilya_get_logged_in_userid(), ILYA__USER_FLAGS_WELCOME_NOTICE, false);

						} else {
							require_once ILYA__INCLUDE_DIR . 'db/notices.php';
							ilya_db_usernotice_delete(ilya_get_logged_in_userid(), $noticeid);
						}

						ilya_redirect(ilya_request(), $_GET);
					}
				}
			}
		}
	}
}


/**
 *	Run the appropriate /ilya-include/pages/*.php file for this request and return back the $ilya_content it passed
 */
function ilya_get_request_content()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$requestlower = strtolower(ilya_request());
	$requestparts = ilya_request_parts();
	$firstlower = strtolower($requestparts[0]);
	$routing = ilya_page_routing();

	if (isset($routing[$requestlower])) {
		ilya_set_template($firstlower);
		$ilya_content = require ILYA__INCLUDE_DIR . $routing[$requestlower];

	} elseif (isset($routing[$firstlower . '/'])) {
		ilya_set_template($firstlower);
		$ilya_content = require ILYA__INCLUDE_DIR . $routing[$firstlower . '/'];

	} elseif (is_numeric($requestparts[0])) {
		ilya_set_template('question');
		$ilya_content = require ILYA__INCLUDE_DIR . 'pages/question.php';

	} else {
		ilya_set_template(strlen($firstlower) ? $firstlower : 'ilya'); // will be changed later
		$ilya_content = require ILYA__INCLUDE_DIR . 'pages/default.php'; // handles many other pages, including custom pages and page modules
	}

	if ($firstlower == 'admin') {
		$_COOKIE['ilya_admin_last'] = $requestlower; // for navigation tab now...
		setcookie('ilya_admin_last', $_COOKIE['ilya_admin_last'], 0, '/', ILYA__COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true); // ...and in future
	}

	if (isset($ilya_content))
		ilya_set_form_security_key();

	return $ilya_content;
}


/**
 *    Output the $ilya_content via the theme class after doing some pre-processing, mainly relating to Javascript
 * @param $ilya_content
 * @return mixed
 */
function ilya_output_content($ilya_content)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_template;

	$requestlower = strtolower(ilya_request());

	// Set appropriate selected flags for navigation (not done in ilya_content_prepare() since it also applies to sub-navigation)

	foreach ($ilya_content['navigation'] as $navtype => $navigation) {
		if (!is_array($navigation) || $navtype == 'cat') {
			continue;
		}

		foreach ($navigation as $navprefix => $navlink) {
			$selected =& $ilya_content['navigation'][$navtype][$navprefix]['selected'];
			if (isset($navlink['selected_on'])) {
				// match specified paths
				foreach ($navlink['selected_on'] as $path) {
					if (strpos($requestlower . '$', $path) === 0)
						$selected = true;
				}
			} elseif ($requestlower === $navprefix || $requestlower . '$' === $navprefix) {
				// exact match for array key
				$selected = true;
			}
		}
	}

	// Slide down notifications

	if (!empty($ilya_content['notices'])) {
		foreach ($ilya_content['notices'] as $notice) {
			$ilya_content['script_onloads'][] = array(
				"ilya_reveal(document.getElementById(" . ilya_js($notice['id']) . "), 'notice');",
			);
		}
	}

	// Handle maintenance mode

	if (ilya_opt('site_maintenance') && ($requestlower != 'login')) {
		if (ilya_get_logged_in_level() >= ILYA__USER_LEVEL_ADMIN) {
			if (!isset($ilya_content['error'])) {
				$ilya_content['error'] = strtr(ilya_lang_html('admin/maintenance_admin_only'), array(
					'^1' => '<a href="' . ilya_path_html('admin/general') . '">',
					'^2' => '</a>',
				));
			}
		} else {
			$ilya_content = ilya_content_prepare();
			$ilya_content['error'] = ilya_lang_html('misc/site_in_maintenance');
		}
	}

	// Handle new users who must confirm their email now, or must be approved before continuing

	$userid = ilya_get_logged_in_userid();
	if (isset($userid) && $requestlower != 'confirm' && $requestlower != 'account') {
		$flags = ilya_get_logged_in_flags();

		if (($flags & ILYA__USER_FLAGS_MUST_CONFIRM) && !($flags & ILYA__USER_FLAGS_EMAIL_CONFIRMED) && ilya_opt('confirm_user_emails')) {
			$ilya_content = ilya_content_prepare();
			$ilya_content['title'] = ilya_lang_html('users/confirm_title');
			$ilya_content['error'] = strtr(ilya_lang_html('users/confirm_required'), array(
				'^1' => '<a href="' . ilya_path_html('confirm') . '">',
				'^2' => '</a>',
			));
		}

		// we no longer block access here for unapproved users; this is handled by the Permissions settings
	}

	// Combine various Javascript elements in $ilya_content into single array for theme layer

	$script = array('<script>');

	if (isset($ilya_content['script_var'])) {
		foreach ($ilya_content['script_var'] as $var => $value) {
			$script[] = 'var ' . $var . ' = ' . ilya_js($value) . ';';
		}
	}

	if (isset($ilya_content['script_lines'])) {
		foreach ($ilya_content['script_lines'] as $scriptlines) {
			$script[] = '';
			$script = array_merge($script, $scriptlines);
		}
	}

	$script[] = '</script>';

	if (isset($ilya_content['script_rel'])) {
		$uniquerel = array_unique($ilya_content['script_rel']); // remove any duplicates
		foreach ($uniquerel as $script_rel) {
			$script[] = '<script src="' . ilya_html(ilya_path_to_root() . $script_rel) . '"></script>';
		}
	}

	if (isset($ilya_content['script_src'])) {
		$uniquesrc = array_unique($ilya_content['script_src']); // remove any duplicates
		foreach ($uniquesrc as $script_src) {
			$script[] = '<script src="' . ilya_html($script_src) . '"></script>';
		}
	}

	// JS onloads must come after jQuery is loaded

	if (isset($ilya_content['focusid'])) {
		$ilya_content['script_onloads'][] = array(
			'$(' . ilya_js('#' . $ilya_content['focusid']) . ').focus();',
		);
	}

	if (isset($ilya_content['script_onloads'])) {
		$script[] = '<script>';
		$script[] = '$(window).on(\'load\', function() {';

		foreach ($ilya_content['script_onloads'] as $scriptonload) {
			foreach ((array)$scriptonload as $scriptline) {
				$script[] = "\t" . $scriptline;
			}
		}

		$script[] = '});';
		$script[] = '</script>';
	}

	if (!isset($ilya_content['script'])) {
		$ilya_content['script'] = array();
	}

	$ilya_content['script'] = array_merge($ilya_content['script'], $script);

	// Load the appropriate theme class and output the page

	$tmpl = substr($ilya_template, 0, 7) == 'custom-' ? 'custom' : $ilya_template;
	$themeclass = ilya_load_theme_class(ilya_get_site_theme(), $tmpl, $ilya_content, ilya_request());
	$themeclass->initialize();

	header('Content-type: ' . $ilya_content['content_type']);

	$themeclass->doctype();
	$themeclass->html();
	$themeclass->finish();
}


/**
 * Update any statistics required by the fields in $ilya_content, and return true if something was done
 * @param $ilya_content
 * @return bool
 */
function ilya_do_content_stats($ilya_content)
{
	if (!isset($ilya_content['inc_views_postid'])) {
		return false;
	}

	require_once ILYA__INCLUDE_DIR . 'db/hotness.php';

	$viewsIncremented = ilya_db_increment_views($ilya_content['inc_views_postid']);

	if ($viewsIncremented && ilya_opt('recalc_hotness_q_view')) {
		ilya_db_hotness_update($ilya_content['inc_views_postid']);
	}

	return true;
}


// Other functions which might be called from anywhere

/**
 * Return an array of the default ILYA requests and which /ilya-include/pages/*.php file implements them
 * If the key of an element ends in /, it should be used for any request with that key as its prefix
 */
function ilya_page_routing()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return array(
		'account' => 'pages/account.php',
		'activity/' => 'pages/activity.php',
		'admin/' => 'pages/admin/admin-default.php',
		'admin/approve' => 'pages/admin/admin-approve.php',
		'admin/categories' => 'pages/admin/admin-categories.php',
		'admin/flagged' => 'pages/admin/admin-flagged.php',
		'admin/hidden' => 'pages/admin/admin-hidden.php',
		'admin/layoutwidgets' => 'pages/admin/admin-widgets.php',
		'admin/moderate' => 'pages/admin/admin-moderate.php',
		'admin/pages' => 'pages/admin/admin-pages.php',
		'admin/plugins' => 'pages/admin/admin-plugins.php',
		'admin/points' => 'pages/admin/admin-points.php',
		'admin/recalc' => 'pages/admin/admin-recalc.php',
		'admin/stats' => 'pages/admin/admin-stats.php',
		'admin/userfields' => 'pages/admin/admin-userfields.php',
		'admin/usertitles' => 'pages/admin/admin-usertitles.php',
		'answers/' => 'pages/answers.php',
		'ask' => 'pages/ask.php',
		'categories/' => 'pages/categories.php',
		'comments/' => 'pages/comments.php',
		'confirm' => 'pages/confirm.php',
		'favorites' => 'pages/favorites.php',
		'favorites/questions' => 'pages/favorites-list.php',
		'favorites/users' => 'pages/favorites-list.php',
		'favorites/tags' => 'pages/favorites-list.php',
		'feedback' => 'pages/feedback.php',
		'forgot' => 'pages/forgot.php',
		'hot/' => 'pages/hot.php',
		'ip/' => 'pages/ip.php',
		'login' => 'pages/login.php',
		'logout' => 'pages/logout.php',
		'messages/' => 'pages/messages.php',
		'message/' => 'pages/message.php',
		'questions/' => 'pages/questions.php',
		'register' => 'pages/register.php',
		'reset' => 'pages/reset.php',
		'search' => 'pages/search.php',
		'tag/' => 'pages/tag.php',
		'tags' => 'pages/tags.php',
		'unanswered/' => 'pages/unanswered.php',
		'unsubscribe' => 'pages/unsubscribe.php',
		'updates' => 'pages/updates.php',
		'user/' => 'pages/user.php',
		'users' => 'pages/users.php',
		'users/blocked' => 'pages/users-blocked.php',
		'users/new' => 'pages/users-newest.php',
		'users/special' => 'pages/users-special.php',
	);
}


/**
 * Sets the template which should be passed to the theme class, telling it which type of page it's displaying
 * @param $template
 */
function ilya_set_template($template)
{
	global $ilya_template;
	$ilya_template = $template;
}


/**
 * Start preparing theme content in global $ilya_content variable, with or without $voting support,
 * in the context of the categories in $categoryids (if not null)
 * @param bool $voting
 * @param array $categoryids
 * @return array
 */
function ilya_content_prepare($voting = false, $categoryids = array())
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_template, $ilya_page_error_html;

	if (ILYA__DEBUG_PERFORMANCE) {
		global $ilya_usage;
		$ilya_usage->mark('control');
	}

	$request = ilya_request();
	$requestlower = ilya_request();
	$navpages = ilya_db_get_pending_result('navpages');
	$widgets = ilya_db_get_pending_result('widgets');

	if (!is_array($categoryids)) {
		// accept old-style parameter
		$categoryids = array($categoryids);
	}

	$lastcategoryid = count($categoryids) > 0 ? end($categoryids) : null;
	$charset = 'utf-8';
	$language = ilya_opt('site_language');
	$language = empty($language) ? 'en' : ilya_html($language);

	$ilya_content = array(
		'content_type' => 'text/html; charset=' . $charset,
		'charset' => $charset,

		'language' => $language,

		'direction' => ilya_opt('site_text_direction'),

		'options' => array(
			'minify_html' => ilya_opt('minify_html'),
		),

		'site_title' => ilya_html(ilya_opt('site_title')),

		'html_tags' => 'lang="' . $language . '"',

		'head_lines' => array(),

		'navigation' => array(
			'user' => array(),

			'main' => array(),

			'footer' => array(
				'feedback' => array(
					'url' => ilya_path_html('feedback'),
					'label' => ilya_lang_html('main/nav_feedback'),
				),
			),

		),

		'sidebar' => ilya_opt('show_custom_sidebar') ? ilya_opt('custom_sidebar') : null,
		'sidepanel' => ilya_opt('show_custom_sidepanel') ? ilya_opt('custom_sidepanel') : null,
		'widgets' => array(),
	);

	// add meta description if we're on the home page
	if ($request === '' || $request === array_search('', ilya_get_request_map())) {
		$ilya_content['description'] = ilya_html(ilya_opt('home_description'));
	}

	if (ilya_opt('show_custom_in_head'))
		$ilya_content['head_lines'][] = ilya_opt('custom_in_head');

	if (ilya_opt('show_custom_header'))
		$ilya_content['body_header'] = ilya_opt('custom_header');

	if (ilya_opt('show_custom_footer'))
		$ilya_content['body_footer'] = ilya_opt('custom_footer');

	if (isset($categoryids))
		$ilya_content['categoryids'] = $categoryids;

	foreach ($navpages as $page) {
		if ($page['nav'] == 'B')
			ilya_navigation_add_page($ilya_content['navigation']['main'], $page);
	}

	if (ilya_opt('nav_home') && ilya_opt('show_custom_home')) {
		$ilya_content['navigation']['main']['$'] = array(
			'url' => ilya_path_html(''),
			'label' => ilya_lang_html('main/nav_home'),
		);
	}

	if (ilya_opt('nav_activity')) {
		$ilya_content['navigation']['main']['activity'] = array(
			'url' => ilya_path_html('activity'),
			'label' => ilya_lang_html('main/nav_activity'),
		);
	}

	$hascustomhome = ilya_has_custom_home();

	if (ilya_opt($hascustomhome ? 'nav_ilya_not_home' : 'nav_ilya_is_home')) {
		$ilya_content['navigation']['main'][$hascustomhome ? 'ilya' : '$'] = array(
			'url' => ilya_path_html($hascustomhome ? 'ilya' : ''),
			'label' => ilya_lang_html('main/nav_ilya'),
		);
	}

	if (ilya_opt('nav_questions')) {
		$ilya_content['navigation']['main']['questions'] = array(
			'url' => ilya_path_html('questions'),
			'label' => ilya_lang_html('main/nav_qs'),
		);
	}

	if (ilya_opt('nav_hot')) {
		$ilya_content['navigation']['main']['hot'] = array(
			'url' => ilya_path_html('hot'),
			'label' => ilya_lang_html('main/nav_hot'),
		);
	}

	if (ilya_opt('nav_unanswered')) {
		$ilya_content['navigation']['main']['unanswered'] = array(
			'url' => ilya_path_html('unanswered'),
			'label' => ilya_lang_html('main/nav_unanswered'),
		);
	}

	if (ilya_using_tags() && ilya_opt('nav_tags')) {
		$ilya_content['navigation']['main']['tag'] = array(
			'url' => ilya_path_html('tags'),
			'label' => ilya_lang_html('main/nav_tags'),
			'selected_on' => array('tags$', 'tag/'),
		);
	}

	if (ilya_using_categories() && ilya_opt('nav_categories')) {
		$ilya_content['navigation']['main']['categories'] = array(
			'url' => ilya_path_html('categories'),
			'label' => ilya_lang_html('main/nav_categories'),
			'selected_on' => array('categories$', 'categories/'),
		);
	}

	if (ilya_opt('nav_users')) {
		$ilya_content['navigation']['main']['user'] = array(
			'url' => ilya_path_html('users'),
			'label' => ilya_lang_html('main/nav_users'),
			'selected_on' => array('users$', 'users/', 'user/'),
		);
	}

	// Only the 'level' permission error prevents the menu option being shown - others reported on /ilya-include/pages/ask.php

	if (ilya_opt('nav_ask') && ilya_user_maximum_permit_error('permit_post_q') != 'level') {
		$ilya_content['navigation']['main']['ask'] = array(
			'url' => ilya_path_html('ask', (ilya_using_categories() && strlen($lastcategoryid)) ? array('cat' => $lastcategoryid) : null),
			'label' => ilya_lang_html('main/nav_ask'),
		);
	}


	if (ilya_get_logged_in_level() >= ILYA__USER_LEVEL_ADMIN || !ilya_user_maximum_permit_error('permit_moderate') ||
		!ilya_user_maximum_permit_error('permit_hide_show') || !ilya_user_maximum_permit_error('permit_delete_hidden')
	) {
		$ilya_content['navigation']['main']['admin'] = array(
			'url' => ilya_path_html('admin'),
			'label' => ilya_lang_html('main/nav_admin'),
			'selected_on' => array('admin/'),
		);
	}

	$ilya_content['search'] = array(
		'form_tags' => 'method="get" action="' . ilya_path_html('search') . '"',
		'form_extra' => ilya_path_form_html('search'),
		'title' => ilya_lang_html('main/search_title'),
		'field_tags' => 'name="q"',
		'button_label' => ilya_lang_html('main/search_button'),
	);

	if (!ilya_opt('feedback_enabled'))
		unset($ilya_content['navigation']['footer']['feedback']);

	foreach ($navpages as $page) {
		if ($page['nav'] == 'M' || $page['nav'] == 'O' || $page['nav'] == 'F') {
			$loc = ($page['nav'] == 'F') ? 'footer' : 'main';
			ilya_navigation_add_page($ilya_content['navigation'][$loc], $page);
		}
	}

	$regioncodes = array(
		'F' => 'full',
		'M' => 'main',
		'S' => 'side',
	);

	$placecodes = array(
		'T' => 'top',
		'H' => 'high',
		'L' => 'low',
		'B' => 'bottom',
	);

	foreach ($widgets as $widget) {
		$tagstring = ',' . $widget['tags'] . ',';
		$showOnTmpl = strpos($tagstring, ",$ilya_template,") !== false || strpos($tagstring, ',all,') !== false;
		// special case for user pages
		$showOnUser = strpos($tagstring, ',user,') !== false && preg_match('/^user(-.+)?$/', $ilya_template) === 1;

		if ($showOnTmpl || $showOnUser) {
			// widget has been selected for display on this template
			$region = @$regioncodes[substr($widget['place'], 0, 1)];
			$place = @$placecodes[substr($widget['place'], 1, 2)];

			if (isset($region) && isset($place)) {
				// region/place codes recognized
				$module = ilya_load_module('widget', $widget['title']);
				$allowTmpl = (substr($ilya_template, 0, 7) == 'custom-') ? 'custom' : $ilya_template;

				if (isset($module) &&
					method_exists($module, 'allow_template') && $module->allow_template($allowTmpl) &&
					method_exists($module, 'allow_region') && $module->allow_region($region) &&
					method_exists($module, 'output_widget')
				) {
					// if module loaded and happy to be displayed here, tell theme about it
					$ilya_content['widgets'][$region][$place][] = $module;
				}
			}
		}
	}

	$logoshow = ilya_opt('logo_show');
	$logourl = ilya_opt('logo_url');
	$logowidth = ilya_opt('logo_width');
	$logoheight = ilya_opt('logo_height');

	if ($logoshow) {
		$ilya_content['logo'] = '<a href="' . ilya_path_html('') . '" class="ilya-logo-link" title="' . ilya_html(ilya_opt('site_title')) . '">' .
			'<img src="' . ilya_html(is_numeric(strpos($logourl, '://')) ? $logourl : ilya_path_to_root() . $logourl) . '"' .
			($logowidth ? (' width="' . $logowidth . '"') : '') . ($logoheight ? (' height="' . $logoheight . '"') : '') .
			' alt="' . ilya_html(ilya_opt('site_title')) . '"/></a>';
	} else {
		$ilya_content['logo'] = '<a href="' . ilya_path_html('') . '" class="ilya-logo-link">' . ilya_html(ilya_opt('site_title')) . '</a>';
	}

	$topath = ilya_get('to'); // lets user switch between login and register without losing destination page

	$userlinks = ilya_get_login_links(ilya_path_to_root(), isset($topath) ? $topath : ilya_path($request, $_GET, ''));

	$ilya_content['navigation']['user'] = array();

	if (ilya_is_logged_in()) {
		$ilya_content['loggedin'] = ilya_lang_html_sub_split('main/logged_in_x', ILYA__FINAL_EXTERNAL_USERS
			? ilya_get_logged_in_user_html(ilya_get_logged_in_user_cache(), ilya_path_to_root(), false)
			: ilya_get_one_user_html(ilya_get_logged_in_handle(), false)
		);

		$ilya_content['navigation']['user']['updates'] = array(
			'url' => ilya_path_html('updates'),
			'label' => ilya_lang_html('main/nav_updates'),
		);

		if (!empty($userlinks['logout'])) {
			$ilya_content['navigation']['user']['logout'] = array(
				'url' => ilya_html(@$userlinks['logout']),
				'label' => ilya_lang_html('main/nav_logout'),
			);
		}

		if (!ILYA__FINAL_EXTERNAL_USERS) {
			$source = ilya_get_logged_in_source();

			if (strlen($source)) {
				$loginmodules = ilya_load_modules_with('login', 'match_source');

				foreach ($loginmodules as $module) {
					if ($module->match_source($source) && method_exists($module, 'logout_html')) {
						ob_start();
						$module->logout_html(ilya_path('logout', array(), ilya_opt('site_url')));
						$ilya_content['navigation']['user']['logout'] = array('label' => ob_get_clean());
					}
				}
			}
		}

		$notices = ilya_db_get_pending_result('notices');
		foreach ($notices as $notice)
			$ilya_content['notices'][] = ilya_notice_form($notice['noticeid'], ilya_viewer_html($notice['content'], $notice['format']), $notice);

	} else {
		require_once ILYA__INCLUDE_DIR . 'util/string.php';

		if (!ILYA__FINAL_EXTERNAL_USERS) {
			$loginmodules = ilya_load_modules_with('login', 'login_html');

			foreach ($loginmodules as $tryname => $module) {
				ob_start();
				$module->login_html(isset($topath) ? (ilya_opt('site_url') . $topath) : ilya_path($request, $_GET, ilya_opt('site_url')), 'menu');
				$label = ob_get_clean();

				if (strlen($label))
					$ilya_content['navigation']['user'][implode('-', ilya_string_to_words($tryname))] = array('label' => $label);
			}
		}

		if (!empty($userlinks['login'])) {
			$ilya_content['navigation']['user']['login'] = array(
				'url' => ilya_html(@$userlinks['login']),
				'label' => ilya_lang_html('main/nav_login'),
			);
		}

		if (!empty($userlinks['register'])) {
			$ilya_content['navigation']['user']['register'] = array(
				'url' => ilya_html(@$userlinks['register']),
				'label' => ilya_lang_html('main/nav_register'),
			);
		}
	}

	if (ILYA__FINAL_EXTERNAL_USERS || !ilya_is_logged_in()) {
		if (ilya_opt('show_notice_visitor') && (!isset($topath)) && (!isset($_COOKIE['ilya_noticed'])))
			$ilya_content['notices'][] = ilya_notice_form('visitor', ilya_opt('notice_visitor'));

	} else {
		setcookie('ilya_noticed', 1, time() + 86400 * 3650, '/', ILYA__COOKIE_DOMAIN, (bool)ini_get('session.cookie_secure'), true); // don't show first-time notice if a user has logged in

		if (ilya_opt('show_notice_welcome') && (ilya_get_logged_in_flags() & ILYA__USER_FLAGS_WELCOME_NOTICE)) {
			if ($requestlower != 'confirm' && $requestlower != 'account') // let people finish registering in peace
				$ilya_content['notices'][] = ilya_notice_form('welcome', ilya_opt('notice_welcome'));
		}
	}

	$ilya_content['script_rel'] = array('ilya-content/jquery-3.3.1.min.js');
	$ilya_content['script_rel'][] = 'ilya-content/ilya-global.js?' . ILYA__VERSION;

	if ($voting)
		$ilya_content['error'] = @$ilya_page_error_html;

	$ilya_content['script_var'] = array(
		'ilya_root' => ilya_path_to_root(),
		'ilya_request' => $request,
	);

	return $ilya_content;
}


/**
 * Get the start parameter which should be used, as constrained by the setting in ilya-config.php
 * @return int
 */
function ilya_get_start()
{
	return min(max(0, (int)ilya_get('start')), ILYA__MAX_LIMIT_START);
}


/**
 * Get the state parameter which should be used, as set earlier in ilya_load_state()
 * @return string
 */
function ilya_get_state()
{
	global $ilya_state;
	return $ilya_state;
}


/**
 * Generate a canonical URL for the current request. Preserves certain GET parameters.
 * @return string The full canonical URL.
 */
function ilya_get_canonical()
{
	$params = array();

	// variable assignment intentional here
	if (($start = ilya_get_start()) > 0) {
		$params['start'] = $start;
	}
	if ($sort = ilya_get('sort')) {
		$params['sort'] = $sort;
	}
	if ($by = ilya_get('by')) {
		$params['by'] = $by;
	}

	return ilya_path_html(ilya_request(), $params, ilya_opt('site_url'));
}
