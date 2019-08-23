<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: User interface for installing, upgrading and fixing the database


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
	header('Location: ../');
	exit;
}

require_once ILYA__INCLUDE_DIR.'db/install.php';

ilya_report_process_stage('init_install');


// Define database failure handler for install process, if not defined already (file could be included more than once)

if (!function_exists('ilya_install_db_fail_handler')) {
	/**
	 * Handler function for database failures during the installation process
	 * @param $type
	 * @param int $errno
	 * @param string $error
	 * @param string $query
	 */
	function ilya_install_db_fail_handler($type, $errno = null, $error = null, $query = null)
	{
		global $pass_failure_from_install;

		$pass_failure_type = $type;
		$pass_failure_errno = $errno;
		$pass_failure_error = $error;
		$pass_failure_query = $query;
		$pass_failure_from_install = true;

		require ILYA__INCLUDE_DIR.'ilya-install.php';

		ilya_exit('error');
	}
}


if (ob_get_level() > 0) {
	// clears any current theme output to prevent broken design
	ob_end_clean();
	// prevents browser content encoding error
	header('Content-Encoding: none');
}

$success = '';
$errorhtml = '';
$suggest = '';
$buttons = array();
$fields = array();
$fielderrors = array();
$hidden = array();


// Process user handling higher up to avoid 'headers already sent' warning

if (!isset($pass_failure_type) && ilya_clicked('super')) {
	require_once ILYA__INCLUDE_DIR.'db/admin.php';
	require_once ILYA__INCLUDE_DIR.'db/users.php';
	require_once ILYA__INCLUDE_DIR.'app/users-edit.php';

	if (ilya_db_count_users() == 0) { // prevent creating multiple accounts
		$inemail = ilya_post_text('email');
		$inpassword = ilya_post_text('password');
		$inhandle = ilya_post_text('handle');

		$fielderrors = array_merge(
			ilya_handle_email_filter($inhandle, $inemail),
			ilya_password_validate($inpassword)
		);

		if (empty($fielderrors)) {
			require_once ILYA__INCLUDE_DIR.'app/users.php';

			$userid = ilya_create_new_user($inemail, $inpassword, $inhandle, ILYA__USER_LEVEL_SUPER);
			ilya_set_logged_in_user($userid, $inhandle);

			ilya_set_option('feedback_email', $inemail);

			$success .= "Congratulations - Your IlyaIdea site is ready to go!\n\nYou are logged in as the super administrator and can start changing settings.\n\nThank you for installing IlyaIdea.";
		}
	}
}


// Output start of HTML early, so we can see a nicely-formatted list of database queries when upgrading

