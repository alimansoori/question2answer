<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Sets up ILYA environment, plus many globally useful functions


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


define('ILYA__VERSION', '1.8.3'); // also used as suffix for .js and .css requests
define('ILYA__BUILD_DATE', '2019-01-12');


/**
 * Autoloads some ILYA classes so it's possible to use them without adding a require_once first. From version 1.7 onwards.
 * These loosely follow PHP-FIG's PSR-0 standard where faux namespaces are separated by underscores. This is being done
 * slowly and carefully to maintain backwards compatibility, and does not apply to plugins, themes, nor most of the core
 * for that matter.
 *
 * Classes are stored in the ilya-include/ILYA folder, and then in subfolders depending on their categorization.
 * Class names should be of the form ILYA_<Namespace>_<Class>, e.g. ILYA_Util_Debug. There may be multiple "namespaces".
 * Classes are mapped to PHP files with the underscores converted to directory separators. The ILYA_Util_Debug class is in
 * the file ilya-include/ILYA/Util/Debug.php. A class named ILYA_Db_User_Messages would be in a file ilya-include/ILYA/Db/User/Messages.php.
 *
 * @param $class
 */
function ilya_autoload($class)
{
	if (strpos($class, 'ILYA_') === 0)
		require ILYA__INCLUDE_DIR . strtr($class, '_', '/') . '.php';
}
spl_autoload_register('ilya_autoload');


// Execution section of this file - remainder contains function definitions

ilya_initialize_php();
ilya_initialize_constants_1();

if (defined('ILYA__WORDPRESS_LOAD_FILE')) {
	// if relevant, load WordPress integration in global scope
	require_once ILYA__WORDPRESS_LOAD_FILE;
} elseif (defined('ILYA__JOOMLA_LOAD_FILE')) {
	// if relevant, load Joomla JConfig class into global scope
	require_once ILYA__JOOMLA_LOAD_FILE;
}


ilya_initialize_constants_2();
ilya_initialize_modularity();
ilya_register_core_modules();

ilya_initialize_predb_plugins();
require_once ILYA__INCLUDE_DIR . 'ilya-db.php';
ilya_db_allow_connect();

// $ilya_autoconnect defaults to true so that optional plugins will load for external code. ILYA core
// code sets $ilya_autoconnect to false so that we can use custom fail handlers.
if (!isset($ilya_autoconnect) || $ilya_autoconnect !== false) {
	ilya_db_connect('ilya_page_db_fail_handler');
	ilya_initialize_postdb_plugins();
}


// Version comparison functions

/**
 * Converts the $version string (e.g. 1.6.2.2) to a floating point that can be used for greater/lesser comparisons
 * (PHP's version_compare() function is not quite suitable for our needs)
 * @deprecated 1.8.2 no longer used
 * @param $version
 * @return float
 */
function ilya_version_to_float($version)
{
	$value = 0.0;

	if (preg_match('/[0-9\.]+/', $version, $matches)) {
		$parts = explode('.', $matches[0]);
		$units = 1.0;

		foreach ($parts as $part) {
			$value += min($part, 999) * $units;
			$units /= 1000;
		}
	}

	return $value;
}


/**
 * Returns true if the current ILYA version is lower than $version
 * @param $version
 * @return bool
 */
function ilya_ilya_version_below($version)
{
	return version_compare(ILYA__VERSION, $version) < 0;
}


/**
 * Returns true if the current PHP version is lower than $version
 * @param $version
 * @return bool
 */
function ilya_php_version_below($version)
{
	return version_compare(phpversion(), $version) < 0;
}


// Initialization functions called above

/**
 * Set up and verify the PHP environment for ILYA, including unregistering globals if necessary
 */
function ilya_initialize_php()
{
	if (ilya_php_version_below('5.1.6'))
		ilya_fatal_error('ILYA requires PHP 5.1.6 or later');

	error_reporting(E_ALL); // be ultra-strict about error checking

	@ini_set('magic_quotes_runtime', 0);

	@setlocale(LC_CTYPE, 'C'); // prevent strtolower() et al affecting non-ASCII characters (appears important for IIS)

	if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get'))
		@date_default_timezone_set(@date_default_timezone_get()); // prevent PHP notices where default timezone not set

	if (ini_get('register_globals')) {
		$checkarrays = array('_ENV', '_GET', '_POST', '_COOKIE', '_SERVER', '_FILES', '_REQUEST', '_SESSION'); // unregister globals if they're registered
		$keyprotect = array_flip(array_merge($checkarrays, array('GLOBALS')));

		foreach ($checkarrays as $checkarray) {
			if (isset(${$checkarray}) && is_array(${$checkarray})) {
				foreach (${$checkarray} as $checkkey => $checkvalue) {
					if (isset($keyprotect[$checkkey])) {
						ilya_fatal_error('My superglobals are not for overriding');
					} else {
						unset($GLOBALS[$checkkey]);
					}
				}
			}
		}
	}
}


/**
 * First stage of setting up ILYA constants, before (if necessary) loading WordPress or Joomla! integration
 */
function ilya_initialize_constants_1()
{
	global $ilya_request_map;

	define('ILYA__CATEGORY_DEPTH', 4); // you can't change this number!

	if (!defined('ILYA__BASE_DIR'))
		define('ILYA__BASE_DIR', dirname(dirname(__FILE__)) . '/'); // try out best if not set in index.php or ilya-index.php - won't work with symbolic links

	define('ILYA__EXTERNAL_DIR', ILYA__BASE_DIR . 'ilya-external/');
	define('ILYA__INCLUDE_DIR', ILYA__BASE_DIR . 'ilya-include/');
	define('ILYA__LANG_DIR', ILYA__BASE_DIR . 'ilya-lang/');
	define('ILYA__THEME_DIR', ILYA__BASE_DIR . 'ilya-theme/');
	define('ILYA__PLUGIN_DIR', ILYA__BASE_DIR . 'ilya-plugin/');

	if (!file_exists(ILYA__BASE_DIR . 'ilya-config.php'))
		ilya_fatal_error('The config file could not be found. Please read the instructions in ilya-config-example.php.');

	require_once ILYA__BASE_DIR . 'ilya-config.php';

	$ilya_request_map = isset($ILYA__CONST_PATH_MAP) && is_array($ILYA__CONST_PATH_MAP) ? $ILYA__CONST_PATH_MAP : array();

	if (defined('ILYA__WORDPRESS_INTEGRATE_PATH') && strlen(ILYA__WORDPRESS_INTEGRATE_PATH)) {
		define('ILYA__FINAL_WORDPRESS_INTEGRATE_PATH', ILYA__WORDPRESS_INTEGRATE_PATH . ((substr(ILYA__WORDPRESS_INTEGRATE_PATH, -1) == '/') ? '' : '/'));
		define('ILYA__WORDPRESS_LOAD_FILE', ILYA__FINAL_WORDPRESS_INTEGRATE_PATH . 'wp-load.php');

		if (!is_readable(ILYA__WORDPRESS_LOAD_FILE)) {
			ilya_fatal_error('Could not find wp-load.php file for WordPress integration - please check ILYA__WORDPRESS_INTEGRATE_PATH in ilya-config.php');
		}
	} elseif (defined('ILYA__JOOMLA_INTEGRATE_PATH') && strlen(ILYA__JOOMLA_INTEGRATE_PATH)) {
		define('ILYA__FINAL_JOOMLA_INTEGRATE_PATH', ILYA__JOOMLA_INTEGRATE_PATH . ((substr(ILYA__JOOMLA_INTEGRATE_PATH, -1) == '/') ? '' : '/'));
		define('ILYA__JOOMLA_LOAD_FILE', ILYA__FINAL_JOOMLA_INTEGRATE_PATH . 'configuration.php');

		if (!is_readable(ILYA__JOOMLA_LOAD_FILE)) {
			ilya_fatal_error('Could not find configuration.php file for Joomla integration - please check ILYA__JOOMLA_INTEGRATE_PATH in ilya-config.php');
		}
	}

	// Polyfills

	// password_hash compatibility for 5.3-5.4
	define('ILYA__PASSWORD_HASH', !ilya_php_version_below('5.3.7'));
	if (ILYA__PASSWORD_HASH) {
		require_once ILYA__INCLUDE_DIR . 'vendor/password_compat.php';
	}

	// http://php.net/manual/en/function.hash-equals.php#115635
	if (!function_exists('hash_equals')) {
		function hash_equals($str1, $str2)
		{
			if (strlen($str1) != strlen($str2)) {
				return false;
			} else {
				$res = $str1 ^ $str2;
				$ret = 0;
				for ($i = strlen($res) - 1; $i >= 0; $i--)
					$ret |= ord($res[$i]);
				return !$ret;
			}
		}
	}
}


