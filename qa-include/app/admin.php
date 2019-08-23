<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Functions used in the admin center pages


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


/**
 * Return true if user is logged in with admin privileges. If not, return false
 * and set up $ilya_content with the appropriate title and error message
 * @param $ilya_content
 * @return bool
 */
function ilya_admin_check_privileges(&$ilya_content)
{
	if (!ilya_is_logged_in()) {
		require_once ILYA__INCLUDE_DIR . 'app/format.php';

		$ilya_content = ilya_content_prepare();

		$ilya_content['title'] = ilya_lang_html('admin/admin_title');
		$ilya_content['error'] = ilya_insert_login_links(ilya_lang_html('admin/not_logged_in'), ilya_request());

		return false;

	} elseif (ilya_get_logged_in_level() < ILYA__USER_LEVEL_ADMIN) {
		$ilya_content = ilya_content_prepare();

		$ilya_content['title'] = ilya_lang_html('admin/admin_title');
		$ilya_content['error'] = ilya_lang_html('admin/no_privileges');

		return false;
	}

	return true;
}


/**
 *	Return a sorted array of available languages, [short code] => [long name]
 */
function ilya_admin_language_options()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	/**
	 * @deprecated The hardcoded language ids will be removed in favor of language metadata files.
	 * See ilya-lang/en-GB directory for a clear example of how to use them.
	 */
	$codetolanguage = array( // 2-letter language codes as per ISO 639-1
		'ar' => 'Arabic - العربية',
		'az' => 'Azerbaijani - Azərbaycanca',
		'bg' => 'Bulgarian - Български',
		'bn' => 'Bengali - বাংলা',
		'ca' => 'Catalan - Català',
		'cs' => 'Czech - Čeština',
		'cy' => 'Welsh - Cymraeg',
		'da' => 'Danish - Dansk',
		'de' => 'German - Deutsch',
		'el' => 'Greek - Ελληνικά',
		'en-GB' => 'English (UK)',
		'es' => 'Spanish - Español',
		'et' => 'Estonian - Eesti',
		'fa' => 'Persian - فارسی',
		'fi' => 'Finnish - Suomi',
		'fr' => 'French - Français',
		'he' => 'Hebrew - עברית',
		'hr' => 'Croatian - Hrvatski',
		'hu' => 'Hungarian - Magyar',
		'id' => 'Indonesian - Bahasa Indonesia',
		'is' => 'Icelandic - Íslenska',
		'it' => 'Italian - Italiano',
		'ja' => 'Japanese - 日本語',
		'ka' => 'Georgian - ქართული ენა',
		'kh' => 'Khmer - ភាសាខ្មែរ',
		'ko' => 'Korean - 한국어',
		'ku-CKB' => 'Kurdish Central - کورد',
		'lt' => 'Lithuanian - Lietuvių',
		'lv' => 'Latvian - Latviešu',
		'nl' => 'Dutch - Nederlands',
		'no' => 'Norwegian - Norsk',
		'pl' => 'Polish - Polski',
		'pt' => 'Portuguese - Português',
		'ro' => 'Romanian - Română',
		'ru' => 'Russian - Русский',
		'sk' => 'Slovak - Slovenčina',
		'sl' => 'Slovenian - Slovenščina',
		'sq' => 'Albanian - Shqip',
		'sr' => 'Serbian - Српски',
		'sv' => 'Swedish - Svenska',
		'th' => 'Thai - ไทย',
		'tr' => 'Turkish - Türkçe',
		'ug' => 'Uyghur - ئۇيغۇرچە',
		'uk' => 'Ukrainian - Українська',
		'uz' => 'Uzbek - ўзбек',
		'vi' => 'Vietnamese - Tiếng Việt',
		'zh-TW' => 'Chinese Traditional - 繁體中文',
		'zh' => 'Chinese Simplified - 简体中文',
	);

	$options = array('' => 'English (US)');

	// find all language folders
	$metadataUtil = new Q2A_Util_Metadata();
	foreach (glob(ILYA__LANG_DIR . '*', GLOB_ONLYDIR) as $directory) {
		$code = basename($directory);
		$metadata = $metadataUtil->fetchFromAddonPath($directory);
		if (isset($metadata['name'])) {
			$options[$code] = $metadata['name'];
		} elseif (isset($codetolanguage[$code])) {
			// otherwise use an entry from above
			$options[$code] = $codetolanguage[$code];
		}
	}

	asort($options, SORT_STRING);
	return $options;
}


/**
 * Return a sorted array of available themes, [theme name] => [theme name]
 */