?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<style>
			body, input { font: 16px Verdana, Arial, Helvetica, sans-serif; }
			body { text-align: center; width: 640px; margin: 64px auto; }
			table { margin: 16px auto; }
			th, td { padding: 2px; }
			th { text-align: right; font-weight: normal; }
			td { text-align: left; }
			.msg-success { color: #090; }
			.msg-error { color: #b00; }
		</style>
	</head>
	<body>
<?php


if (isset($pass_failure_type)) {
	// this page was requested due to query failure, via the fail handler
	switch ($pass_failure_type) {
		case 'connect':
			$errorhtml .= 'Could not establish database connection. Please check the username, password and hostname in the config file, and if necessary set up the appropriate MySQL user and privileges.';
			break;

		case 'select':
			$errorhtml .= 'Could not switch to the IlyaIdea database. Please check the database name in the config file, and if necessary create the database in MySQL and grant appropriate user privileges.';
			break;

		case 'query':
			global $pass_failure_from_install;

			if (@$pass_failure_from_install)
				$errorhtml .= "IlyaIdea was unable to perform the installation query below. Please check the user in the config file has CREATE and ALTER permissions:\n\n".ilya_html($pass_failure_query."\n\nError ".$pass_failure_errno.": ".$pass_failure_error."\n\n");
			else
				$errorhtml .= "A IlyaIdea database query failed when generating this page.\n\nA full description of the failure is available in the web server's error log file.";
			break;
	}
}
else {
	// this page was requested by user GET/POST, so handle any incoming clicks on buttons

	if (ilya_clicked('create')) {
		ilya_db_install_tables();

		if (ILYA__FINAL_EXTERNAL_USERS) {
			if (defined('ILYA__FINAL_WORDPRESS_INTEGRATE_PATH')) {
				require_once ILYA__INCLUDE_DIR.'db/admin.php';
				require_once ILYA__INCLUDE_DIR.'app/format.php';

				// create link back to WordPress home page
				ilya_db_page_move(ilya_db_page_create(get_option('blogname'), ILYA__PAGE_FLAGS_EXTERNAL, get_option('home'), null, null, null), 'O', 1);

				$success .= 'Your IlyaIdea database has been created and integrated with your WordPress site.';

			}
			elseif (defined('ILYA__FINAL_JOOMLA_INTEGRATE_PATH')) {
				require_once ILYA__INCLUDE_DIR.'db/admin.php';
				require_once ILYA__INCLUDE_DIR.'app/format.php';
				$jconfig = new JConfig();

				// create link back to Joomla! home page (Joomla doesn't have a 'home' config setting we can use like WP does, so we'll just assume that the Joomla home is the parent of the ILYA site. If it isn't, the user can fix the link for themselves later)
				ilya_db_page_move(ilya_db_page_create($jconfig->sitename, ILYA__PAGE_FLAGS_EXTERNAL, '../', null, null, null), 'O', 1);
				$success .= 'Your IlyaIdea database has been created and integrated with your Joomla! site.';
			}
			else {
				$success .= 'Your IlyaIdea database has been created for external user identity management. Please read the online documentation to complete integration.';
			}
		}
		else {
			$success .= 'Your IlyaIdea database has been created.';
		}
	}

	if (ilya_clicked('nonuser')) {
		ilya_db_install_tables();
		$success .= 'The additional IlyaIdea database tables have been created.';
	}

	if (ilya_clicked('upgrade')) {
		ilya_db_upgrade_tables();
		$success .= 'Your IlyaIdea database has been updated.';
	}

	if (ilya_clicked('repair')) {
		ilya_db_install_tables();
		$success .= 'The IlyaIdea database tables have been repaired.';
	}

	ilya_initialize_postdb_plugins();
	if (ilya_clicked('module')) {
		$moduletype = ilya_post_text('moduletype');
		$modulename = ilya_post_text('modulename');

		$module = ilya_load_module($moduletype, $modulename);

		$queries = $module->init_queries(ilya_db_list_tables());

		if (!empty($queries)) {
			if (!is_array($queries))
				$queries = array($queries);

			foreach ($queries as $query)
				ilya_db_upgrade_query($query);
		}

		$success .= 'The '.$modulename.' '.$moduletype.' module has completed database initialization.';
	}

}

if (ilya_db_connection(false) !== null && !@$pass_failure_from_install) {
	$check = ilya_db_check_tables(); // see where the database is at

	switch ($check) {
		case 'none':
			if (@$pass_failure_errno == 1146) // don't show error if we're in installation process
				$errorhtml = '';

			$errorhtml .= 'Welcome to IlyaIdea. It\'s time to set up your database!';

			if (ILYA__FINAL_EXTERNAL_USERS) {
				if (defined('ILYA__FINAL_WORDPRESS_INTEGRATE_PATH')) {
					$errorhtml .= "\n\nWhen you click below, your IlyaIdea site will be set up to integrate with the users of your WordPress site <a href=\"".ilya_html(get_option('home'))."\" target=\"_blank\">".ilya_html(get_option('blogname'))."</a>. Please consult the online documentation for more information.";
				}
				elseif (defined('ILYA__FINAL_JOOMLA_INTEGRATE_PATH')) {
					$jconfig = new JConfig();
					$errorhtml .= "\n\nWhen you click below, your IlyaIdea site will be set up to integrate with the users of your Joomla! site <a href=\"../\" target=\"_blank\">".$jconfig->sitename."</a>. It's also recommended to install the Joomla QAIntegration plugin for additional user-access control. Please consult the online documentation for more information.";
				}
				else {
					$errorhtml .= "\n\nWhen you click below, your IlyaIdea site will be set up to integrate with your existing user database and management. Users will be referenced with database column type ".ilya_html(ilya_get_mysql_user_column_type()).". Please consult the online documentation for more information.";
				}

				$buttons = array('create' => 'Set up the Database');
			}
			else {
				$errorhtml .= "\n\nWhen you click below, your IlyaIdea database will be set up to manage user identities and logins internally.\n\nIf you want to offer a single sign-on for an existing user base or website, please consult the online documentation before proceeding.";
				$buttons = array('create' => 'Set up the Database including User Management');
			}
			break;

		case 'old-version':
			// don't show error if we need to upgrade
			if (!@$pass_failure_from_install)
				$errorhtml = '';

			// don't show error before this
			$errorhtml .= 'Your IlyaIdea database needs to be upgraded for this version of the software.';
			$buttons = array('upgrade' => 'Upgrade the Database');
			break;

		case 'non-users-missing':
			$errorhtml = 'This IlyaIdea site is sharing its users with another ILYA site, but it needs some additional database tables for its own content. Please click below to create them.';
			$buttons = array('nonuser' => 'Set up the Tables');
			break;

		case 'table-missing':
			$errorhtml .= 'One or more tables are missing from your IlyaIdea database.';
			$buttons = array('repair' => 'Repair the Database');
			break;

		case 'column-missing':
			$errorhtml .= 'One or more IlyaIdea database tables are missing a column.';
			$buttons = array('repair' => 'Repair the Database');
			break;

		default:
			require_once ILYA__INCLUDE_DIR.'db/admin.php';

			if (!ILYA__FINAL_EXTERNAL_USERS && ilya_db_count_users() == 0) {
				$errorhtml .= "There are currently no users in the IlyaIdea database.\n\nPlease enter your details below to create the super administrator:";
				$fields = array(
					'handle' => array('label' => 'Username:', 'type' => 'text'),
					'password' => array('label' => 'Password:', 'type' => 'password'),
					'email' => array('label' => 'Email address:', 'type' => 'text'),
				);
				$buttons = array('super' => 'Set up the Super Administrator');
			}
			else {
				$tables = ilya_db_list_tables();

				$moduletypes = ilya_list_module_types();

				foreach ($moduletypes as $moduletype) {
					$modules = ilya_load_modules_with($moduletype, 'init_queries');

					foreach ($modules as $modulename => $module) {
						$queries = $module->init_queries($tables);
						if (!empty($queries)) {
							// also allows single query to be returned
							$errorhtml = strtr(ilya_lang_html('admin/module_x_database_init'), array(
								'^1' => ilya_html($modulename),
								'^2' => ilya_html($moduletype),
								'^3' => '',
								'^4' => '',
							));

							$buttons = array('module' => 'Initialize the Database');

							$hidden['moduletype'] = $moduletype;
							$hidden['modulename'] = $modulename;
							break;
						}
					}
				}
			}
			break;
	}
}

if (empty($errorhtml)) {
	if (empty($success))
		$success = 'Your IlyaIdea database has been checked with no problems.';

	$suggest = '<a href="'.ilya_path_html('admin', null, null, ILYA__URL_FORMAT_SAFEST).'">Go to admin center</a>';
}

?>

		<form method="post" action="<?php echo ilya_path_html('install', null, null, ILYA__URL_FORMAT_SAFEST)?>">

<?php

if (strlen($success))
	echo '<p class="msg-success">'.nl2br(ilya_html($success)).'</p>';

if (strlen($errorhtml))
	echo '<p class="msg-error">'.nl2br($errorhtml).'</p>';

if (strlen($suggest))
	echo '<p>'.$suggest.'</p>';


// Very simple general form display logic (we don't use theme since it depends on tons of DB options)

if (count($fields)) {
	echo '<table>';

	foreach ($fields as $name => $field) {
		echo '<tr>';
		echo '<th>'.ilya_html($field['label']).'</th>';
		echo '<td><input type="'.ilya_html($field['type']).'" size="24" name="'.ilya_html($name).'" value="'.ilya_html(@${'in'.$name}).'"></td>';
		if (isset($fielderrors[$name]))
			echo '<td class="msg-error"><small>'.ilya_html($fielderrors[$name]).'</small></td>';
		else
			echo '<td></td>';
		echo '</tr>';
	}

	echo '</table>';
}

foreach ($buttons as $name => $value)
	echo '<input type="submit" name="'.ilya_html($name).'" value="'.ilya_html($value).'">';

foreach ($hidden as $name => $value)
	echo '<input type="hidden" name="'.ilya_html($name).'" value="'.ilya_html($value).'">';

ilya_db_disconnect();

?>

		</form>
	</body>
</html>
