<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for admin page showing usage statistics and clean-up buttons


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

if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

require_once ILYA_INCLUDE_DIR . 'db/recalc.php';
require_once ILYA_INCLUDE_DIR . 'app/admin.php';
require_once ILYA_INCLUDE_DIR . 'db/admin.php';
require_once ILYA_INCLUDE_DIR . 'app/format.php';


// Check admin privileges (do late to allow one DB query)

if (!ilya_admin_check_privileges($ilya_content))
	return $ilya_content;


// Get the information to display

$qcount = (int)ilya_opt('cache_qcount');
$qcount_anon = ilya_db_count_posts('Q', false);
$qcount_unans = (int)ilya_opt('cache_unaqcount');

$acount = (int)ilya_opt('cache_acount');
$acount_anon = ilya_db_count_posts('A', false);

$ccount = (int)ilya_opt('cache_ccount');
$ccount_anon = ilya_db_count_posts('C', false);


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/admin_title') . ' - ' . ilya_lang_html('admin/stats_title');

$ilya_content['error'] = ilya_admin_page_error();

$ilya_content['form'] = array(
	'style' => 'wide',

	'fields' => array(
		'ilya_version' => array(
			'label' => ilya_lang_html('admin/ilya_version'),
			'value' => ilya_html(ILYA_VERSION),
		),

		'ilya_date' => array(
			'label' => ilya_lang_html('admin/ilya_build_date'),
			'value' => ilya_html(ILYA_BUILD_DATE),
		),

		'ilya_latest' => array(
			'label' => ilya_lang_html('admin/ilya_latest_version'),
			'type' => 'custom',
			'html' => '<span id="ilya-version">...</span>',
		),

		'break0' => array(
			'type' => 'blank',
		),

		'db_version' => array(
			'label' => ilya_lang_html('admin/ilya_db_version'),
			'value' => ilya_html(ilya_opt('db_version')),
		),

		'db_size' => array(
			'label' => ilya_lang_html('admin/ilya_db_size'),
			'value' => ilya_html(ilya_format_number(ilya_db_table_size() / 1048576, 1) . ' MB'),
		),

		'break1' => array(
			'type' => 'blank',
		),

		'php_version' => array(
			'label' => ilya_lang_html('admin/php_version'),
			'value' => ilya_html(phpversion()),
		),

		'mysql_version' => array(
			'label' => ilya_lang_html('admin/mysql_version'),
			'value' => ilya_html(ilya_db_mysql_version()),
		),

		'break2' => array(
			'type' => 'blank',
		),

		'qcount' => array(
			'label' => ilya_lang_html('admin/total_qs'),
			'value' => ilya_html(ilya_format_number($qcount)),
		),

		'qcount_unans' => array(
			'label' => ilya_lang_html('admin/total_qs_unans'),
			'value' => ilya_html(ilya_format_number($qcount_unans)),
		),

		'qcount_users' => array(
			'label' => ilya_lang_html('admin/from_users'),
			'value' => ilya_html(ilya_format_number($qcount - $qcount_anon)),
		),

		'qcount_anon' => array(
			'label' => ilya_lang_html('admin/from_anon'),
			'value' => ilya_html(ilya_format_number($qcount_anon)),
		),

		'break3' => array(
			'type' => 'blank',
		),

		'acount' => array(
			'label' => ilya_lang_html('admin/total_as'),
			'value' => ilya_html(ilya_format_number($acount)),
		),

		'acount_users' => array(
			'label' => ilya_lang_html('admin/from_users'),
			'value' => ilya_html(ilya_format_number($acount - $acount_anon)),
		),

		'acount_anon' => array(
			'label' => ilya_lang_html('admin/from_anon'),
			'value' => ilya_html(ilya_format_number($acount_anon)),
		),

		'break4' => array(
			'type' => 'blank',
		),

		'ccount' => array(
			'label' => ilya_lang_html('admin/total_cs'),
			'value' => ilya_html(ilya_format_number($ccount)),
		),

		'ccount_users' => array(
			'label' => ilya_lang_html('admin/from_users'),
			'value' => ilya_html(ilya_format_number($ccount - $ccount_anon)),
		),

		'ccount_anon' => array(
			'label' => ilya_lang_html('admin/from_anon'),
			'value' => ilya_html(ilya_format_number($ccount_anon)),
		),

		'break5' => array(
			'type' => 'blank',
		),

		'users' => array(
			'label' => ilya_lang_html('admin/users_registered'),
			'value' => ILYA_FINAL_EXTERNAL_USERS ? '' : ilya_html(ilya_format_number(ilya_db_count_users())),
		),

		'users_active' => array(
			'label' => ilya_lang_html('admin/users_active'),
			'value' => ilya_html(ilya_format_number((int)ilya_opt('cache_userpointscount'))),
		),

		'users_posted' => array(
			'label' => ilya_lang_html('admin/users_posted'),
			'value' => ilya_html(ilya_format_number(ilya_db_count_active_users('posts'))),
		),

		'users_voted' => array(
			'label' => ilya_lang_html('admin/users_voted'),
			'value' => ilya_html(ilya_format_number(ilya_db_count_active_users('uservotes'))),
		),
	),
);