function ilya_admin_theme_options()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$metadataUtil = new Q2A_Util_Metadata();
	foreach (glob(ILYA__THEME_DIR . '*', GLOB_ONLYDIR) as $directory) {
		$theme = basename($directory);
		$metadata = $metadataUtil->fetchFromAddonPath($directory);
		if (empty($metadata)) {
			// limit theme parsing to first 8kB
			$contents = @file_get_contents($directory . '/ilya-styles.css', false, null, 0, 8192);
			$metadata = ilya_addon_metadata($contents, 'Theme');
		}
		$options[$theme] = isset($metadata['name']) ? $metadata['name'] : $theme;
	}

	asort($options, SORT_STRING);
	return $options;
}


/**
 * Return an array of widget placement options, with keys matching the database value
 */
function ilya_admin_place_options()
{
	return array(
		'FT' => ilya_lang_html('options/place_full_top'),
		'FH' => ilya_lang_html('options/place_full_below_nav'),
		'FL' => ilya_lang_html('options/place_full_below_content'),
		'FB' => ilya_lang_html('options/place_full_below_footer'),
		'MT' => ilya_lang_html('options/place_main_top'),
		'MH' => ilya_lang_html('options/place_main_below_title'),
		'ML' => ilya_lang_html('options/place_main_below_lists'),
		'MB' => ilya_lang_html('options/place_main_bottom'),
		'ST' => ilya_lang_html('options/place_side_top'),
		'SH' => ilya_lang_html('options/place_side_below_sidebar'),
		'SL' => ilya_lang_html('options/place_side_low'),
		'SB' => ilya_lang_html('options/place_side_last'),
	);
}


/**
 * Return an array of page size options up to $maximum, [page size] => [page size]
 * @param $maximum
 * @return array
 */
function ilya_admin_page_size_options($maximum)
{
	$rawoptions = array(5, 10, 15, 20, 25, 30, 40, 50, 60, 80, 100, 120, 150, 200, 250, 300, 400, 500, 600, 800, 1000);

	$options = array();
	foreach ($rawoptions as $rawoption) {
		if ($rawoption > $maximum)
			break;

		$options[$rawoption] = $rawoption;
	}

	return $options;
}


/**
 * Return an array of options representing matching precision, [value] => [label]
 */
function ilya_admin_match_options()
{
	return array(
		5 => ilya_lang_html('options/match_5'),
		4 => ilya_lang_html('options/match_4'),
		3 => ilya_lang_html('options/match_3'),
		2 => ilya_lang_html('options/match_2'),
		1 => ilya_lang_html('options/match_1'),
	);
}


/**
 * Return an array of options representing permission restrictions, [value] => [label]
 * ranging from $widest to $narrowest. Set $doconfirms to whether email confirmations are on
 * @param $widest
 * @param $narrowest
 * @param bool $doconfirms
 * @param bool $dopoints
 * @return array
 */
function ilya_admin_permit_options($widest, $narrowest, $doconfirms = true, $dopoints = true)
{
	require_once ILYA__INCLUDE_DIR . 'app/options.php';

	$options = array(
		ILYA__PERMIT_ALL => ilya_lang_html('options/permit_all'),
		ILYA__PERMIT_USERS => ilya_lang_html('options/permit_users'),
		ILYA__PERMIT_CONFIRMED => ilya_lang_html('options/permit_confirmed'),
		ILYA__PERMIT_POINTS => ilya_lang_html('options/permit_points'),
		ILYA__PERMIT_POINTS_CONFIRMED => ilya_lang_html('options/permit_points_confirmed'),
		ILYA__PERMIT_APPROVED => ilya_lang_html('options/permit_approved'),
		ILYA__PERMIT_APPROVED_POINTS => ilya_lang_html('options/permit_approved_points'),
		ILYA__PERMIT_EXPERTS => ilya_lang_html('options/permit_experts'),
		ILYA__PERMIT_EDITORS => ilya_lang_html('options/permit_editors'),
		ILYA__PERMIT_MODERATORS => ilya_lang_html('options/permit_moderators'),
		ILYA__PERMIT_ADMINS => ilya_lang_html('options/permit_admins'),
		ILYA__PERMIT_SUPERS => ilya_lang_html('options/permit_supers'),
	);

	foreach ($options as $key => $label) {
		if ($key < $narrowest || $key > $widest)
			unset($options[$key]);
	}

	if (!$doconfirms) {
		unset($options[ILYA__PERMIT_CONFIRMED]);
		unset($options[ILYA__PERMIT_POINTS_CONFIRMED]);
	}

	if (!$dopoints) {
		unset($options[ILYA__PERMIT_POINTS]);
		unset($options[ILYA__PERMIT_POINTS_CONFIRMED]);
		unset($options[ILYA__PERMIT_APPROVED_POINTS]);
	}

	if (ILYA__FINAL_EXTERNAL_USERS || !ilya_opt('moderate_users')) {
		unset($options[ILYA__PERMIT_APPROVED]);
		unset($options[ILYA__PERMIT_APPROVED_POINTS]);
	}

	return $options;
}


