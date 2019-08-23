<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for admin page listing plugins and showing their options


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
	header('Location: ../../../');
	exit;
}

require_once ILYA__INCLUDE_DIR . 'app/admin.php';


// Check admin privileges

if (!ilya_admin_check_privileges($ilya_content))
	return $ilya_content;

// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/admin_title') . ' - ' . ilya_lang_html('admin/plugins_title');

$ilya_content['error'] = ilya_admin_page_error();

$ilya_content['script_rel'][] = 'ilya-content/ilya-admin.js?' . ILYA__VERSION;


$pluginManager = new ILYA_Plugin_PluginManager();
$pluginManager->cleanRemovedPlugins();

$enabledPlugins = $pluginManager->getEnabledPlugins();
$fileSystemPlugins = $pluginManager->getFilesystemPlugins();

$pluginHashes = $pluginManager->getHashesForPlugins($fileSystemPlugins);

$showpluginforms = true;
if (ilya_is_http_post()) {
	if (!ilya_check_form_security_code('admin/plugins', ilya_post_text('ilya_form_security_code'))) {
		$ilya_content['error'] = ilya_lang_html('misc/form_security_reload');
		$showpluginforms = false;
	} else {
		if (ilya_clicked('dosave')) {
			$enabledPluginHashes = ilya_post_text('enabled_plugins_hashes');
			$enabledPluginHashesArray = explode(';', $enabledPluginHashes);
			$pluginDirectories = array_keys(array_intersect($pluginHashes, $enabledPluginHashesArray));
			$pluginManager->setEnabledPlugins($pluginDirectories);

			ilya_redirect('admin/plugins');
		}
	}
}

// Map modules with options to their containing plugins

$pluginoptionmodules = array();

$tables = ilya_db_list_tables();
$moduletypes = ilya_list_module_types();

foreach ($moduletypes as $type) {
	$modules = ilya_list_modules($type);

	foreach ($modules as $name) {
		$module = ilya_load_module($type, $name);

		if (method_exists($module, 'admin_form')) {
			$info = ilya_get_module_info($type, $name);
			$dir = rtrim($info['directory'], '/');
			$pluginoptionmodules[$dir][] = array(
				'type' => $type,
				'name' => $name,
			);
		}
	}
}

foreach ($moduletypes as $type) {
	$modules = ilya_load_modules_with($type, 'init_queries');

	foreach ($modules as $name => $module) {
		$queries = $module->init_queries($tables);

		if (!empty($queries)) {
			if (ilya_is_http_post())
				ilya_redirect('install');

			else {
				$ilya_content['error'] = strtr(ilya_lang_html('admin/module_x_database_init'), array(
					'^1' => ilya_html($name),
					'^2' => ilya_html($type),
					'^3' => '<a href="' . ilya_path_html('install') . '">',
					'^4' => '</a>',
				));
			}
		}
	}
}