/**
 * Second stage of setting up ILYA constants, after (if necessary) loading WordPress or Joomla! integration
 */
function ilya_initialize_constants_2()
{
	// Default values if not set in ilya-config.php

	$defaults = array(
		'ILYA__COOKIE_DOMAIN' => '',
		'ILYA__HTML_COMPRESSION' => true,
		'ILYA__MAX_LIMIT_START' => 19999,
		'ILYA__IGNORED_WORDS_FREQ' => 10000,
		'ILYA__ALLOW_UNINDEXED_QUERIES' => false,
		'ILYA__OPTIMIZE_LOCAL_DB' => true,
		'ILYA__OPTIMIZE_DISTANT_DB' => false,
		'ILYA__PERSISTENT_CONN_DB' => false,
		'ILYA__DEBUG_PERFORMANCE' => false,
	);

	foreach ($defaults as $key => $def) {
		if (!defined($key)) {
			define($key, $def);
		}
	}

	// Start performance monitoring

	if (ILYA__DEBUG_PERFORMANCE) {
		global $ilya_usage;
		$ilya_usage = new ILYA_Util_Usage;
		// ensure errors are displayed
		@ini_set('display_errors', 'On');
	}

	// More for WordPress integration

	if (defined('ILYA__FINAL_WORDPRESS_INTEGRATE_PATH')) {
		define('ILYA__FINAL_MYSQL_HOSTNAME', DB_HOST);
		define('ILYA__FINAL_MYSQL_USERNAME', DB_USER);
		define('ILYA__FINAL_MYSQL_PASSWORD', DB_PASSWORD);
		define('ILYA__FINAL_MYSQL_DATABASE', DB_NAME);
		define('ILYA__FINAL_EXTERNAL_USERS', true);

		// Undo WordPress's addition of magic quotes to various things (leave $_COOKIE as is since WP code might need that)

		function ilya_undo_wordpress_quoting($param, $isget)
		{
			if (is_array($param)) { //
				foreach ($param as $key => $value)
					$param[$key] = ilya_undo_wordpress_quoting($value, $isget);

			} else {
				$param = stripslashes($param);
				if ($isget)
					$param = strtr($param, array('\\\'' => '\'', '\"' => '"')); // also compensate for WordPress's .htaccess file
			}

			return $param;
		}

		$_GET = ilya_undo_wordpress_quoting($_GET, true);
		$_POST = ilya_undo_wordpress_quoting($_POST, false);
		$_SERVER['PHP_SELF'] = stripslashes($_SERVER['PHP_SELF']);

	} elseif (defined('ILYA__FINAL_JOOMLA_INTEGRATE_PATH')) {
		// More for Joomla integration
		$jconfig = new JConfig();
		define('ILYA__FINAL_MYSQL_HOSTNAME', $jconfig->host);
		define('ILYA__FINAL_MYSQL_USERNAME', $jconfig->user);
		define('ILYA__FINAL_MYSQL_PASSWORD', $jconfig->password);
		define('ILYA__FINAL_MYSQL_DATABASE', $jconfig->db);
		define('ILYA__FINAL_EXTERNAL_USERS', true);
	} else {
		define('ILYA__FINAL_MYSQL_HOSTNAME', ILYA__MYSQL_HOSTNAME);
		define('ILYA__FINAL_MYSQL_USERNAME', ILYA__MYSQL_USERNAME);
		define('ILYA__FINAL_MYSQL_PASSWORD', ILYA__MYSQL_PASSWORD);
		define('ILYA__FINAL_MYSQL_DATABASE', ILYA__MYSQL_DATABASE);
		define('ILYA__FINAL_EXTERNAL_USERS', ILYA__EXTERNAL_USERS);
	}

	if (defined('ILYA__MYSQL_PORT')) {
		define('ILYA__FINAL_MYSQL_PORT', ILYA__MYSQL_PORT);
	}

	// Possible URL schemes for ILYA and the string used for url scheme testing

	define('ILYA__URL_FORMAT_INDEX', 0);  // http://...../index.php/123/why-is-the-sky-blue
	define('ILYA__URL_FORMAT_NEAT', 1);   // http://...../123/why-is-the-sky-blue [requires .htaccess]
	define('ILYA__URL_FORMAT_PARAM', 3);  // http://...../?ilya=123/why-is-the-sky-blue
	define('ILYA__URL_FORMAT_PARAMS', 4); // http://...../?ilya=123&ilya_1=why-is-the-sky-blue
	define('ILYA__URL_FORMAT_SAFEST', 5); // http://...../index.php?ilya=123&ilya_1=why-is-the-sky-blue

	define('ILYA__URL_TEST_STRING', '$&-_~#%\\@^*()][`\';=:|".{},!<>?# π§½Жש'); // tests escaping, spaces, quote slashing and unicode - but not + and /
}


/**
 * Gets everything ready to start using modules, layers and overrides
 */
function ilya_initialize_modularity()
{
	global $ilya_modules, $ilya_layers, $ilya_override_files, $ilya_override_files_temp, $ilya_overrides, $ilya_direct;

	$ilya_modules = array();
	$ilya_layers = array();
	$ilya_override_files = array();
	$ilya_override_files_temp = array();
	$ilya_overrides = array();
	$ilya_direct = array();
}


/**
 * Set up output buffering. Use gzip compression if option set and it's not an admin page (since some of these contain lengthy processes).
 * @param $request
 * @return bool whether buffering was used
 */
function ilya_initialize_buffering($request = '')
{
	if (headers_sent()) {
		return false;
	}

	$useGzip = ILYA__HTML_COMPRESSION && substr($request, 0, 6) !== 'admin/' && extension_loaded('zlib');
	ob_start($useGzip ? 'ob_gzhandler' : null);
	return true;
}


/**
 * Register all modules that come as part of the ILYA core (as opposed to plugins)
 */
