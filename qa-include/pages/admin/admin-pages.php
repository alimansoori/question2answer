<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Controller for admin page for editing custom pages and external links


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
require_once QA_INCLUDE_DIR . 'app/format.php';
require_once QA_INCLUDE_DIR . 'db/selects.php';


// Get current list of pages and determine the state of this admin page

$pageid = ilya_post_text('edit');
if (!isset($pageid))
	$pageid = ilya_get('edit');

list($pages, $editpage) = ilya_db_select_with_pending(
	ilya_db_pages_selectspec(),
	isset($pageid) ? ilya_db_page_full_selectspec($pageid, true) : null
);

if ((ilya_clicked('doaddpage') || ilya_clicked('doaddlink') || ilya_get('doaddlink') || ilya_clicked('dosavepage')) && !isset($editpage)) {
	$editpage = array('title' => ilya_get('text'), 'tags' => ilya_get('url'), 'nav' => ilya_get('nav'), 'position' => 1);
	$isexternal = ilya_clicked('doaddlink') || ilya_get('doaddlink') || ilya_post_text('external');

} elseif (isset($editpage))
	$isexternal = $editpage['flags'] & QA_PAGE_FLAGS_EXTERNAL;


// Check admin privileges (do late to allow one DB query)

if (!ilya_admin_check_privileges($ilya_content))
	return $ilya_content;


// Define an array of navigation settings we can change, option name => language key

$hascustomhome = ilya_has_custom_home();

$navoptions = array(
	'nav_home' => 'main/nav_home',
	'nav_activity' => 'main/nav_activity',
	$hascustomhome ? 'nav_ilya_not_home' : 'nav_ilya_is_home' => $hascustomhome ? 'main/nav_qa' : 'admin/nav_ilya_is_home',
	'nav_questions' => 'main/nav_qs',
	'nav_hot' => 'main/nav_hot',
	'nav_unanswered' => 'main/nav_unanswered',
	'nav_tags' => 'main/nav_tags',
	'nav_categories' => 'main/nav_categories',
	'nav_users' => 'main/nav_users',
	'nav_ask' => 'main/nav_ask',
);

$navpaths = array(
	'nav_home' => '',
	'nav_activity' => 'activity',
	'nav_ilya_not_home' => 'qa',
	'nav_ilya_is_home' => '',
	'nav_questions' => 'questions',
	'nav_hot' => 'hot',
	'nav_unanswered' => 'unanswered',
	'nav_tags' => 'tags',
	'nav_categories' => 'categories',
	'nav_users' => 'users',
	'nav_ask' => 'ask',
);

if (!ilya_opt('show_custom_home'))
	unset($navoptions['nav_home']);

if (!ilya_using_categories())
	unset($navoptions['nav_categories']);

if (!ilya_using_tags())
	unset($navoptions['nav_tags']);


// Process saving an old or new page

$securityexpired = false;

if (ilya_clicked('docancel'))
	$editpage = null;

