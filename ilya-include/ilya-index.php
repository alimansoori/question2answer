<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: The Grand Central of ILYA - most requests come through here


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

// Try our best to set base path here just in case it wasn't set in index.php (pre version 1.0.1)

if (!defined('ILYA_BASE_DIR')) {
	define('ILYA_BASE_DIR', dirname(empty($_SERVER['SCRIPT_FILENAME']) ? dirname(__FILE__) : $_SERVER['SCRIPT_FILENAME']) . '/');
}


// If this is an special non-page request, branch off here

if (isset($_POST['ilya']) && $_POST['ilya'] == 'ajax') {
	require 'ilya-ajax.php';
}

elseif (isset($_GET['ilya']) && $_GET['ilya'] == 'image') {
	require 'ilya-image.php';
}

elseif (isset($_GET['ilya']) && $_GET['ilya'] == 'blob') {
	require 'ilya-blob.php';
}

else {
	// Otherwise, load the ILYA base file which sets up a bunch of crucial stuff
	$ilya_autoconnect = false;
	require 'ilya-base.php';

	/**
	 * Determine the request and root of the installation, and the requested start position used by many pages.
	 *
	 * Apache and Nginx behave slightly differently:
	 *   Apache ilya-rewrite unescapes characters, converts `+` to ` `, cuts off at `#` or `&`
	 *   Nginx ilya-rewrite unescapes characters, retains `+`, contains true path
	 */
	function ilya_index_set_request()
	{
		if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

		$relativedepth = 0;

		if (isset($_GET['ilya-rewrite'])) { // URLs rewritten by .htaccess or Nginx
			$urlformat = ILYA_URL_FORMAT_NEAT;
			$ilya_rewrite = strtr(ilya_gpc_to_string($_GET['ilya-rewrite']), '+', ' '); // strtr required by Nginx
			$requestparts = explode('/', $ilya_rewrite);
			unset($_GET['ilya-rewrite']);

			if (!empty($_SERVER['REQUEST_URI'])) { // workaround for the fact that Apache unescapes characters while rewriting
				$origpath = $_SERVER['REQUEST_URI'];
				$_GET = array();

				$questionpos = strpos($origpath, '?');
				if (is_numeric($questionpos)) {
					$params = explode('&', substr($origpath, $questionpos + 1));

					foreach ($params as $param) {
						if (preg_match('/^([^\=]*)(\=(.*))?$/', $param, $matches)) {
							$argument = strtr(urldecode($matches[1]), '.', '_'); // simulate PHP's $_GET behavior
							$_GET[$argument] = ilya_string_to_gpc(urldecode(@$matches[3]));
						}
					}

					$origpath = substr($origpath, 0, $questionpos);
				}

				// Generally we assume that $_GET['ilya-rewrite'] has the right path depth, but this won't be the case if there's
				// a & or # somewhere in the middle of the path, due to Apache unescaping. So we make a special case for that.
				// If 'REQUEST_URI' and 'ilya-rewrite' already match (as on Nginx), we can skip this.
				$normalizedpath = urldecode($origpath);
				if (substr($normalizedpath, -strlen($ilya_rewrite)) !== $ilya_rewrite) {
					$keepparts = count($requestparts);
					$requestparts = explode('/', urldecode($origpath)); // new request calculated from $_SERVER['REQUEST_URI']

					// loop forwards so we capture all parts
					for ($part = 0, $max = count($requestparts); $part < $max; $part++) {
						if (is_numeric(strpos($requestparts[$part], '&')) || is_numeric(strpos($requestparts[$part], '#'))) {
							$keepparts += count($requestparts) - $part - 1; // this is how many parts remain
							break;
						}
					}

					$requestparts = array_slice($requestparts, -$keepparts); // remove any irrelevant parts from the beginning
				}
			}

			$relativedepth = count($requestparts);
		} elseif (isset($_GET['ilya'])) {
			if (strpos($_GET['ilya'], '/') === false) {
				$urlformat = (empty($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/index.php') !== false)
					? ILYA_URL_FORMAT_SAFEST : ILYA_URL_FORMAT_PARAMS;
				$requestparts = array(ilya_gpc_to_string($_GET['ilya']));

				for ($part = 1; $part < 10; $part++) {
					if (isset($_GET['ilya_' . $part])) {
						$requestparts[] = ilya_gpc_to_string($_GET['ilya_' . $part]);
						unset($_GET['ilya_' . $part]);
					}
				}
			} else {
				$urlformat = ILYA_URL_FORMAT_PARAM;
				$requestparts = explode('/', ilya_gpc_to_string($_GET['ilya']));
			}

			unset($_GET['ilya']);
		} else {
			$normalizedpath = strtr($_SERVER['PHP_SELF'], '+', ' '); // seems necessary, and plus does not work with this scheme
			$indexpath = '/index.php/';
			$indexpos = strpos($normalizedpath, $indexpath);

			if (!empty($_SERVER['REQUEST_URI'])) { // workaround for the fact that Apache unescapes characters
				$origpath = $_SERVER['REQUEST_URI'];
				$questionpos = strpos($origpath, '?');
				if ($questionpos !== false) {
					$origpath = substr($origpath, 0, $questionpos);
				}

				$normalizedpath = urldecode($origpath);
				$indexpos = strpos($normalizedpath, $indexpath);
			}

			if (is_numeric($indexpos)) {
				$urlformat = ILYA_URL_FORMAT_INDEX;
				$requestparts = explode('/', substr($normalizedpath, $indexpos + strlen($indexpath)));
				$relativedepth = 1 + count($requestparts);
			} else {
				$urlformat = null; // at home page so can't identify path type
				$requestparts = array();
			}
		}

		foreach ($requestparts as $part => $requestpart) { // remove any blank parts
			if (!strlen($requestpart))
				unset($requestparts[$part]);
		}

		reset($requestparts);
		$key = key($requestparts);

		$requestkey = isset($requestparts[$key]) ? $requestparts[$key] : '';
		$replacement = array_search($requestkey, ilya_get_request_map());
		if ($replacement !== false)
			$requestparts[$key] = $replacement;

		ilya_set_request(
			implode('/', $requestparts),
			($relativedepth > 1 ? str_repeat('../', $relativedepth - 1) : './'),
			$urlformat
		);
	}

	ilya_index_set_request();


	// Branch off to appropriate file for further handling

	$requestlower = strtolower(ilya_request());

	if ($requestlower == 'install') {
		require ILYA_INCLUDE_DIR . 'ilya-install.php';
	} elseif ($requestlower == 'url/test/' . ILYA_URL_TEST_STRING) {
		require ILYA_INCLUDE_DIR . 'ilya-url-test.php';
	} else {
		// enable gzip compression for output (needs to come early)
		ilya_initialize_buffering($requestlower);

		if (substr($requestlower, 0, 5) == 'feed/') {
			require ILYA_INCLUDE_DIR . 'ilya-feed.php';
		} else {
			require ILYA_INCLUDE_DIR . 'ilya-page.php';
		}
	}
}

ilya_report_process_stage('shutdown');
