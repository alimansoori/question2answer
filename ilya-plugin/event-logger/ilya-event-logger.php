<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	File: ilya-plugin/event-logger/ilya-event-logger.php
	Description: Event module class for event logger plugin


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

class ilya_event_logger
{
	public function init_queries($table_list)
	{
		if (ilya_opt('event_logger_to_database')) {
			$tablename = ilya_db_add_table_prefix('eventlog');

			if (!in_array($tablename, $table_list)) {
				// table does not exist, so create it
				require_once ILYA__INCLUDE_DIR . 'app/users.php';
				require_once ILYA__INCLUDE_DIR . 'db/maxima.php';

				return 'CREATE TABLE ^eventlog (' .
					'datetime DATETIME NOT NULL,' .
					'ipaddress VARCHAR (45) CHARACTER SET ascii,' .
					'userid ' . ilya_get_mysql_user_column_type() . ',' .
					'handle VARCHAR(' . ILYA__DB_MAX_HANDLE_LENGTH . '),' .
					'cookieid BIGINT UNSIGNED,' .
					'event VARCHAR (20) CHARACTER SET ascii NOT NULL,' .
					'params VARCHAR (800) NOT NULL,' .
					'KEY datetime (datetime),' .
					'KEY ipaddress (ipaddress),' .
					'KEY userid (userid),' .
					'KEY event (event)' .
					') ENGINE=MyISAM DEFAULT CHARSET=utf8';
			} else {
				// table exists: check it has the correct schema
				$column = ilya_db_read_one_assoc(ilya_db_query_sub('SHOW COLUMNS FROM ^eventlog WHERE Field="ipaddress"'));
				if (strtolower($column['Type']) !== 'varchar(45)') {
					// upgrade to handle IPv6
					return 'ALTER TABLE ^eventlog MODIFY ipaddress VARCHAR(45) CHARACTER SET ascii';
				}
			}
		}

		return array();
	}


	public function admin_form(&$ilya_content)
	{
		// Process form input

		$saved = false;

		if (ilya_clicked('event_logger_save_button')) {
			ilya_opt('event_logger_to_database', (int)ilya_post_text('event_logger_to_database_field'));
			ilya_opt('event_logger_to_files', ilya_post_text('event_logger_to_files_field'));
			ilya_opt('event_logger_directory', ilya_post_text('event_logger_directory_field'));
			ilya_opt('event_logger_hide_header', !ilya_post_text('event_logger_hide_header_field'));

			$saved = true;
		}

		// Check the validity of the currently entered directory (if any)

		$directory = ilya_opt('event_logger_directory');

		$note = null;
		$error = null;

		if (!strlen($directory))
			$note = 'Please specify a directory that is writable by the web server.';
		elseif (!file_exists($directory))
			$error = 'This directory cannot be found. Please enter the full path.';
		elseif (!is_dir($directory))
			$error = 'This is a file. Please enter the full path of a directory.';
		elseif (!is_writable($directory))
			$error = 'This directory is not writable by the web server. Please choose a different directory, use chown/chmod to change permissions, or contact your web hosting company for assistance.';

		// Create the form for display

		ilya_set_display_rules($ilya_content, array(
			'event_logger_directory_display' => 'event_logger_to_files_field',
			'event_logger_hide_header_display' => 'event_logger_to_files_field',
		));

		return array(
			'ok' => ($saved && !isset($error)) ? 'Event log settings saved' : null,

			'fields' => array(
				array(
					'label' => 'Log events to <code>' . ILYA__MYSQL_TABLE_PREFIX . 'eventlog</code> database table',
					'tags' => 'name="event_logger_to_database_field"',
					'value' => ilya_opt('event_logger_to_database'),
					'type' => 'checkbox',
				),

				array(
					'label' => 'Log events to daily log files',
					'tags' => 'name="event_logger_to_files_field" id="event_logger_to_files_field"',
					'value' => ilya_opt('event_logger_to_files'),
					'type' => 'checkbox',
				),

				array(
					'id' => 'event_logger_directory_display',
					'label' => 'Directory for log files - enter full path:',
					'value' => ilya_html($directory),
					'tags' => 'name="event_logger_directory_field"',
					'note' => $note,
					'error' => ilya_html($error),
				),

				array(
					'id' => 'event_logger_hide_header_display',
					'label' => 'Include header lines at top of each log file',
					'type' => 'checkbox',
					'tags' => 'name="event_logger_hide_header_field"',
					'value' => !ilya_opt('event_logger_hide_header'),
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="event_logger_save_button"',
				),
			),
		);
	}


	public function value_to_text($value)
	{
		require_once ILYA__INCLUDE_DIR . 'util/string.php';

		if (is_array($value))
			$text = 'array(' . count($value) . ')';
		elseif (ilya_strlen($value) > 40)
			$text = ilya_substr($value, 0, 38) . '...';
		else
			$text = $value;

		return strtr($text, "\t\n\r", '   ');
	}


	public function process_event($event, $userid, $handle, $cookieid, $params)
	{
		if (ilya_opt('event_logger_to_database')) {
			$paramstring = '';

			foreach ($params as $key => $value) {
				$paramstring .= (strlen($paramstring) ? "\t" : '') . $key . '=' . $this->value_to_text($value);
			}

			ilya_db_query_sub(
				'INSERT INTO ^eventlog (datetime, ipaddress, userid, handle, cookieid, event, params) ' .
				'VALUES (NOW(), $, $, $, #, $, $)',
				ilya_remote_ip_address(), $userid, $handle, $cookieid, $event, $paramstring
			);
		}

		if (ilya_opt('event_logger_to_files')) {
			// Substitute some placeholders if certain information is missing
			if (!strlen($userid))
				$userid = 'no_userid';

			if (!strlen($handle))
				$handle = 'no_handle';

			if (!strlen($cookieid))
				$cookieid = 'no_cookieid';

			$ip = ilya_remote_ip_address();
			if (!strlen($ip))
				$ip = 'no_ipaddress';

			// Build the log file line to be written

			$fixedfields = array(
				'Date' => date('Y\-m\-d'),
				'Time' => date('H\:i\:s'),
				'IPaddress' => $ip,
				'UserID' => $userid,
				'Username' => $handle,
				'CookieID' => $cookieid,
				'Event' => $event,
			);

			$fields = $fixedfields;

			foreach ($params as $key => $value) {
				$fields['param_' . $key] = $key . '=' . $this->value_to_text($value);
			}

			$string = implode("\t", $fields);

			// Build the full path and file name

			$directory = ilya_opt('event_logger_directory');

			if (substr($directory, -1) != '/')
				$directory .= '/';

			$filename = $directory . 'ilya-log-' . date('Y\-m\-d') . '.txt';

			// Open, lock, write, unlock, close (to prevent interference between multiple writes)

			$exists = file_exists($filename);

			$file = @fopen($filename, 'a');

			if (is_resource($file)) {
				if (flock($file, LOCK_EX)) {
					if (!$exists && filesize($filename) === 0 && !ilya_opt('event_logger_hide_header')) {
						$string = "IlyaIdea " . ILYA__VERSION . " log file generated by Event Logger plugin.\n" .
							"This file is formatted as tab-delimited text with UTF-8 encoding.\n\n" .
							implode("\t", array_keys($fixedfields)) . "\textras...\n\n" . $string;
					}

					fwrite($file, $string . "\n");
					flock($file, LOCK_UN);
				}

				fclose($file);
			}
		}
	}
}