elseif (ilya_clicked('dosaveoptions') || ilya_clicked('doaddpage') || ilya_clicked('doaddlink')) {
	if (!ilya_check_form_security_code('admin/pages', ilya_post_text('code')))
		$securityexpired = true;
	else foreach ($navoptions as $optionname => $langkey)
		ilya_set_option($optionname, (int)ilya_post_text('option_' . $optionname));

} elseif (ilya_clicked('dosavepage')) {
	require_once QA_INCLUDE_DIR . 'db/admin.php';
	require_once QA_INCLUDE_DIR . 'util/string.php';

	if (!ilya_check_form_security_code('admin/pages', ilya_post_text('code')))
		$securityexpired = true;
	else {
		$reloadpages = false;

		if (ilya_post_text('dodelete')) {
			ilya_db_page_delete($editpage['pageid']);

			$searchmodules = ilya_load_modules_with('search', 'unindex_page');
			foreach ($searchmodules as $searchmodule)
				$searchmodule->unindex_page($editpage['pageid']);

			$editpage = null;
			$reloadpages = true;

		} else {
			$inname = ilya_post_text('name');
			$inposition = ilya_post_text('position');
			$inpermit = (int)ilya_post_text('permit');
			$inurl = ilya_post_text('url');
			$innewwindow = ilya_post_text('newwindow');
			$inheading = ilya_post_text('heading');
			$incontent = ilya_post_text('content');

			$errors = array();

			// Verify the name (navigation link) is legitimate

			if (empty($inname))
				$errors['name'] = ilya_lang('main/field_required');
			elseif (ilya_strlen($inname) > QA_DB_MAX_CAT_PAGE_TITLE_LENGTH)
				$errors['name'] = ilya_lang_sub('main/max_length_x', QA_DB_MAX_CAT_PAGE_TITLE_LENGTH);

			if ($isexternal) {
				// Verify the url is legitimate (vaguely)

				if (empty($inurl))
					$errors['url'] = ilya_lang('main/field_required');
				elseif (ilya_strlen($inurl) > QA_DB_MAX_CAT_PAGE_TAGS_LENGTH)
					$errors['url'] = ilya_lang_sub('main/max_length_x', QA_DB_MAX_CAT_PAGE_TAGS_LENGTH);

			} else {
				// Verify the heading is legitimate

				if (ilya_strlen($inheading) > QA_DB_MAX_TITLE_LENGTH)
					$errors['heading'] = ilya_lang_sub('main/max_length_x', QA_DB_MAX_TITLE_LENGTH);

				// Verify the slug is legitimate (and try some defaults if we're creating a new page, and it's not)

				for ($attempt = 0; $attempt < 100; $attempt++) {
					switch ($attempt) {
						case 0:
							$inslug = ilya_post_text('slug');
							if (!isset($inslug))
								$inslug = implode('-', ilya_string_to_words($inname));
							break;

						case 1:
							$inslug = ilya_lang_sub('admin/page_default_slug', $inslug);
							break;

						default:
							$inslug = ilya_lang_sub('admin/page_default_slug', $attempt - 1);
							break;
					}

					list($matchcategoryid, $matchpage) = ilya_db_select_with_pending(
						ilya_db_slugs_to_category_id_selectspec($inslug),
						ilya_db_page_full_selectspec($inslug, false)
					);

					if (empty($inslug))
						$errors['slug'] = ilya_lang('main/field_required');
					elseif (ilya_strlen($inslug) > QA_DB_MAX_CAT_PAGE_TAGS_LENGTH)
						$errors['slug'] = ilya_lang_sub('main/max_length_x', QA_DB_MAX_CAT_PAGE_TAGS_LENGTH);
					elseif (preg_match('/[\\+\\/]/', $inslug))
						$errors['slug'] = ilya_lang_sub('admin/slug_bad_chars', '+ /');
					elseif (ilya_admin_is_slug_reserved($inslug))
						$errors['slug'] = ilya_lang('admin/slug_reserved');
					elseif (isset($matchpage) && ($matchpage['pageid'] != @$editpage['pageid']))
						$errors['slug'] = ilya_lang('admin/page_already_used');
					elseif (isset($matchcategoryid))
						$errors['slug'] = ilya_lang('admin/category_already_used');
					else
						unset($errors['slug']);

					if (isset($editpage['pageid']) || !isset($errors['slug'])) // don't try other options if editing existing page
						break;
				}
			}

			// Perform appropriate database action

			if (isset($editpage['pageid'])) { // changing existing page
				if ($isexternal) {
					ilya_db_page_set_fields($editpage['pageid'],
						isset($errors['name']) ? $editpage['title'] : $inname,
						QA_PAGE_FLAGS_EXTERNAL | ($innewwindow ? QA_PAGE_FLAGS_NEW_WINDOW : 0),
						isset($errors['url']) ? $editpage['tags'] : $inurl,
						null, null, $inpermit);

				} else {
					$setheading = isset($errors['heading']) ? $editpage['heading'] : $inheading;
					$setslug = isset($errors['slug']) ? $editpage['tags'] : $inslug;
					$setcontent = isset($errors['content']) ? $editpage['content'] : $incontent;

					ilya_db_page_set_fields($editpage['pageid'],
						isset($errors['name']) ? $editpage['title'] : $inname,
						0,
						$setslug, $setheading, $setcontent, $inpermit);

					$searchmodules = ilya_load_modules_with('search', 'unindex_page');
					foreach ($searchmodules as $searchmodule)
						$searchmodule->unindex_page($editpage['pageid']);

					$indextext = ilya_viewer_text($setcontent, 'html');

					$searchmodules = ilya_load_modules_with('search', 'index_page');
					foreach ($searchmodules as $searchmodule)
						$searchmodule->index_page($editpage['pageid'], $setslug, $setheading, $setcontent, 'html', $indextext);
				}

				ilya_db_page_move($editpage['pageid'], substr($inposition, 0, 1), substr($inposition, 1));

				$reloadpages = true;

				if (empty($errors))
					$editpage = null;
				else
					$editpage = @$pages[$editpage['pageid']];

			} else { // creating a new one
				if (empty($errors)) {
					if ($isexternal) {
						$pageid = ilya_db_page_create($inname, QA_PAGE_FLAGS_EXTERNAL | ($innewwindow ? QA_PAGE_FLAGS_NEW_WINDOW : 0), $inurl, null, null, $inpermit);
					} else {
						$pageid = ilya_db_page_create($inname, 0, $inslug, $inheading, $incontent, $inpermit);

						$indextext = ilya_viewer_text($incontent, 'html');

						$searchmodules = ilya_load_modules_with('search', 'index_page');
						foreach ($searchmodules as $searchmodule)
							$searchmodule->index_page($pageid, $inslug, $inheading, $incontent, 'html', $indextext);
					}

					ilya_db_page_move($pageid, substr($inposition, 0, 1), substr($inposition, 1));

					$editpage = null;
					$reloadpages = true;
				}
			}

			if (ilya_clicked('dosaveview') && empty($errors) && !$isexternal)
				ilya_redirect($inslug);
		}

		if ($reloadpages) {
			ilya_db_flush_pending_result('navpages');
			$pages = ilya_db_select_with_pending(ilya_db_pages_selectspec());
		}
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/admin_title') . ' - ' . ilya_lang_html('admin/pages_title');
$ilya_content['error'] = $securityexpired ? ilya_lang_html('admin/form_security_expired') : ilya_admin_page_error();

if (isset($editpage)) {
	$positionoptions = array();

	if (!$isexternal)
		$positionoptions['_' . max(1, @$editpage['position'])] = ilya_lang_html('admin/no_link');

	$navlangkey = array(
		'B' => 'admin/before_main_menu',
		'M' => 'admin/after_main_menu',
		'O' => 'admin/opposite_main_menu',
		'F' => 'admin/after_footer',
	);

	foreach ($navlangkey as $nav => $langkey) {
		$previous = null;
		$passedself = false;
		$maxposition = 0;

		foreach ($pages as $key => $page) {
			if ($page['nav'] == $nav) {
				if (isset($previous))
					$positionhtml = ilya_lang_html_sub('admin/after_x_tab', ilya_html($passedself ? $page['title'] : $previous['title']));
				else
					$positionhtml = ilya_lang_html($langkey);

				if ($page['pageid'] == @$editpage['pageid'])
					$passedself = true;

				$maxposition = max($maxposition, $page['position']);
				$positionoptions[$nav . $page['position']] = $positionhtml;

				$previous = $page;
			}
		}

		if (!isset($editpage['pageid']) || $nav != @$editpage['nav']) {
			$positionvalue = isset($previous) ? ilya_lang_html_sub('admin/after_x_tab', ilya_html($previous['title'])) : ilya_lang_html($langkey);
			$positionoptions[$nav . (isset($previous) ? (1 + $maxposition) : 1)] = $positionvalue;
		}
	}

	$positionvalue = @$positionoptions[$editpage['nav'] . $editpage['position']];

	$permitoptions = ilya_admin_permit_options(QA_PERMIT_ALL, QA_PERMIT_ADMINS, false, false);
	$permitvalue = @$permitoptions[isset($inpermit) ? $inpermit : $editpage['permit']];

	$ilya_content['form'] = array(
		'tags' => 'method="post" action="' . ilya_path_html(ilya_request()) . '"',

		'style' => 'tall',

		'fields' => array(
			'name' => array(
				'tags' => 'name="name" id="name"',
				'label' => ilya_lang_html($isexternal ? 'admin/link_name' : 'admin/page_name'),
				'value' => ilya_html(isset($inname) ? $inname : @$editpage['title']),
				'error' => ilya_html(@$errors['name']),
			),

			'delete' => array(
				'tags' => 'name="dodelete" id="dodelete"',
				'label' => ilya_lang_html($isexternal ? 'admin/delete_link' : 'admin/delete_page'),
				'value' => 0,
				'type' => 'checkbox',
			),

			'position' => array(
				'id' => 'position_display',
				'tags' => 'name="position"',
				'label' => ilya_lang_html('admin/position'),
				'type' => 'select',
				'options' => $positionoptions,
				'value' => $positionvalue,
			),

			'permit' => array(
				'id' => 'permit_display',
				'tags' => 'name="permit"',
				'label' => ilya_lang_html('admin/permit_to_view'),
				'type' => 'select',
				'options' => $permitoptions,
				'value' => $permitvalue,
			),

			'slug' => array(
				'id' => 'slug_display',
				'tags' => 'name="slug"',
				'label' => ilya_lang_html('admin/page_slug'),
				'value' => ilya_html(isset($inslug) ? $inslug : @$editpage['tags']),
				'error' => ilya_html(@$errors['slug']),
			),

			'url' => array(
				'id' => 'url_display',
				'tags' => 'name="url"',
				'label' => ilya_lang_html('admin/link_url'),
				'value' => ilya_html(isset($inurl) ? $inurl : @$editpage['tags']),
				'error' => ilya_html(@$errors['url']),
			),

			'newwindow' => array(
				'id' => 'newwindow_display',
				'tags' => 'name="newwindow"',
				'label' => ilya_lang_html('admin/link_new_window'),
				'value' => (isset($innewwindow) ? $innewwindow : (@$editpage['flags'] & QA_PAGE_FLAGS_NEW_WINDOW)) ? 1 : 0,
				'type' => 'checkbox',
			),

			'heading' => array(
				'id' => 'heading_display',
				'tags' => 'name="heading"',
				'label' => ilya_lang_html('admin/page_heading'),
				'value' => ilya_html(isset($inheading) ? $inheading : @$editpage['heading']),
				'error' => ilya_html(@$errors['heading']),
			),

			'content' => array(
				'id' => 'content_display',
				'tags' => 'name="content"',
				'label' => ilya_lang_html('admin/page_content_html'),
				'value' => ilya_html(isset($incontent) ? $incontent : @$editpage['content']),
				'error' => ilya_html(@$errors['content']),
				'rows' => 16,
			),
		),

		'buttons' => array(
			'save' => array(
				'label' => ilya_lang_html(isset($editpage['pageid']) ? 'main/save_button' : ($isexternal ? 'admin/add_link_button' : 'admin/add_page_button')),
			),

			'saveview' => array(
				'tags' => 'name="dosaveview"',
				'label' => ilya_lang_html('admin/save_view_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => ilya_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			'dosavepage' => '1', // for IE
			'edit' => @$editpage['pageid'],
			'external' => (int)$isexternal,
			'code' => ilya_get_form_security_code('admin/pages'),
		),
	);

	if ($isexternal) {
		unset($ilya_content['form']['fields']['slug']);
		unset($ilya_content['form']['fields']['heading']);
		unset($ilya_content['form']['fields']['content']);

	} else {
		unset($ilya_content['form']['fields']['url']);
		unset($ilya_content['form']['fields']['newwindow']);
	}

	if (isset($editpage['pageid'])) {
		ilya_set_display_rules($ilya_content, array(
			'position_display' => '!dodelete',
			'permit_display' => '!dodelete',
			($isexternal ? 'url_display' : 'slug_display') => '!dodelete',
			($isexternal ? 'newwindow_display' : 'heading_display') => '!dodelete',
			'content_display' => '!dodelete',
		));

	} else {
		unset($ilya_content['form']['fields']['slug']);
		unset($ilya_content['form']['fields']['delete']);
	}

	if ($isexternal || !isset($editpage['pageid']))
		unset($ilya_content['form']['buttons']['saveview']);

	$ilya_content['focusid'] = 'name';

} else {
	// List of standard navigation links
	$ilya_content['form'] = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'style' => 'tall',

		'fields' => array(),

		'buttons' => array(
			'save' => array(
				'tags' => 'name="dosaveoptions"',
				'label' => ilya_lang_html('main/save_button'),
			),

			'addpage' => array(
				'tags' => 'name="doaddpage"',
				'label' => ilya_lang_html('admin/add_page_button'),
			),

			'addlink' => array(
				'tags' => 'name="doaddlink"',
				'label' => ilya_lang_html('admin/add_link_button'),
			),
		),

		'hidden' => array(
			'code' => ilya_get_form_security_code('admin/pages'),
		),
	);

	$ilya_content['form']['fields']['navlinks'] = array(
		'label' => ilya_lang_html('admin/nav_links_explanation'),
		'type' => 'static',
		'tight' => true,
	);

	foreach ($navoptions as $optionname => $langkey) {
		$ilya_content['form']['fields'][$optionname] = array(
			'label' => '<a href="' . ilya_path_html($navpaths[$optionname]) . '">' . ilya_lang_html($langkey) . '</a>',
			'tags' => 'name="option_' . $optionname . '"',
			'type' => 'checkbox',
			'value' => ilya_opt($optionname),
		);
	}

	$ilya_content['form']['fields'][] = array(
		'type' => 'blank'
	);

	// List of suggested plugin pages

	$listhtml = '';

	$pagemodules = ilya_load_modules_with('page', 'suggest_requests');

	foreach ($pagemodules as $tryname => $trypage) {
		$suggestrequests = $trypage->suggest_requests();

		foreach ($suggestrequests as $suggestrequest) {
			$listhtml .= '<li><b><a href="' . ilya_path_html($suggestrequest['request']) . '">' . ilya_html($suggestrequest['title']) . '</a></b>';

			$listhtml .= ilya_lang_html_sub('admin/plugin_module', ilya_html($tryname));

			$listhtml .= strtr(ilya_lang_html('admin/add_link_link'), array(
				'^1' => '<a href="' . ilya_path_html(ilya_request(), array('doaddlink' => 1, 'text' => $suggestrequest['title'], 'url' => $suggestrequest['request'], 'nav' => @$suggestrequest['nav'])) . '">',
				'^2' => '</a>',
			));

			if (method_exists($trypage, 'admin_form'))
				$listhtml .= ' - <a href="' . ilya_admin_module_options_path('page', $tryname) . '">' . ilya_lang_html('admin/options') . '</a>';

			$listhtml .= '</li>';
		}
	}

	if (strlen($listhtml)) {
		$ilya_content['form']['fields']['plugins'] = array(
			'label' => ilya_lang_html('admin/plugin_pages_explanation'),
			'type' => 'custom',
			'html' => '<ul style="margin-bottom:0;">' . $listhtml . '</ul>',
		);
	}

	// List of custom pages or links

	$listhtml = '';

	foreach ($pages as $page) {
		$listhtml .= '<li><b><a href="' . ilya_custom_page_url($page) . '">' . ilya_html($page['title']) . '</a></b>';

		$listhtml .= strtr(ilya_lang_html(($page['flags'] & QA_PAGE_FLAGS_EXTERNAL) ? 'admin/edit_link' : 'admin/edit_page'), array(
			'^1' => '<a href="' . ilya_path_html('admin/pages', array('edit' => $page['pageid'])) . '">',
			'^2' => '</a>',
		));

		$listhtml .= '</li>';
	}

	$ilya_content['form']['fields']['pages'] = array(
		'label' => strlen($listhtml) ? ilya_lang_html('admin/click_name_edit') : ilya_lang_html('admin/pages_explanation'),
		'type' => 'custom',
		'html' => strlen($listhtml) ? '<ul style="margin-bottom:0;">' . $listhtml . '</ul>' : null,
	);
}

$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();


return $ilya_content;