if (ILYA_FINAL_EXTERNAL_USERS)
	unset($ilya_content['form']['fields']['users']);
else
	unset($ilya_content['form']['fields']['users_active']);

foreach ($ilya_content['form']['fields'] as $index => $field) {
	if (empty($field['type']))
		$ilya_content['form']['fields'][$index]['type'] = 'static';
}

$ilya_content['form_2'] = array(
	'tags' => 'method="post" action="' . ilya_path_html('admin/recalc') . '"',

	'title' => ilya_lang_html('admin/database_cleanup'),

	'style' => 'basic',

	'buttons' => array(
		'recount_posts' => array(
			'label' => ilya_lang_html('admin/recount_posts'),
			'tags' => 'name="dorecountposts" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/recount_posts_stop')) . ', \'recount_posts_note\');"',
			'note' => '<span id="recount_posts_note">' . ilya_lang_html('admin/recount_posts_note') . '</span>',
		),

		'reindex_content' => array(
			'label' => ilya_lang_html('admin/reindex_content'),
			'tags' => 'name="doreindexcontent" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/reindex_content_stop')) . ', \'reindex_content_note\');"',
			'note' => '<span id="reindex_content_note">' . ilya_lang_html('admin/reindex_content_note') . '</span>',
		),

		'recalc_points' => array(
			'label' => ilya_lang_html('admin/recalc_points'),
			'tags' => 'name="dorecalcpoints" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/recalc_stop')) . ', \'recalc_points_note\');"',
			'note' => '<span id="recalc_points_note">' . ilya_lang_html('admin/recalc_points_note') . '</span>',
		),

		'refill_events' => array(
			'label' => ilya_lang_html('admin/refill_events'),
			'tags' => 'name="dorefillevents" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/recalc_stop')) . ', \'refill_events_note\');"',
			'note' => '<span id="refill_events_note">' . ilya_lang_html('admin/refill_events_note') . '</span>',
		),

		'recalc_categories' => array(
			'label' => ilya_lang_html('admin/recalc_categories'),
			'tags' => 'name="dorecalccategories" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/recalc_stop')) . ', \'recalc_categories_note\');"',
			'note' => '<span id="recalc_categories_note">' . ilya_lang_html('admin/recalc_categories_note') . '</span>',
		),

		'delete_hidden' => array(
			'label' => ilya_lang_html('admin/delete_hidden'),
			'tags' => 'name="dodeletehidden" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/delete_stop')) . ', \'delete_hidden_note\');"',
			'note' => '<span id="delete_hidden_note">' . ilya_lang_html('admin/delete_hidden_note') . '</span>',
		),
	),

	'hidden' => array(
		'code' => ilya_get_form_security_code('admin/recalc'),
	),
);

if (!ilya_using_categories())
	unset($ilya_content['form_2']['buttons']['recalc_categories']);

if (defined('ILYA_BLOBS_DIRECTORY')) {
	if (ilya_db_has_blobs_in_db()) {
		$ilya_content['form_2']['buttons']['blobs_to_disk'] = array(
			'label' => ilya_lang_html('admin/blobs_to_disk'),
			'tags' => 'name="doblobstodisk" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/blobs_stop')) . ', \'blobs_to_disk_note\');"',
			'note' => '<span id="blobs_to_disk_note">' . ilya_lang_html('admin/blobs_to_disk_note') . '</span>',
		);
	}

	if (ilya_db_has_blobs_on_disk()) {
		$ilya_content['form_2']['buttons']['blobs_to_db'] = array(
			'label' => ilya_lang_html('admin/blobs_to_db'),
			'tags' => 'name="doblobstodb" onclick="return ilya_recalc_click(this.name, this, ' . ilya_js(ilya_lang_html('admin/blobs_stop')) . ', \'blobs_to_db_note\');"',
			'note' => '<span id="blobs_to_db_note">' . ilya_lang_html('admin/blobs_to_db_note') . '</span>',
		);
	}
}


$ilya_content['script_rel'][] = 'ilya-content/ilya-admin.js?' . ILYA_VERSION;
$ilya_content['script_var']['ilya_warning_recalc'] = ilya_lang('admin/stop_recalc_warning');

$ilya_content['script_onloads'][] = array(
	"ilya_version_check('https://raw.githubusercontent.com/ilya/question2answer/master/VERSION.txt', " . ilya_js(ilya_html(ILYA_VERSION), true) . ", 'ilya-version', true);"
);

$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();


return $ilya_content;