function ilya_register_core_modules()
{
	ilya_register_module('filter', 'plugins/ilya-filter-basic.php', 'ilya_filter_basic', '');
	ilya_register_module('editor', 'plugins/ilya-editor-basic.php', 'ilya_editor_basic', '');
	ilya_register_module('viewer', 'plugins/ilya-viewer-basic.php', 'ilya_viewer_basic', '');
	ilya_register_module('event', 'plugins/ilya-event-limits.php', 'ilya_event_limits', 'ILYA Event Limits');
	ilya_register_module('event', 'plugins/ilya-event-notify.php', 'ilya_event_notify', 'ILYA Event Notify');
	ilya_register_module('event', 'plugins/ilya-event-updates.php', 'ilya_event_updates', 'ILYA Event Updates');
	ilya_register_module('search', 'plugins/ilya-search-basic.php', 'ilya_search_basic', '');
	ilya_register_module('widget', 'plugins/ilya-widget-activity-count.php', 'ilya_activity_count', 'Activity Count');
	ilya_register_module('widget', 'plugins/ilya-widget-ask-box.php', 'ilya_ask_box', 'Ask Box');
	ilya_register_module('widget', 'plugins/ilya-widget-related-qs.php', 'ilya_related_qs', 'Related Questions');
	ilya_register_module('widget', 'plugins/ilya-widget-category-list.php', 'ilya_category_list', 'Categories');
}


/**
 * Load plugins before database is available. Generally this includes database overrides and
 * process plugins that run early in the request lifecycle.
 */
function ilya_initialize_predb_plugins()
{
	global $ilya_pluginManager;
	$ilya_pluginManager = new ILYA_Plugin_PluginManager();
	$ilya_pluginManager->readAllPluginMetadatas();

	$ilya_pluginManager->loadPluginsBeforeDbInit();
	ilya_load_override_files();
}


/**
 * Load plugins after database is available. Plugins loaded here are able to be disabled in admin.
 */
function ilya_initialize_postdb_plugins()
{
	global $ilya_pluginManager;

	require_once ILYA__INCLUDE_DIR . 'app/options.php';
	ilya_preload_options();

	$ilya_pluginManager->loadPluginsAfterDbInit();
	ilya_load_override_files();

	ilya_report_process_stage('plugins_loaded');
}


/**
 * Standard database failure handler function which bring up the install/repair/upgrade page
 * @param $type
 * @param int $errno
 * @param string $error
 * @param string $query
 * @return mixed
 */
function ilya_page_db_fail_handler($type, $errno = null, $error = null, $query = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$pass_failure_type = $type;
	$pass_failure_errno = $errno;
	$pass_failure_error = $error;
	$pass_failure_query = $query;

	require_once ILYA__INCLUDE_DIR . 'ilya-install.php';

	ilya_exit('error');
}


/**
 * Retrieve metadata information from the $contents of a ilya-theme.php or ilya-plugin.php file, specified by $type ('Plugin' or 'Theme').
 * If $versiononly is true, only min version metadata is parsed.
 * Name, Description, Min ILYA & Min PHP are not currently used by themes.
 *
 * @deprecated Deprecated from 1.7; ILYA_Util_Metadata class and metadata.json files should be used instead
 * @param $contents
 * @param $type
 * @param bool $versiononly
 * @return array
 */
function ilya_addon_metadata($contents, $type, $versiononly = false)
{
	$fields = array(
		'min_q2a' => 'Minimum Question2Answer Version',
		'min_php' => 'Minimum PHP Version',
	);
	if (!$versiononly) {
		$fields = array_merge($fields, $fields = array(
			'name' => 'Name',
			'uri' => 'URI',
			'description' => 'Description',
			'version' => 'Version',
			'date' => 'Date',
			'author' => 'Author',
			'author_uri' => 'Author URI',
			'license' => 'License',
			'update_uri' => 'Update Check URI',
		));
	}

	$metadata = array();
	foreach ($fields as $key => $field) {
		// prepend 'Theme'/'Plugin' and search for key data
		$fieldregex = str_replace(' ', '[ \t]*', preg_quote("$type $field", '/'));
		if (preg_match('/' . $fieldregex . ':[ \t]*([^\n\f]*)[\n\f]/i', $contents, $matches))
			$metadata[$key] = trim($matches[1]);
	}

	return $metadata;
}


/**
 * Apply all the function overrides in override files that have been registered by plugins
 */
function ilya_load_override_files()
{
	global $ilya_override_files, $ilya_override_files_temp, $ilya_overrides;

	$functionindex = array();

	foreach ($ilya_override_files_temp as $override) {
		$ilya_override_files[] = $override;
		$filename = $override['directory'] . $override['include'];
		$functionsphp = file_get_contents($filename);

		preg_match_all('/\Wfunction\s+(ilya_[a-z_]+)\s*\(/im', $functionsphp, $rawmatches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);

		$reversematches = array_reverse($rawmatches[1], true); // reverse so offsets remain correct as we step through
		$postreplace = array();
		// include file name in defined function names to make debugging easier if there is an error
		$suffix = '_in_' . preg_replace('/[^A-Za-z0-9_]+/', '_', basename($override['include']));

		foreach ($reversematches as $rawmatch) {
			$function = strtolower($rawmatch[0]);
			$position = $rawmatch[1];

			if (isset($ilya_overrides[$function]))
				$postreplace[$function . '_base'] = $ilya_overrides[$function];

			$newname = $function . '_override_' . (@++$functionindex[$function]) . $suffix;
			$functionsphp = substr_replace($functionsphp, $newname, $position, strlen($function));
			$ilya_overrides[$function] = $newname;
		}

		foreach ($postreplace as $oldname => $newname) {
			if (preg_match_all('/\W(' . preg_quote($oldname) . ')\s*\(/im', $functionsphp, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE)) {
				$searchmatches = array_reverse($matches[1]);
				foreach ($searchmatches as $searchmatch) {
					$functionsphp = substr_replace($functionsphp, $newname, $searchmatch[1], strlen($searchmatch[0]));
				}
			}
		}

		// echo '<pre style="text-align:left;">'.htmlspecialchars($functionsphp).'</pre>'; // to debug munged code

		ilya_eval_from_file($functionsphp, $filename);
	}

	$ilya_override_files_temp = array();
}


// Functions for registering different varieties of ILYA modularity

/**
 * Register a module of $type named $name, whose class named $class is defined in file $include (or null if no include necessary)
 * If this module comes from a plugin, pass in the local plugin $directory and the $urltoroot relative url for that directory
 * @param $type
 * @param $include
 * @param $class
 * @param $name
 * @param string $directory
 * @param string $urltoroot
 */
function ilya_register_module($type, $include, $class, $name, $directory = ILYA__INCLUDE_DIR, $urltoroot = null)
{
	global $ilya_modules;

	$previous = @$ilya_modules[$type][$name];

	if (isset($previous)) {
		ilya_fatal_error('A ' . $type . ' module named ' . $name . ' already exists. Please check there are no duplicate plugins. ' .
			"\n\nModule 1: " . $previous['directory'] . $previous['include'] . "\nModule 2: " . $directory . $include);
	}

	$ilya_modules[$type][$name] = array(
		'directory' => $directory,
		'urltoroot' => $urltoroot,
		'include' => $include,
		'class' => $class,
	);
}


/**
 * Register a layer named $name, defined in file $include. If this layer comes from a plugin (as all currently do),
 * pass in the local plugin $directory and the $urltoroot relative url for that directory
 * @param $include
 * @param $name
 * @param string $directory
 * @param string $urltoroot
 */
function ilya_register_layer($include, $name, $directory = ILYA__INCLUDE_DIR, $urltoroot = null)
{
	global $ilya_layers;

	$previous = @$ilya_layers[$name];

	if (isset($previous)) {
		ilya_fatal_error('A layer named ' . $name . ' already exists. Please check there are no duplicate plugins. ' .
			"\n\nLayer 1: " . $previous['directory'] . $previous['include'] . "\nLayer 2: " . $directory . $include);
	}

	$ilya_layers[$name] = array(
		'directory' => $directory,
		'urltoroot' => $urltoroot,
		'include' => $include,
	);
}