/**
 * Return the sub navigation structure common to admin pages
 */
function ilya_admin_sub_navigation()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$navigation = array();
	$level = ilya_get_logged_in_level();

	if ($level >= ILYA__USER_LEVEL_ADMIN) {
		$navigation['admin/general'] = array(
			'label' => ilya_lang_html('admin/general_title'),
			'url' => ilya_path_html('admin/general'),
		);

		$navigation['admin/emails'] = array(
			'label' => ilya_lang_html('admin/emails_title'),
			'url' => ilya_path_html('admin/emails'),
		);

		$navigation['admin/users'] = array(
			'label' => ilya_lang_html('admin/users_title'),
			'url' => ilya_path_html('admin/users'),
			'selected_on' => array('admin/users$', 'admin/userfields$', 'admin/usertitles$'),
		);

		$navigation['admin/layout'] = array(
			'label' => ilya_lang_html('admin/layout_title'),
			'url' => ilya_path_html('admin/layout'),
		);

		$navigation['admin/posting'] = array(
			'label' => ilya_lang_html('admin/posting_title'),
			'url' => ilya_path_html('admin/posting'),
		);

		$navigation['admin/viewing'] = array(
			'label' => ilya_lang_html('admin/viewing_title'),
			'url' => ilya_path_html('admin/viewing'),
		);

		$navigation['admin/lists'] = array(
			'label' => ilya_lang_html('admin/lists_title'),
			'url' => ilya_path_html('admin/lists'),
		);

		if (ilya_using_categories())
			$navigation['admin/categories'] = array(
				'label' => ilya_lang_html('admin/categories_title'),
				'url' => ilya_path_html('admin/categories'),
			);

		$navigation['admin/permissions'] = array(
			'label' => ilya_lang_html('admin/permissions_title'),
			'url' => ilya_path_html('admin/permissions'),
		);

		$navigation['admin/pages'] = array(
			'label' => ilya_lang_html('admin/pages_title'),
			'url' => ilya_path_html('admin/pages'),
		);

		$navigation['admin/feeds'] = array(
			'label' => ilya_lang_html('admin/feeds_title'),
			'url' => ilya_path_html('admin/feeds'),
		);

		$navigation['admin/points'] = array(
			'label' => ilya_lang_html('admin/points_title'),
			'url' => ilya_path_html('admin/points'),
		);

		$navigation['admin/spam'] = array(
			'label' => ilya_lang_html('admin/spam_title'),
			'url' => ilya_path_html('admin/spam'),
		);

		$navigation['admin/caching'] = array(
			'label' => ilya_lang_html('admin/caching_title'),
			'url' => ilya_path_html('admin/caching'),
		);

		$navigation['admin/stats'] = array(
			'label' => ilya_lang_html('admin/stats_title'),
			'url' => ilya_path_html('admin/stats'),
		);

		if (!ILYA__FINAL_EXTERNAL_USERS)
			$navigation['admin/mailing'] = array(
				'label' => ilya_lang_html('admin/mailing_title'),
				'url' => ilya_path_html('admin/mailing'),
			);

		$navigation['admin/plugins'] = array(
			'label' => ilya_lang_html('admin/plugins_title'),
			'url' => ilya_path_html('admin/plugins'),
		);
	}

	if (!ilya_user_maximum_permit_error('permit_moderate')) {
		$count = ilya_user_permit_error('permit_moderate') ? null : ilya_opt('cache_queuedcount'); // if only in some categories don't show cached count

		$navigation['admin/moderate'] = array(
			'label' => ilya_lang_html('admin/moderate_title') . ($count ? (' (' . $count . ')') : ''),
			'url' => ilya_path_html('admin/moderate'),
		);
	}

	if (ilya_opt('flagging_of_posts') && !ilya_user_maximum_permit_error('permit_hide_show')) {
		$count = ilya_user_permit_error('permit_hide_show') ? null : ilya_opt('cache_flaggedcount'); // if only in some categories don't show cached count

		$navigation['admin/flagged'] = array(
			'label' => ilya_lang_html('admin/flagged_title') . ($count ? (' (' . $count . ')') : ''),
			'url' => ilya_path_html('admin/flagged'),
		);
	}

	if (!ilya_user_maximum_permit_error('permit_hide_show') || !ilya_user_maximum_permit_error('permit_delete_hidden')) {
		$navigation['admin/hidden'] = array(
			'label' => ilya_lang_html('admin/hidden_title'),
			'url' => ilya_path_html('admin/hidden'),
		);
	}

	if (!ILYA__FINAL_EXTERNAL_USERS && ilya_opt('moderate_users') && $level >= ILYA__USER_LEVEL_MODERATOR) {
		$count = ilya_opt('cache_uapprovecount');

		$navigation['admin/approve'] = array(
			'label' => ilya_lang_html('admin/approve_users_title') . ($count ? (' (' . $count . ')') : ''),
			'url' => ilya_path_html('admin/approve'),
		);
	}

	return $navigation;
}