if (!empty($fileSystemPlugins)) {
	$metadataUtil = new ILYA_Util_Metadata();
	$sortedPluginFiles = array();

	foreach ($fileSystemPlugins as $pluginDirectory) {
		$pluginDirectoryPath = ILYA__PLUGIN_DIR . $pluginDirectory;
		$metadata = $metadataUtil->fetchFromAddonPath($pluginDirectoryPath);
		if (empty($metadata)) {
			$pluginFile = $pluginDirectoryPath . '/ilya-plugin.php';

			// limit plugin parsing to first 8kB
			$contents = file_get_contents($pluginFile, false, null, 0, 8192);
			$metadata = ilya_addon_metadata($contents, 'Plugin');
		}

		$metadata['name'] = isset($metadata['name']) && !empty($metadata['name'])
			? ilya_html($metadata['name'])
			: ilya_lang_html('admin/unnamed_plugin');
		$sortedPluginFiles[$pluginDirectory] = $metadata;
	}

	ilya_sort_by($sortedPluginFiles, 'name');

	$pluginIndex = -1;
	foreach ($sortedPluginFiles as $pluginDirectory => $metadata) {
		$pluginIndex++;

		$pluginDirectoryPath = ILYA__PLUGIN_DIR . $pluginDirectory;
		$hash = $pluginHashes[$pluginDirectory];
		$showthisform = $showpluginforms && (ilya_get('show') == $hash);

		$namehtml = $metadata['name'];

		if (isset($metadata['uri']) && strlen($metadata['uri']))
			$namehtml = '<a href="' . ilya_html($metadata['uri']) . '">' . $namehtml . '</a>';

		$namehtml = '<b>' . $namehtml . '</b>';

		$metaver = isset($metadata['version']) && strlen($metadata['version']);
		if ($metaver)
			$namehtml .= ' v' . ilya_html($metadata['version']);

		if (isset($metadata['author']) && strlen($metadata['author'])) {
			$authorhtml = ilya_html($metadata['author']);

			if (isset($metadata['author_uri']) && strlen($metadata['author_uri']))
				$authorhtml = '<a href="' . ilya_html($metadata['author_uri']) . '">' . $authorhtml . '</a>';

			$authorhtml = ilya_lang_html_sub('main/by_x', $authorhtml);

		} else
			$authorhtml = '';

		if ($metaver && isset($metadata['update_uri']) && strlen($metadata['update_uri'])) {
			$elementid = 'version_check_' . md5($pluginDirectory);

			$updatehtml = '(<span id="' . $elementid . '">...</span>)';

			$ilya_content['script_onloads'][] = array(
				"ilya_version_check(" . ilya_js($metadata['update_uri']) . ", " . ilya_js($metadata['version'], true) . ", " . ilya_js($elementid) . ", false);"
			);
		}
		else
			$updatehtml = '';

		if (isset($metadata['description']))
			$deschtml = ilya_html($metadata['description']);
		else
			$deschtml = '';

		if (isset($pluginoptionmodules[$pluginDirectoryPath]) && !$showthisform) {
			$deschtml .= (strlen($deschtml) ? ' - ' : '') . '<a href="' . ilya_admin_plugin_options_path($pluginDirectory) . '">' .
				ilya_lang_html('admin/options') . '</a>';
		}

		$allowDisable = isset($metadata['load_order']) && $metadata['load_order'] === 'after_db_init';
		$beforeDbInit = isset($metadata['load_order']) && $metadata['load_order'] === 'before_db_init';
		$enabled = $beforeDbInit || !$allowDisable || in_array($pluginDirectory, $enabledPlugins);

		$pluginhtml = $namehtml . ' ' . $authorhtml . ' ' . $updatehtml . '<br>';
		$pluginhtml .= $deschtml . (strlen($deschtml) > 0 ? '<br>' : '');
		$pluginhtml .= '<small style="color:#666">' . ilya_html($pluginDirectoryPath) . '/</small>';

		if (ilya_ilya_version_below(@$metadata['min_ilya']))
			$pluginhtml = '<s style="color:#999">'.$pluginhtml.'</s><br><span style="color:#f00">'.
				ilya_lang_html_sub('admin/requires_ilya_version', ilya_html($metadata['min_ilya'])).'</span>';

		elseif (ilya_php_version_below(@$metadata['min_php']))
			$pluginhtml = '<s style="color:#999">'.$pluginhtml.'</s><br><span style="color:#f00">'.
				ilya_lang_html_sub('admin/requires_php_version', ilya_html($metadata['min_php'])).'</span>';

		$ilya_content['form_plugin_'.$pluginIndex] = array(
			'tags' => 'id="'.ilya_html($hash).'"',
			'style' => 'tall',
			'fields' => array(
				array(
					'type' => 'checkbox',
					'label' => ilya_lang_html('admin/enabled'),
					'value' => $enabled,
					'tags' => sprintf('id="plugin_enabled_%s"%s', $hash, $allowDisable ? '' : ' disabled'),
				),
				array(
					'type' => 'custom',
					'html' => $pluginhtml,
				),
			),
		);

		if ($showthisform && isset($pluginoptionmodules[$pluginDirectoryPath])) {
			foreach ($pluginoptionmodules[$pluginDirectoryPath] as $pluginoptionmodule) {
				$type = $pluginoptionmodule['type'];
				$name = $pluginoptionmodule['name'];

				$module = ilya_load_module($type, $name);

				$form = $module->admin_form($ilya_content);

				if (!isset($form['tags']))
					$form['tags'] = 'method="post" action="' . ilya_admin_plugin_options_path($pluginDirectory) . '"';

				if (!isset($form['style']))
					$form['style'] = 'tall';

				$form['boxed'] = true;

				$form['hidden']['ilya_form_security_code'] = ilya_get_form_security_code('admin/plugins');

				$ilya_content['form_plugin_options'] = $form;
			}
		}
	}
}

$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();

$ilya_content['form'] = array(
	'tags' => 'method="post" action="' . ilya_self_html() . '" name="plugins_form" onsubmit="ilya_get_enabled_plugins_hashes(); return true;"',

	'style' => 'wide',

	'buttons' => array(
		'dosave' => array(
			'tags' => 'name="dosave"',
			'label' => ilya_lang_html('admin/save_options_button'),
		),
	),

	'hidden' => array(
		'ilya_form_security_code' => ilya_get_form_security_code('admin/plugins'),
		'enabled_plugins_hashes' => '',
	),
);


return $ilya_content;