/**
 * Register a file $include containing override functions. If this file comes from a plugin (as all currently do),
 * pass in the local plugin $directory and the $urltoroot relative url for that directory
 * @param $include
 * @param string $directory
 * @param string $urltoroot
 */
function ilya_register_overrides($include, $directory = ILYA__INCLUDE_DIR, $urltoroot = null)
{
	global $ilya_override_files_temp;

	$ilya_override_files_temp[] = array(
		'directory' => $directory,
		'urltoroot' => $urltoroot,
		'include' => $include,
	);
}


/**
 * Register a set of language phrases, which should be accessed by the prefix $name/ in the ilya_lang_*() functions.
 * Pass in the $pattern representing the PHP files that define these phrases, where * in the pattern is replaced with
 * the language code (e.g. 'fr') and/or 'default'. These files should be formatted like ILYA's ilya-lang-*.php files.
 * @param $pattern
 * @param $name
 */
function ilya_register_phrases($pattern, $name)
{
	global $ilya_lang_file_pattern;

	if (file_exists(ILYA__INCLUDE_DIR . 'lang/ilya-lang-' . $name . '.php')) {
		ilya_fatal_error('The name "' . $name . '" for phrases is reserved and cannot be used by plugins.' . "\n\nPhrases: " . $pattern);
	}

	if (isset($ilya_lang_file_pattern[$name])) {
		ilya_fatal_error('A set of phrases named ' . $name . ' already exists. Please check there are no duplicate plugins. ' .
			"\n\nPhrases 1: " . $ilya_lang_file_pattern[$name] . "\nPhrases 2: " . $pattern);
	}

	$ilya_lang_file_pattern[$name] = $pattern;
}


// Function for registering varieties of ILYA modularity, which are (only) called from ilya-plugin.php files

/**
 * Register a plugin module of $type named $name, whose class named $class is defined in file $include (or null if no include necessary)
 * This function relies on some global variable values and can only be called from a plugin's ilya-plugin.php file
 * @param $type
 * @param $include
 * @param $class
 * @param $name
 */
function ilya_register_plugin_module($type, $include, $class, $name)
{
	global $ilya_plugin_directory, $ilya_plugin_urltoroot;

	if (empty($ilya_plugin_directory) || empty($ilya_plugin_urltoroot)) {
		ilya_fatal_error('ilya_register_plugin_module() can only be called from a plugin ilya-plugin.php file');
	}

	ilya_register_module($type, $include, $class, $name, $ilya_plugin_directory, $ilya_plugin_urltoroot);
}


/**
 * Register a plugin layer named $name, defined in file $include. Can only be called from a plugin's ilya-plugin.php file
 * @param $include
 * @param $name
 */
function ilya_register_plugin_layer($include, $name)
{
	global $ilya_plugin_directory, $ilya_plugin_urltoroot;

	if (empty($ilya_plugin_directory) || empty($ilya_plugin_urltoroot)) {
		ilya_fatal_error('ilya_register_plugin_layer() can only be called from a plugin ilya-plugin.php file');
	}

	ilya_register_layer($include, $name, $ilya_plugin_directory, $ilya_plugin_urltoroot);
}


/**
 * Register a plugin file $include containing override functions. Can only be called from a plugin's ilya-plugin.php file
 * @param $include
 */
function ilya_register_plugin_overrides($include)
{
	global $ilya_plugin_directory, $ilya_plugin_urltoroot;

	if (empty($ilya_plugin_directory) || empty($ilya_plugin_urltoroot)) {
		ilya_fatal_error('ilya_register_plugin_overrides() can only be called from a plugin ilya-plugin.php file');
	}

	ilya_register_overrides($include, $ilya_plugin_directory, $ilya_plugin_urltoroot);
}


/**
 * Register a file name $pattern within a plugin directory containing language phrases accessed by the prefix $name
 * @param $pattern
 * @param $name
 */
function ilya_register_plugin_phrases($pattern, $name)
{
	global $ilya_plugin_directory, $ilya_plugin_urltoroot;

	if (empty($ilya_plugin_directory) || empty($ilya_plugin_urltoroot)) {
		ilya_fatal_error('ilya_register_plugin_phrases() can only be called from a plugin ilya-plugin.php file');
	}

	ilya_register_phrases($ilya_plugin_directory . $pattern, $name);
}


// Low-level functions used throughout ILYA

/**
 * Calls eval() on the PHP code in $eval which came from the file $filename. It supplements PHP's regular error reporting by
 * displaying/logging (as appropriate) the original source filename, if an error occurred when evaluating the code.
 * @param $eval
 * @param $filename
 */
function ilya_eval_from_file($eval, $filename)
{
	// could also use ini_set('error_append_string') but apparently it doesn't work for errors logged on disk

	global $php_errormsg;

	$oldtrackerrors = @ini_set('track_errors', 1);
	$php_errormsg = null;

	eval('?' . '>' . $eval);

	if (strlen($php_errormsg)) {
		switch (strtolower(@ini_get('display_errors'))) {
			case 'on':
			case '1':
			case 'yes':
			case 'true':
			case 'stdout':
			case 'stderr':
				echo ' of ' . ilya_html($filename) . "\n";
				break;
		}

		@error_log('PHP Question2Answer more info: ' . $php_errormsg . " in eval()'d code from " . ilya_html($filename));
	}

	@ini_set('track_errors', $oldtrackerrors);
}


/**
 * Call $function with the arguments in the $args array (doesn't work with call-by-reference functions)
 * @param $function
 * @param $args
 * @return mixed
 */
function ilya_call($function, $args)
{
	// call_user_func_array(...) is very slow, so we break out most common cases first
	switch (count($args)) {
		case 0:
			return $function();
		case 1:
			return $function($args[0]);
		case 2:
			return $function($args[0], $args[1]);
		case 3:
			return $function($args[0], $args[1], $args[2]);
		case 4:
			return $function($args[0], $args[1], $args[2], $args[3]);
		case 5:
			return $function($args[0], $args[1], $args[2], $args[3], $args[4]);
	}

	return call_user_func_array($function, $args);
}


/**
 * Determines whether a function is to be overridden by a plugin. But if the function is being called with
 * the _base suffix, any override will be bypassed due to $ilya_direct.
 *
 * @param string $function The function to override
 * @return string|null The name of the overriding function (of the form `ilya_functionname_override_1_in_filename`)
 */
function ilya_to_override($function)
{
	global $ilya_overrides, $ilya_direct;

	// handle most common case first
	if (!isset($ilya_overrides[$function])) {
		return null;
	}

	if (strpos($function, '_override_') !== false) {
		ilya_fatal_error('Override functions should not be calling ilya_to_override()!');
	}

	if (@$ilya_direct[$function]) {
		unset($ilya_direct[$function]); // bypass the override just this once
		return null;
	}

	return $ilya_overrides[$function];
}


/**
 * Call the function which immediately overrides $function with the arguments in the $args array
 * @param $function
 * @param $args
 * @return mixed
 */
function ilya_call_override($function, $args)
{
	global $ilya_overrides;

	if (strpos($function, '_override_') !== false) {
		ilya_fatal_error('Override functions should not be calling ilya_call_override()!');
	}

	if (!function_exists($function . '_base')) {
		// define the base function the first time that it's needed
		eval('function ' . $function . '_base() { global $ilya_direct; $ilya_direct[\'' . $function . '\']=true; $args=func_get_args(); return ilya_call(\'' . $function . '\', $args); }');
	}

	return ilya_call($ilya_overrides[$function], $args);
}