/**
 * Return the error that needs to displayed on all admin pages, or null if none
 */
function ilya_admin_page_error()
{
	if (file_exists(ILYA__INCLUDE_DIR . 'db/install.php')) // file can be removed for extra security
		include_once ILYA__INCLUDE_DIR . 'db/install.php';

	if (defined('ILYA__DB_VERSION_CURRENT') && ilya_opt('db_version') < ILYA__DB_VERSION_CURRENT && ilya_get_logged_in_level() >= ILYA__USER_LEVEL_ADMIN) {
		return strtr(
			ilya_lang_html('admin/upgrade_db'),

			array(
				'^1' => '<a href="' . ilya_path_html('install') . '">',
				'^2' => '</a>',
			)
		);

	} elseif (defined('ILYA__BLOBS_DIRECTORY') && !is_writable(ILYA__BLOBS_DIRECTORY)) {
		return ilya_lang_html_sub('admin/blobs_directory_error', ilya_html(ILYA__BLOBS_DIRECTORY));
	}

	return null;
}


/**
 * Return an HTML fragment to display for a URL test which has passed
 */
function ilya_admin_url_test_html()
{
	return '; font-size:9px; color:#060; font-weight:bold; font-family:arial,sans-serif; border-color:#060;">OK<';
}


/**
 * Returns whether a URL path beginning with $requestpart is reserved by the engine or a plugin page module
 * @param $requestpart
 * @return bool
 */
function ilya_admin_is_slug_reserved($requestpart)
{
	$requestpart = trim(strtolower($requestpart));
	$routing = ilya_page_routing();

	if (isset($routing[$requestpart]) || isset($routing[$requestpart . '/']) || is_numeric($requestpart))
		return true;

	$pathmap = ilya_get_request_map();

	foreach ($pathmap as $mappedrequest) {
		if (trim(strtolower($mappedrequest)) == $requestpart)
			return true;
	}

	switch ($requestpart) {
		case '':
		case 'ilya':
		case 'feed':
		case 'install':
		case 'url':
		case 'image':
		case 'ajax':
			return true;
	}

	$pagemodules = ilya_load_modules_with('page', 'match_request');
	foreach ($pagemodules as $pagemodule) {
		if ($pagemodule->match_request($requestpart))
			return true;
	}

	return false;
}


/**
 * Returns true if admin (hidden/flagged/approve/moderate) page $action performed on $entityid is permitted by the
 * logged in user and was processed successfully
 * @param $entityid
 * @param $action
 * @return bool
 */
