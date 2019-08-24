<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Server-side response to Ajax version check requests


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

require_once ILYA_INCLUDE_DIR . 'app/admin.php';
require_once ILYA_INCLUDE_DIR . 'app/users.php';

if (ilya_get_logged_in_level() < ILYA_USER_LEVEL_ADMIN) {
	echo "ILYA_AJAX_RESPONSE\n0\n" . ilya_lang_html('admin/no_privileges');
	return;
}

$uri = ilya_post_text('uri');
$currentVersion = ilya_post_text('version');
$isCore = ilya_post_text('isCore') === "true";

if ($isCore) {
	$contents = ilya_retrieve_url($uri);

	if (strlen($contents) > 0) {
		if (ilya_ilya_version_below($contents)) {
			$response =
				'<a href="https://github.com/ilya/question2answer/releases" style="color:#d00;">' .
				ilya_lang_html_sub('admin/version_get_x', ilya_html('v' . $contents)) .
				'</a>';
		} else {
			$response = ilya_html($contents); // Output the current version number
		}
	} else {
		$response = ilya_lang_html('admin/version_latest_unknown');
	}
} else {
	$metadataUtil = new ILYA_Util_Metadata();
	$metadata = $metadataUtil->fetchFromUrl($uri);

	if (strlen(@$metadata['version']) > 0) {
		if (version_compare($currentVersion, $metadata['version']) < 0) {
			if (ilya_ilya_version_below(@$metadata['min_ilya'])) {
				$response = strtr(ilya_lang_html('admin/version_requires_ilya'), array(
					'^1' => ilya_html('v' . $metadata['version']),
					'^2' => ilya_html($metadata['min_ilya']),
				));
			} elseif (ilya_php_version_below(@$metadata['min_php'])) {
				$response = strtr(ilya_lang_html('admin/version_requires_php'), array(
					'^1' => ilya_html('v' . $metadata['version']),
					'^2' => ilya_html($metadata['min_php']),
				));
			} else {
				$response = ilya_lang_html_sub('admin/version_get_x', ilya_html('v' . $metadata['version']));

				if (strlen(@$metadata['uri'])) {
					$response = '<a href="' . ilya_html($metadata['uri']) . '" style="color:#d00;">' . $response . '</a>';
				}
			}
		} else {
			$response = ilya_lang_html('admin/version_latest');
		}
	} else {
		$response = ilya_lang_html('admin/version_latest_unknown');
	}
}

echo "ILYA_AJAX_RESPONSE\n1\n" . $response;