/**
 * Exit PHP immediately after reporting a shutdown with $reason to any installed process modules
 * @param string $reason
 */
function ilya_exit($reason = null)
{
	ilya_report_process_stage('shutdown', $reason);

	$code = $reason === 'error' ? 1 : 0;
	exit($code);
}


/**
 * Display $message in the browser, write it to server error log, and then stop abruptly
 * @param $message
 * @return mixed
 */
function ilya_fatal_error($message)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	echo 'Question2Answer fatal error:<p style="color: red">' . ilya_html($message, true) . '</p>';
	@error_log('PHP Question2Answer fatal error: ' . $message);
	echo '<p>Stack trace:<p>';

	$backtrace = array_reverse(array_slice(debug_backtrace(), 1));
	foreach ($backtrace as $trace) {
		$color = strpos(@$trace['file'], '/ilya-plugin/') !== false ? 'red' : '#999';
		echo sprintf(
			'<code style="color: %s">%s() in %s:%s</code><br>',
			$color, ilya_html(@$trace['function']), basename(@$trace['file']), @$trace['line']
		);
	}

	ilya_exit('error');
}


// Functions for listing, loading and getting info on modules

/**
 * Return an array with all registered modules' information
 */
function ilya_list_modules_info()
{
	global $ilya_modules;
	return $ilya_modules;
}

/**
 * Return an array of all the module types for which at least one module has been registered
 */
function ilya_list_module_types()
{
	return array_keys(ilya_list_modules_info());
}

/**
 * Return a list of names of registered modules of $type
 * @param $type
 * @return array
 */
function ilya_list_modules($type)
{
	$modules = ilya_list_modules_info();
	return is_array(@$modules[$type]) ? array_keys($modules[$type]) : array();
}

/**
 * Return an array containing information about the module of $type named $name
 * @param $type
 * @param $name
 * @return array
 */
function ilya_get_module_info($type, $name)
{
	$modules = ilya_list_modules_info();
	return @$modules[$type][$name];
}

/**
 * Return an instantiated class for module of $type named $name, whose functions can be called, or null if it doesn't exist
 * @param $type
 * @param $name
 * @return mixed|null
 */
function ilya_load_module($type, $name)
{
	global $ilya_modules;

	$module = @$ilya_modules[$type][$name];

	if (is_array($module)) {
		if (isset($module['object']))
			return $module['object'];

		if (strlen(@$module['include']))
			require_once $module['directory'] . $module['include'];

		if (strlen(@$module['class'])) {
			$object = new $module['class'];

			if (method_exists($object, 'load_module'))
				$object->load_module($module['directory'], ilya_path_to_root() . $module['urltoroot'], $type, $name);

			$ilya_modules[$type][$name]['object'] = $object;
			return $object;
		}
	}

	return null;
}

/**
 * Return an array of instantiated clases for modules which have defined $method
 * (all modules are loaded but not included in the returned array)
 * @param $method
 * @return array
 */
function ilya_load_all_modules_with($method)
{
	$modules = array();

	$regmodules = ilya_list_modules_info();

	foreach ($regmodules as $moduletype => $modulesinfo) {
		foreach ($modulesinfo as $modulename => $moduleinfo) {
			$module = ilya_load_module($moduletype, $modulename);

			if (method_exists($module, $method))
				$modules[$modulename] = $module;
		}
	}

	return $modules;
}

/**
 * Return an array of instantiated clases for modules of $type which have defined $method
 * (other modules of that type are also loaded but not included in the returned array)
 * @param $type
 * @param $method
 * @return array
 */
function ilya_load_modules_with($type, $method)
{
	$modules = array();

	$trynames = ilya_list_modules($type);

	foreach ($trynames as $tryname) {
		$module = ilya_load_module($type, $tryname);

		if (method_exists($module, $method))
			$modules[$tryname] = $module;
	}

	return $modules;
}


// HTML and Javascript escaping and sanitization

/**
 * Return HTML representation of $string, work well with blocks of text if $multiline is true
 * @param $string
 * @param bool $multiline
 * @return mixed|string
 */
function ilya_html($string, $multiline = false)
{
	$html = htmlspecialchars((string)$string);

	if ($multiline) {
		$html = preg_replace('/\r\n?/', "\n", $html);
		$html = preg_replace('/(?<=\s) /', '&nbsp;', $html);
		$html = str_replace("\t", '&nbsp; &nbsp; ', $html);
		$html = nl2br($html);
	}

	return $html;
}


/**
 * Return $html after ensuring it is safe, i.e. removing Javascripts and the like - uses htmLawed library
 * Links open in a new window if $linksnewwindow is true. Set $storage to true if sanitization is for
 * storing in the database, rather than immediate display to user - some think this should be less strict.
 * @param $html
 * @param bool $linksnewwindow
 * @param bool $storage
 * @return mixed|string
 */
function ilya_sanitize_html($html, $linksnewwindow = false, $storage = false)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once 'vendor/htmLawed.php';

	global $ilya_sanitize_html_newwindow;

	$ilya_sanitize_html_newwindow = $linksnewwindow;

	$safe = htmLawed($html, array(
		'safe' => 1,
		'elements' => '*+embed+object-form',
		'schemes' => 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https; style: !; classid:clsid',
		'keep_bad' => 0,
		'anti_link_spam' => array('/.*/', ''),
		'hook_tag' => 'ilya_sanitize_html_hook_tag',
	));

	return $safe;
}


/**
 * htmLawed hook function used to process tags in ilya_sanitize_html(...)
 * @param $element
 * @param array $attributes
 * @return string
 */
function ilya_sanitize_html_hook_tag($element, $attributes = null)
{
	global $ilya_sanitize_html_newwindow;

	if (!isset($attributes)) // it's a closing tag
		return '</' . $element . '>';

	if ($element == 'param' && trim(strtolower(@$attributes['name'])) == 'allowscriptaccess')
		$attributes['name'] = 'allowscriptaccess_denied';

	if ($element == 'embed')
		unset($attributes['allowscriptaccess']);

	if ($element == 'a' && isset($attributes['href']) && $ilya_sanitize_html_newwindow)
		$attributes['target'] = '_blank';

	$html = '<' . $element;
	foreach ($attributes as $key => $value)
		$html .= ' ' . $key . '="' . $value . '"';

	return $html . '>';
}


/**
 * Return XML representation of $string, which is similar to HTML but ASCII control characters are also disallowed
 * @param $string
 * @return string
 */
function ilya_xml($string)
{
	return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$string));
}


/**
 * Return JavaScript representation of $value, putting in quotes if non-numeric or if $forcequotes is true. In the
 * case of boolean values they are returned as the appropriate true or false string
 * @param $value
 * @param bool $forcequotes
 * @return string
 */
function ilya_js($value, $forcequotes = false)
{
	$boolean = is_bool($value);
	if ($boolean)
		$value = $value ? 'true' : 'false';
	if ((is_numeric($value) || $boolean) && !$forcequotes)
		return $value;
	else
		return "'" . strtr($value, array(
				"'" => "\\'",
				'/' => '\\/',
				'\\' => '\\\\',
				"\n" => "\\n",
				"\r" => "\\n",
			)) . "'";
}


// Finding out more about the current request

/**
 * Inform ILYA that the current request is $request (slash-separated, independent of the url scheme chosen),
 * that the relative path to the ILYA root apperas to be $relativeroot, and the url scheme appears to be $usedformat
 * @param $request
 * @param $relativeroot
 * @param $usedformat
 * @return mixed
 */
