<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Initialization for page requests


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

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../');
	exit;
}

require_once QA_INCLUDE_DIR . 'app/page.php';


// Below are the steps that actually execute for this file - all the above are function definitions

global $ilya_usage;

ilya_report_process_stage('init_page');
ilya_db_connect('ilya_page_db_fail_handler');
ilya_initialize_postdb_plugins();

ilya_page_queue_pending();
ilya_load_state();
ilya_check_login_modules();

if (QA_DEBUG_PERFORMANCE)
	$ilya_usage->mark('setup');

ilya_check_page_clicks();

$ilya_content = ilya_get_request_content();

if (is_array($ilya_content)) {
	if (QA_DEBUG_PERFORMANCE)
		$ilya_usage->mark('view');

	ilya_output_content($ilya_content);

	if (QA_DEBUG_PERFORMANCE)
		$ilya_usage->mark('theme');

	if (ilya_do_content_stats($ilya_content) && QA_DEBUG_PERFORMANCE)
		$ilya_usage->mark('stats');

	if (QA_DEBUG_PERFORMANCE)
		$ilya_usage->output();
}

ilya_db_disconnect();