function ilya_admin_single_click($entityid, $action)
{
	$userid = ilya_get_logged_in_userid();

	if (!ILYA__FINAL_EXTERNAL_USERS && ($action == 'userapprove' || $action == 'userblock')) { // approve/block moderated users
		require_once ILYA__INCLUDE_DIR . 'db/selects.php';

		$useraccount = ilya_db_select_with_pending(ilya_db_user_account_selectspec($entityid, true));

		if (isset($useraccount) && ilya_get_logged_in_level() >= ILYA__USER_LEVEL_MODERATOR) {
			switch ($action) {
				case 'userapprove':
					if ($useraccount['level'] <= ILYA__USER_LEVEL_APPROVED) { // don't demote higher level users
						require_once ILYA__INCLUDE_DIR . 'app/users-edit.php';
						ilya_set_user_level($useraccount['userid'], $useraccount['handle'], ILYA__USER_LEVEL_APPROVED, $useraccount['level']);
						return true;
					}
					break;

				case 'userblock':
					require_once ILYA__INCLUDE_DIR . 'app/users-edit.php';
					ilya_set_user_blocked($useraccount['userid'], $useraccount['handle'], true);
					return true;
					break;
			}
		}

	} else { // something to do with a post
		require_once ILYA__INCLUDE_DIR . 'app/posts.php';

		$post = ilya_post_get_full($entityid);

		if (isset($post)) {
			$queued = (substr($post['type'], 1) == '_QUEUED');

			switch ($action) {
				case 'approve':
					if ($queued && !ilya_user_post_permit_error('permit_moderate', $post)) {
						ilya_post_set_status($entityid, ILYA__POST_STATUS_NORMAL, $userid);
						return true;
					}
					break;

				case 'reject':
					if ($queued && !ilya_user_post_permit_error('permit_moderate', $post)) {
						ilya_post_set_status($entityid, ILYA__POST_STATUS_HIDDEN, $userid);
						return true;
					}
					break;

				case 'hide':
					if (!$queued && !ilya_user_post_permit_error('permit_hide_show', $post)) {
						ilya_post_set_status($entityid, ILYA__POST_STATUS_HIDDEN, $userid);
						return true;
					}
					break;

				case 'reshow':
					if ($post['hidden'] && !ilya_user_post_permit_error('permit_hide_show', $post)) {
						ilya_post_set_status($entityid, ILYA__POST_STATUS_NORMAL, $userid);
						return true;
					}
					break;

				case 'delete':
					if ($post['hidden'] && !ilya_user_post_permit_error('permit_delete_hidden', $post)) {
						ilya_post_delete($entityid);
						return true;
					}
					break;

				case 'clearflags':
					require_once ILYA__INCLUDE_DIR . 'app/votes.php';

					if (!ilya_user_post_permit_error('permit_hide_show', $post)) {
						ilya_flags_clear_all($post, $userid, ilya_get_logged_in_handle(), null);
						return true;
					}
					break;
			}
		}
	}

	return false;
}


/**
 * Checks for a POSTed click on an admin (hidden/flagged/approve/moderate) page, and refresh the page if processed successfully (non Ajax)
 */
function ilya_admin_check_clicks()
{
	if (!ilya_is_http_post()) {
		return null;
	}

	foreach ($_POST as $field => $value) {
		if (strpos($field, 'admin_') !== 0) {
			continue;
		}

		@list($dummy, $entityid, $action) = explode('_', $field);

		if (strlen($entityid) && strlen($action)) {
			if (!ilya_check_form_security_code('admin/click', ilya_post_text('code')))
				return ilya_lang_html('misc/form_security_again');
			elseif (ilya_admin_single_click($entityid, $action))
				ilya_redirect(ilya_request());
		}
	}

	return null;
}


/**
 * Retrieve metadata information from the $contents of a ilya-theme.php or ilya-plugin.php file, mapping via $fields.
 *
 * @deprecated Deprecated from 1.7; use `ilya_addon_metadata($contents, $type)` instead.
 * @param $contents
 * @param $fields
 * @return array
 */
function ilya_admin_addon_metadata($contents, $fields)
{
	$metadata = array();

	foreach ($fields as $key => $field) {
		if (preg_match('/' . str_replace(' ', '[ \t]*', preg_quote($field, '/')) . ':[ \t]*([^\n\f]*)[\n\f]/i', $contents, $matches))
			$metadata[$key] = trim($matches[1]);
	}

	return $metadata;
}


/**
 * Return the hash code for the plugin in $directory (without trailing slash), used for in-page navigation on admin/plugins page
 * @param $directory
 * @return mixed
 */
function ilya_admin_plugin_directory_hash($directory)
{
	$pluginManager = new Q2A_Plugin_PluginManager();
	$hashes = $pluginManager->getHashesForPlugins(array($directory));

	return reset($hashes);
}


/**
 * Return the URL (relative to the current page) to navigate to the options panel for the plugin in $directory (without trailing slash)
 * @param $directory
 * @return mixed|string
 */
function ilya_admin_plugin_options_path($directory)
{
	$hash = ilya_admin_plugin_directory_hash($directory);
	return ilya_path_html('admin/plugins', array('show' => $hash), null, null, $hash);
}


/**
 * Return the URL (relative to the current page) to navigate to the options panel for plugin module $name of $type
 * @param $type
 * @param $name
 * @return mixed|string
 */
function ilya_admin_module_options_path($type, $name)
{
	$info = ilya_get_module_info($type, $name);
	$dir = basename($info['directory']);

	return ilya_admin_plugin_options_path($dir);
}