function ilya_set_request($request, $relativeroot, $usedformat = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_request, $ilya_root_url_relative, $ilya_used_url_format;

	$ilya_request = $request;
	$ilya_root_url_relative = $relativeroot;
	$ilya_used_url_format = $usedformat;
}


/**
 * Returns the current ILYA request (slash-separated, independent of the url scheme chosen)
 */
function ilya_request()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_request;
	return $ilya_request;
}


/**
 * Returns the indexed $part (as separated by slashes) of the current ILYA request, or null if it doesn't exist
 * @param $part
 * @return
 */
function ilya_request_part($part)
{
	$parts = explode('/', ilya_request());
	return @$parts[$part];
}


/**
 * Returns an array of parts (as separated by slashes) of the current ILYA request, starting at part $start
 * @param int $start
 * @return array
 */
function ilya_request_parts($start = 0)
{
	return array_slice(explode('/', ilya_request()), $start);
}


/**
 * Return string for incoming GET/POST/COOKIE value, stripping slashes if appropriate
 * @param $string
 * @return mixed|string
 */
function ilya_gpc_to_string($string)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return get_magic_quotes_gpc() ? stripslashes($string) : $string;
}


/**
 * Return string with slashes added, if appropriate for later removal by ilya_gpc_to_string()
 * @param $string
 * @return mixed|string
 */
function ilya_string_to_gpc($string)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return get_magic_quotes_gpc() ? addslashes($string) : $string;
}


/**
 * Return string for incoming GET field, or null if it's not defined
 * @param $field
 * @return mixed|null|string
 */
function ilya_get($field)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return isset($_GET[$field]) ? ilya_gpc_to_string($_GET[$field]) : null;
}


/**
 * Return string for incoming POST field, or null if it's not defined.
 * While we're at it, trim() surrounding white space and converted to Unix line endings.
 * @param $field
 * @return mixed|null
 */
function ilya_post_text($field)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return isset($_POST[$field]) ? preg_replace('/\r\n?/', "\n", trim(ilya_gpc_to_string($_POST[$field]))) : null;
}

/**
 * Return an array for incoming POST field, or null if it's not an array or not defined.
 * While we're at it, trim() surrounding white space for each value and convert them to Unix line endings.
 * @param $field
 * @return array|mixed|null
 */
function ilya_post_array($field)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	if (!isset($_POST[$field]) || !is_array($_POST[$field])) {
		return null;
	}

	$result = array();
	foreach ($_POST[$field] as $key => $value)
		$result[$key] = preg_replace('/\r\n?/', "\n", trim(ilya_gpc_to_string($value)));

	return $result;
}


/**
 * Return true if form button $name was clicked (as type=submit/image) to create this page request, or if a
 * simulated click was sent for the button (via 'ilya_click' POST field)
 * @param $name
 * @return bool|mixed
 */
function ilya_clicked($name)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return isset($_POST[$name]) || isset($_POST[$name . '_x']) || (ilya_post_text('ilya_click') == $name);
}


/**
 * Determine the remote IP address of the user accessing the site.
 * @return mixed  String representing IP if it's available, or null otherwise.
 */
function ilya_remote_ip_address()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
}


/**
 * Checks whether an HTTP request has exceeded the post_max_size PHP variable. This happens whenever an HTTP request
 * is too big to be properly processed by PHP, usually because there is an attachment in the HTTP request. A warning
 * is added to the server's log displaying the size of the file that triggered this situation. It is important to note
 * that whenever this happens the $_POST and $_FILES superglobals are empty.
 */
function ilya_post_limit_exceeded()
{
	if (in_array($_SERVER['REQUEST_METHOD'], array('POST', 'PUT')) && empty($_POST) && empty($_FILES)) {
		$postmaxsize = ini_get('post_max_size');  // Gets the current post_max_size configuration
		$unit = substr($postmaxsize, -1);
		if (!is_numeric($unit)) {
			$postmaxsize = substr($postmaxsize, 0, -1);
		}
		// Gets an integer value that can be compared against the size of the HTTP request
		$postmaxsize = convert_to_bytes($unit, $postmaxsize);
		return $_SERVER['CONTENT_LENGTH'] > $postmaxsize;
	}
}


/**
* Turns a numeric value and a unit (g/m/k) into bytes
* @param string $unit One of 'g', 'm', 'k'. It is case insensitive
* @param int $value The value to turn into bytes
* @return int The amount of bytes the unit and the value represent. If the unit is not one of 'g', 'm' or 'k' then
* the original value is returned
*/
function convert_to_bytes($unit, $value)
{
	$value = (int) $value;

	switch (strtolower($unit)) {
		case 'g':
			return $value * pow(1024, 3);
		case 'm':
			return $value * pow(1024, 2);
		case 'k':
			return $value * 1024;
		default:
			return $value;
	}
}


/**
 * Whether we are responding to an HTTP GET request
 * @return bool True if the request is GET
 */
function ilya_is_http_get()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Return true if we are responding to an HTTP POST request
 */
function ilya_is_http_post()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return $_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_POST);
}


/**
 * Return true if we appear to be responding to a secure HTTP request (but hard to be sure)
 */
function ilya_is_https_probably()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return (@$_SERVER['HTTPS'] && ($_SERVER['HTTPS'] != 'off')) || (@$_SERVER['SERVER_PORT'] == 443);
}


/**
 * Return true if it appears the page request is coming from a human using a web browser, rather than a search engine
 * or other bot. Based on a whitelist of terms in user agents, this can easily be tricked by a scraper or bad bot.
 */
function ilya_is_human_probably()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'util/string.php';

	$useragent = @$_SERVER['HTTP_USER_AGENT'];

	return (strlen($useragent) == 0) || ilya_string_matches_one($useragent, array(
		'MSIE', 'Firefox', 'Chrome', 'Safari', 'Opera', 'Gecko', 'MIDP', 'PLAYSTATION', 'Teleca',
		'BlackBerry', 'UP.Browser', 'Polaris', 'MAUI_WAP_Browser', 'iPad', 'iPhone', 'iPod',
	));
}


/**
 * Return true if it appears that the page request is coming from a mobile client rather than a desktop/laptop web browser
 */
function ilya_is_mobile_probably()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'util/string.php';

	// inspired by: http://dangerousprototypes.com/docs/PhpBB3_MOD:_Replacement_mobile_browser_detection_for_mobile_themes

	$loweragent = strtolower(@$_SERVER['HTTP_USER_AGENT']);

	if (strpos($loweragent, 'ipad') !== false) // consider iPad as desktop
		return false;

	$mobileheaders = array('HTTP_X_OPERAMINI_PHONE', 'HTTP_X_WAP_PROFILE', 'HTTP_PROFILE');

	foreach ($mobileheaders as $header)
		if (isset($_SERVER[$header]))
			return true;

	if (ilya_string_matches_one($loweragent, array(
		'android', 'phone', 'mobile', 'windows ce', 'palm', ' mobi', 'wireless', 'blackberry', 'opera mini', 'symbian',
		'nokia', 'samsung', 'ericsson,', 'vodafone/', 'kindle', 'ipod', 'wap1.', 'wap2.', 'sony', 'sanyo', 'sharp',
		'panasonic', 'philips', 'pocketpc', 'avantgo', 'blazer', 'ipaq', 'up.browser', 'up.link', 'mmp', 'smartphone', 'midp',
	)))
		return true;

	return ilya_string_matches_one(strtolower(@$_SERVER['HTTP_ACCEPT']), array(
		'application/vnd.wap.xhtml+xml', 'text/vnd.wap.wml',
	));
}


