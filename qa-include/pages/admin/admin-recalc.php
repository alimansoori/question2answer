<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Handles admin-triggered recalculations if JavaScript disabled


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
	header('Location: ../../../');
	exit;
}

require_once QA_INCLUDE_DIR . 'app/admin.php';
require_once QA_INCLUDE_DIR . 'app/recalc.php';


// Check we have administrative privileges

if (!ilya_admin_check_privileges($ilya_content))
	return $ilya_content;


// Find out the operation

$allowstates = array(
	'dorecountposts',
	'doreindexcontent',
	'dorecalcpoints',
	'dorefillevents',
	'dorecalccategories',
	'dodeletehidden',
	'doblobstodisk',
	'doblobstodb',
);

$recalcnow = false;

foreach ($allowstates as $allowstate) {
	if (ilya_post_text($allowstate) || ilya_get($allowstate)) {
		$state = $allowstate;
		$code = ilya_post_text('code');

		if (isset($code) && ilya_check_form_security_code('admin/recalc', $code))
			$recalcnow = true;
	}
}

if ($recalcnow) {
	?>

	<html>
		<head>
			<meta http-equiv="content-type" content="text/html; charset=utf-8">
		</head>
		<body>
			<code>

	<?php

	while ($state) {
		set_time_limit(60);

		$stoptime = time() + 2; // run in lumps of two seconds...

		while (ilya_recalc_perform_step($state) && time() < $stoptime)
			;

		echo ilya_html(ilya_recalc_get_message($state)) . str_repeat('    ', 1024) . "<br>\n";

		flush();
		sleep(1); // ... then rest for one
	}

	?>
			</code>

			<a href="<?php echo ilya_path_html('admin/stats')?>"><?php echo ilya_lang_html('admin/admin_title').' - '.ilya_lang_html('admin/stats_title')?></a>
		</body>
	</html>

	<?php
	ilya_exit();

} elseif (isset($state)) {
	$ilya_content = ilya_content_prepare();

	$ilya_content['title'] = ilya_lang_html('admin/admin_title');
	$ilya_content['error'] = ilya_lang_html('misc/form_security_again');

	$ilya_content['form'] = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'style' => 'wide',

		'buttons' => array(
			'recalc' => array(
				'tags' => 'name="' . ilya_html($state) . '"',
				'label' => ilya_lang_html('misc/form_security_again'),
			),
		),

		'hidden' => array(
			'code' => ilya_get_form_security_code('admin/recalc'),
		),
	);

	return $ilya_content;

} else {
	require_once QA_INCLUDE_DIR . 'app/format.php';

	$ilya_content = ilya_content_prepare();

	$ilya_content['title'] = ilya_lang_html('admin/admin_title');
	$ilya_content['error'] = ilya_lang_html('main/page_not_found');

	return $ilya_content;
}