// Language phrase support

/**
 * Return the translated string for $identifier, unless we're using external translation logic.
 * This will retrieve the 'site_language' option so make sure you've already loaded/set that if
 * loading an option now will cause a problem (see issue in ilya_default_option()). The part of
 * $identifier before the slash (/) replaces the * in the ilya-lang-*.php file references, and the
 * part after the / is the key of the array element to be taken from that file's returned result.
 * @param $identifier
 * @return string
 */
function ilya_lang($identifier)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_lang_file_pattern, $ilya_phrases_full;

	list($group, $label) = explode('/', $identifier, 2);

	if (isset($ilya_phrases_full[$group][$label]))
		return $ilya_phrases_full[$group][$label];

	if (!isset($ilya_phrases_full[$group])) {
		// load the default language files
		if (isset($ilya_lang_file_pattern[$group]))
			$include = str_replace('*', 'default', $ilya_lang_file_pattern[$group]);
		else
			$include = ILYA__INCLUDE_DIR . 'lang/ilya-lang-' . $group . '.php';

		$ilya_phrases_full[$group] = is_file($include) ? (array)(include_once $include) : array();

		// look for a localized file in ilya-lang/<lang>/
		$languagecode = ilya_opt('site_language');
		if (strlen($languagecode)) {
			if (isset($ilya_lang_file_pattern[$group]))
				$include = str_replace('*', $languagecode, $ilya_lang_file_pattern[$group]);
			else
				$include = ILYA__LANG_DIR . $languagecode . '/ilya-lang-' . $group . '.php';

			$phrases = is_file($include) ? (array)(include $include) : array();
			$ilya_phrases_full[$group] = array_merge($ilya_phrases_full[$group], $phrases);
		}

		// add any custom phrases from ilya-lang/custom/
		$include = ILYA__LANG_DIR . 'custom/ilya-lang-' . $group . '.php';
		$phrases = is_file($include) ? (array)(include $include) : array();
		$ilya_phrases_full[$group] = array_merge($ilya_phrases_full[$group], $phrases);

		if (isset($ilya_phrases_full[$group][$label]))
			return $ilya_phrases_full[$group][$label];
	}

	return '[' . $identifier . ']'; // as a last resort, return the identifier to help in development
}


/**
 * Return the translated string for $identifier, with $symbol substituted for $textparam
 * @param $identifier
 * @param $textparam
 * @param string $symbol
 * @return mixed
 */
function ilya_lang_sub($identifier, $textparam, $symbol = '^')
{
	return str_replace($symbol, $textparam, ilya_lang($identifier));
}


/**
 * Return the translated string for $identifier, converted to HTML
 * @param $identifier
 * @return mixed|string
 */
function ilya_lang_html($identifier)
{
	return ilya_html(ilya_lang($identifier));
}


/**
 * Return the translated string for $identifier converted to HTML, with $symbol *then* substituted for $htmlparam
 * @param $identifier
 * @param $htmlparam
 * @param string $symbol
 * @return mixed
 */
function ilya_lang_html_sub($identifier, $htmlparam, $symbol = '^')
{
	return str_replace($symbol, $htmlparam, ilya_lang_html($identifier));
}


/**
 * Return an array containing the translated string for $identifier converted to HTML, then split into three,
 * with $symbol substituted for $htmlparam in the 'data' element, and obvious 'prefix' and 'suffix' elements
 * @param $identifier
 * @param $htmlparam
 * @param string $symbol
 * @return array
 */
function ilya_lang_html_sub_split($identifier, $htmlparam, $symbol = '^')
{
	$html = ilya_lang_html($identifier);

	$symbolpos = strpos($html, $symbol);
	if (!is_numeric($symbolpos))
		ilya_fatal_error('Missing ' . $symbol . ' in language string ' . $identifier);

	return array(
		'prefix' => substr($html, 0, $symbolpos),
		'data' => $htmlparam,
		'suffix' => substr($html, $symbolpos + 1),
	);
}


// Request and path generation

/**
 * Return the relative path to the ILYA root (if it was previously set by ilya_set_request())
 */
function ilya_path_to_root()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_root_url_relative;
	return $ilya_root_url_relative;
}


/**
 * Return an array of mappings of ILYA requests, as defined in the ilya-config.php file
 */
function ilya_get_request_map()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_request_map;
	return $ilya_request_map;
}


/**
 * Return the relative URI path for $request, with optional parameters $params and $anchor.
 * Slashes in $request will not be urlencoded, but any other characters will.
 * If $neaturls is set, use that, otherwise retrieve the option. If $rooturl is set, take
 * that as the root of the ILYA site, otherwise use path to root which was set elsewhere.
 * @param $request
 * @param array $params
 * @param string $rooturl
 * @param int $neaturls
 * @param string $anchor
 * @return string
 */
function ilya_path($request, $params = null, $rooturl = null, $neaturls = null, $anchor = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	if (!isset($neaturls)) {
		require_once ILYA__INCLUDE_DIR . 'app/options.php';
		$neaturls = ilya_opt('neat_urls');
	}

	if (!isset($rooturl))
		$rooturl = ilya_path_to_root();

	$url = $rooturl . ((empty($rooturl) || (substr($rooturl, -1) == '/')) ? '' : '/');
	$paramsextra = '';

	$requestparts = explode('/', $request);
	$pathmap = ilya_get_request_map();

	if (isset($pathmap[$requestparts[0]])) {
		$newpart = $pathmap[$requestparts[0]];

		if (strlen($newpart))
			$requestparts[0] = $newpart;
		elseif (count($requestparts) == 1)
			array_shift($requestparts);
	}

	foreach ($requestparts as $index => $requestpart) {
		$requestparts[$index] = urlencode($requestpart);
	}
	$requestpath = implode('/', $requestparts);

	switch ($neaturls) {
		case ILYA__URL_FORMAT_INDEX:
			if (!empty($request))
				$url .= 'index.php/' . $requestpath;
			break;

		case ILYA__URL_FORMAT_NEAT:
			$url .= $requestpath;
			break;

		case ILYA__URL_FORMAT_PARAM:
			if (!empty($request))
				$paramsextra = '?ilya=' . $requestpath;
			break;

		default:
			$url .= 'index.php';

		case ILYA__URL_FORMAT_PARAMS:
			if (!empty($request)) {
				foreach ($requestparts as $partindex => $requestpart)
					$paramsextra .= (strlen($paramsextra) ? '&' : '?') . 'ilya' . ($partindex ? ('_' . $partindex) : '') . '=' . $requestpart;
			}
			break;
	}

	if (is_array($params)) {
		foreach ($params as $key => $value) {
			$value = is_array($value) ? '' : (string) $value;
			$paramsextra .= (strlen($paramsextra) ? '&' : '?') . urlencode($key) . '=' . urlencode($value);
		}
	}

	return $url . $paramsextra . (empty($anchor) ? '' : '#' . urlencode($anchor));
}


/**
 * Return HTML representation of relative URI path for $request - see ilya_path() for other parameters
 * @param $request
 * @param array $params
 * @param string $rooturl
 * @param int $neaturls
 * @param string $anchor
 * @return mixed|string
 */
function ilya_path_html($request, $params = null, $rooturl = null, $neaturls = null, $anchor = null)
{
	return ilya_html(ilya_path($request, $params, $rooturl, $neaturls, $anchor));
}


/**
 * Return the absolute URI for $request - see ilya_path() for other parameters
 * @param $request
 * @param array $params
 * @param string $anchor
 * @return string
 */
function ilya_path_absolute($request, $params = null, $anchor = null)
{
	return ilya_path($request, $params, ilya_opt('site_url'), null, $anchor);
}


/**
 * Get ILYA request for a question, and make it search-engine friendly, shortening it if necessary
 * by removing shorter words which are generally less meaningful.
 * @param int $questionid The question ID
 * @param string $title The question title
 * @return string
 */
function ilya_q_request($questionid, $title)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'app/options.php';
	require_once ILYA__INCLUDE_DIR . 'util/string.php';

	$title = ilya_block_words_replace($title, ilya_get_block_words_preg());
	$slug = ilya_slugify($title, ilya_opt('q_urls_remove_accents'), ilya_opt('q_urls_title_length'));

	return (int)$questionid . '/' . $slug;
}


/**
 * Return the HTML anchor that should be used for post $postid with $basetype (Q/A/C)
 * @param $basetype
 * @param $postid
 * @return mixed|string
 */
function ilya_anchor($basetype, $postid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return strtolower($basetype) . $postid; // used to be $postid only but this violated HTML spec
}


/**
 * Return the URL for question $questionid with $title, possibly using $absolute URLs.
 * To link to a specific answer or comment in a question, set $showtype and $showid accordingly.
 * @param $questionid
 * @param $title
 * @param bool $absolute
 * @param string $showtype
 * @param int $showid
 * @return mixed|string
 */
function ilya_q_path($questionid, $title, $absolute = false, $showtype = null, $showid = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	if (($showtype == 'Q' || $showtype == 'A' || $showtype == 'C') && isset($showid)) {
		$params = array('show' => $showid); // due to pagination
		$anchor = ilya_anchor($showtype, $showid);

	} else {
		$params = null;
		$anchor = null;
	}

	return ilya_path(ilya_q_request($questionid, $title), $params, $absolute ? ilya_opt('site_url') : null, null, $anchor);
}


/**
 * Return the HTML representation of the URL for $questionid - other parameters as for ilya_q_path()
 * @param $questionid
 * @param $title
 * @param bool $absolute
 * @param string $showtype
 * @param int $showid
 * @return mixed|string
 */
function ilya_q_path_html($questionid, $title, $absolute = false, $showtype = null, $showid = null)
{
	return ilya_html(ilya_q_path($questionid, $title, $absolute, $showtype, $showid));
}


/**
 * Return the request for the specified $feed
 * @param $feed
 * @return mixed|string
 */
function ilya_feed_request($feed)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return 'feed/' . $feed . '.rss';
}


/**
 * Return an HTML-ready relative URL for the current page, preserving GET parameters - this is useful for action="..." in HTML forms
 */
function ilya_self_html()
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_used_url_format;

	return ilya_path_html(ilya_request(), $_GET, null, $ilya_used_url_format);
}


/**
 * Return HTML for hidden fields to insert into a <form method="get"...> on the page.
 * This is needed because any parameters on the URL will be lost when the form is submitted.
 * @param $request
 * @param array $params
 * @param string $rooturl
 * @param int $neaturls
 * @param string $anchor
 * @return mixed|string
 */
function ilya_path_form_html($request, $params = null, $rooturl = null, $neaturls = null, $anchor = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$path = ilya_path($request, $params, $rooturl, $neaturls, $anchor);
	$formhtml = '';

	$questionpos = strpos($path, '?');
	if (is_numeric($questionpos)) {
		$params = explode('&', substr($path, $questionpos + 1));

		foreach ($params as $param)
			if (preg_match('/^([^\=]*)(\=(.*))?$/', $param, $matches))
				$formhtml .= '<input type="hidden" name="' . ilya_html(urldecode($matches[1])) . '" value="' . ilya_html(urldecode(@$matches[3])) . '"/>';
	}

	return $formhtml;
}


/**
 * Redirect the user's web browser to $request and then we're done - see ilya_path() for other parameters
 * @param $request
 * @param array $params
 * @param string $rooturl
 * @param int $neaturls
 * @param string $anchor
 * @return mixed
 */
function ilya_redirect($request, $params = null, $rooturl = null, $neaturls = null, $anchor = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	ilya_redirect_raw(ilya_path($request, $params, $rooturl, $neaturls, $anchor));
}


/**
 * Redirect the user's web browser to page $path which is already a URL
 * @param $url
 * @return mixed
 */
function ilya_redirect_raw($url)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	header('Location: ' . $url);
	ilya_exit('redirect');
}


// General utilities

/**
 * Return the contents of remote $url, using file_get_contents() if possible, otherwise curl functions
 * @param $url
 * @return mixed|string
 */
function ilya_retrieve_url($url)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	// ensure we're fetching a remote URL
	if (!preg_match('#^https?://#', $url)) {
		return '';
	}

	$contents = @file_get_contents($url);

	if (!strlen($contents) && function_exists('curl_exec')) { // try curl as a backup (if allow_url_fopen not set)
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$contents = @curl_exec($curl);
		curl_close($curl);
	}

	return $contents;
}


/**
 * Shortcut to get or set an option value without specifying database
 * @param $name
 * @param mixed $value
 * @return
 */
function ilya_opt($name, $value = null)
{
	global $ilya_options_cache;

	if (!isset($value) && isset($ilya_options_cache[$name]))
		return $ilya_options_cache[$name]; // quick shortcut to reduce calls to ilya_get_options()

	require_once ILYA__INCLUDE_DIR . 'app/options.php';

	if (isset($value))
		ilya_set_option($name, $value);

	$options = ilya_get_options(array($name));

	return $options[$name];
}

/**
 * Simple method to output a preformatted variable
 * @param $var
 */
function ilya_debug($var)
{
	echo "\n" . '<pre style="padding: 10px; background-color: #eee; color: #444; font-size: 11px; text-align: left">';
	echo $var === null ? 'NULL' : htmlspecialchars(print_r($var, true), ENT_COMPAT|ENT_SUBSTITUTE);
	echo '</pre>' . "\n";
}


// Event and process stage reporting

/**
 * Suspend the reporting of events to event modules via ilya_report_event(...) if $suspend is
 * true, otherwise reinstate it. A counter is kept to allow multiple calls.
 * @param bool $suspend
 */
function ilya_suspend_event_reports($suspend = true)
{
	global $ilya_event_reports_suspended;

	$ilya_event_reports_suspended += ($suspend ? 1 : -1);
}


/**
 * Send a notification of event $event by $userid, $handle and $cookieid to all event modules, with extra $params
 * @param $event
 * @param $userid
 * @param $handle
 * @param $cookieid
 * @param array $params
 * @return mixed|void
 */
function ilya_report_event($event, $userid, $handle, $cookieid, $params = array())
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	global $ilya_event_reports_suspended;

	if ($ilya_event_reports_suspended > 0)
		return;

	$eventmodules = ilya_load_modules_with('event', 'process_event');
	foreach ($eventmodules as $eventmodule)
		$eventmodule->process_event($event, $userid, $handle, $cookieid, $params);
}


function ilya_report_process_stage($method) // can have extra params
{
	global $ilya_process_reports_suspended;

	if (@$ilya_process_reports_suspended)
		return;

	$ilya_process_reports_suspended = true; // prevent loop, e.g. because of an error

	$args = func_get_args();
	$args = array_slice($args, 1);

	$processmodules = ilya_load_modules_with('process', $method);
	foreach ($processmodules as $processmodule) {
		call_user_func_array(array($processmodule, $method), $args);
	}

	$ilya_process_reports_suspended = null;
}
