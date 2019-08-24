<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for admin page for editing categories


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

require_once ILYA_INCLUDE_DIR . 'app/admin.php';
require_once ILYA_INCLUDE_DIR . 'db/selects.php';
require_once ILYA_INCLUDE_DIR . 'db/admin.php';
require_once ILYA_INCLUDE_DIR . 'app/format.php';


// Get relevant list of categories

$editcategoryid = ilya_post_text('edit');
if (!isset($editcategoryid))
	$editcategoryid = ilya_get('edit');
if (!isset($editcategoryid))
	$editcategoryid = ilya_get('addsub');

$categories = ilya_db_select_with_pending(ilya_db_category_nav_selectspec($editcategoryid, true, false, true));


// Check admin privileges (do late to allow one DB query)

if (!ilya_admin_check_privileges($ilya_content))
	return $ilya_content;


// Work out the appropriate state for the page

$editcategory = @$categories[$editcategoryid];

if (isset($editcategory)) {
	$parentid = ilya_get('addsub');
	if (isset($parentid))
		$editcategory = array('parentid' => $parentid);

} else {
	if (ilya_clicked('doaddcategory'))
		$editcategory = array();

	elseif (ilya_clicked('dosavecategory')) {
		$parentid = ilya_post_text('parent');
		$editcategory = array('parentid' => strlen($parentid) ? $parentid : null);
	}
}

$setmissing = ilya_post_text('missing') || ilya_get('missing');

$setparent = !$setmissing && (ilya_post_text('setparent') || ilya_get('setparent')) && isset($editcategory['categoryid']);

$hassubcategory = false;
foreach ($categories as $category) {
	if (!strcmp($category['parentid'], $editcategoryid))
		$hassubcategory = true;
}


// Process saving options

$savedoptions = false;
$securityexpired = false;

if (ilya_clicked('dosaveoptions')) {
	if (!ilya_check_form_security_code('admin/categories', ilya_post_text('code')))
		$securityexpired = true;

	else {
		ilya_set_option('allow_no_category', (int)ilya_post_text('option_allow_no_category'));
		ilya_set_option('allow_no_sub_category', (int)ilya_post_text('option_allow_no_sub_category'));
		$savedoptions = true;
	}
}


// Process saving an old or new category

if (ilya_clicked('docancel')) {
	if ($setmissing || $setparent)
		ilya_redirect(ilya_request(), array('edit' => $editcategory['categoryid']));
	elseif (isset($editcategory['categoryid']))
		ilya_redirect(ilya_request());
	else
		ilya_redirect(ilya_request(), array('edit' => @$editcategory['parentid']));

} elseif (ilya_clicked('dosetmissing')) {
	if (!ilya_check_form_security_code('admin/categories', ilya_post_text('code')))
		$securityexpired = true;

	else {
		$inreassign = ilya_get_category_field_value('reassign');
		ilya_db_category_reassign($editcategory['categoryid'], $inreassign);
		ilya_redirect(ilya_request(), array('recalc' => 1, 'edit' => $editcategory['categoryid']));
	}

} elseif (ilya_clicked('dosavecategory')) {
	if (!ilya_check_form_security_code('admin/categories', ilya_post_text('code')))
		$securityexpired = true;

	elseif (ilya_post_text('dodelete')) {
		if (!$hassubcategory) {
			$inreassign = ilya_get_category_field_value('reassign');
			ilya_db_category_reassign($editcategory['categoryid'], $inreassign);
			ilya_db_category_delete($editcategory['categoryid']);
			ilya_redirect(ilya_request(), array('recalc' => 1, 'edit' => $editcategory['parentid']));
		}

	} else {
		require_once ILYA_INCLUDE_DIR . 'util/string.php';

		$inname = ilya_post_text('name');
		$incontent = ilya_post_text('content');
		$inparentid = $setparent ? ilya_get_category_field_value('parent') : $editcategory['parentid'];
		$inposition = ilya_post_text('position');
		$errors = array();

		// Check the parent ID

		$incategories = ilya_db_select_with_pending(ilya_db_category_nav_selectspec($inparentid, true));

		// Verify the name is legitimate for that parent ID

		if (empty($inname))
			$errors['name'] = ilya_lang('main/field_required');
		elseif (ilya_strlen($inname) > ILYA_DB_MAX_CAT_PAGE_TITLE_LENGTH)
			$errors['name'] = ilya_lang_sub('main/max_length_x', ILYA_DB_MAX_CAT_PAGE_TITLE_LENGTH);
		else {
			foreach ($incategories as $category) {
				if (!strcmp($category['parentid'], $inparentid) &&
					strcmp($category['categoryid'], @$editcategory['categoryid']) &&
					ilya_strtolower($category['title']) == ilya_strtolower($inname)
				) {
					$errors['name'] = ilya_lang('admin/category_already_used');
				}
			}
		}

		// Verify the slug is legitimate for that parent ID

		for ($attempt = 0; $attempt < 100; $attempt++) {
			switch ($attempt) {
				case 0:
					$inslug = ilya_post_text('slug');
					if (!isset($inslug))
						$inslug = implode('-', ilya_string_to_words($inname));
					break;

				case 1:
					$inslug = ilya_lang_sub('admin/category_default_slug', $inslug);
					break;

				default:
					$inslug = ilya_lang_sub('admin/category_default_slug', $attempt - 1);
					break;
			}

			$matchcategoryid = ilya_db_category_slug_to_id($inparentid, $inslug); // query against DB since MySQL ignores accents, etc...

			if (!isset($inparentid))
				$matchpage = ilya_db_single_select(ilya_db_page_full_selectspec($inslug, false));
			else
				$matchpage = null;

			if (empty($inslug))
				$errors['slug'] = ilya_lang('main/field_required');
			elseif (ilya_strlen($inslug) > ILYA_DB_MAX_CAT_PAGE_TAGS_LENGTH)
				$errors['slug'] = ilya_lang_sub('main/max_length_x', ILYA_DB_MAX_CAT_PAGE_TAGS_LENGTH);
			elseif (preg_match('/[\\+\\/]/', $inslug))
				$errors['slug'] = ilya_lang_sub('admin/slug_bad_chars', '+ /');
			elseif (!isset($inparentid) && ilya_admin_is_slug_reserved($inslug)) // only top level is a problem
				$errors['slug'] = ilya_lang('admin/slug_reserved');
			elseif (isset($matchcategoryid) && strcmp($matchcategoryid, @$editcategory['categoryid']))
				$errors['slug'] = ilya_lang('admin/category_already_used');
			elseif (isset($matchpage))
				$errors['slug'] = ilya_lang('admin/page_already_used');
			else
				unset($errors['slug']);

			if (isset($editcategory['categoryid']) || !isset($errors['slug'])) // don't try other options if editing existing category
				break;
		}

		// Perform appropriate database action

		if (empty($errors)) {
			if (isset($editcategory['categoryid'])) { // changing existing category
				ilya_db_category_rename($editcategory['categoryid'], $inname, $inslug);

				$recalc = false;

				if ($setparent) {
					ilya_db_category_set_parent($editcategory['categoryid'], $inparentid);
					$recalc = true;
				} else {
					ilya_db_category_set_content($editcategory['categoryid'], $incontent);
					ilya_db_category_set_position($editcategory['categoryid'], $inposition);
					$recalc = $hassubcategory && $inslug !== $editcategory['tags'];
				}

				ilya_redirect(ilya_request(), array('edit' => $editcategory['categoryid'], 'saved' => true, 'recalc' => (int)$recalc));

			} else { // creating a new one
				$categoryid = ilya_db_category_create($inparentid, $inname, $inslug);

				ilya_db_category_set_content($categoryid, $incontent);

				if (isset($inposition))
					ilya_db_category_set_position($categoryid, $inposition);

				ilya_redirect(ilya_request(), array('edit' => $inparentid, 'added' => true));
			}
		}
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html('admin/admin_title') . ' - ' . ilya_lang_html('admin/categories_title');
$ilya_content['error'] = $securityexpired ? ilya_lang_html('admin/form_security_expired') : ilya_admin_page_error();

if ($setmissing) {
	$ilya_content['form'] = array(
		'tags' => 'method="post" action="' . ilya_path_html(ilya_request()) . '"',

		'style' => 'tall',

		'fields' => array(
			'reassign' => array(
				'label' => isset($editcategory)
					? ilya_lang_html_sub('admin/category_no_sub_to', ilya_html($editcategory['title']))
					: ilya_lang_html('admin/category_none_to'),
				'loose' => true,
			),
		),

		'buttons' => array(
			'save' => array(
				'tags' => 'id="dosaveoptions"', // just used for ilya_recalc_click()
				'label' => ilya_lang_html('main/save_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => ilya_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			'dosetmissing' => '1', // for IE
			'edit' => @$editcategory['categoryid'],
			'missing' => '1',
			'code' => ilya_get_form_security_code('admin/categories'),
		),
	);

	ilya_set_up_category_field($ilya_content, $ilya_content['form']['fields']['reassign'], 'reassign',
		$categories, @$editcategory['categoryid'], ilya_opt('allow_no_category'), ilya_opt('allow_no_sub_category'));


} elseif (isset($editcategory)) {
	$ilya_content['form'] = array(
		'tags' => 'method="post" action="' . ilya_path_html(ilya_request()) . '"',

		'style' => 'tall',

		'ok' => ilya_get('saved') ? ilya_lang_html('admin/category_saved') : (ilya_get('added') ? ilya_lang_html('admin/category_added') : null),

		'fields' => array(
			'name' => array(
				'id' => 'name_display',
				'tags' => 'name="name" id="name"',
				'label' => ilya_lang_html(count($categories) ? 'admin/category_name' : 'admin/category_name_first'),
				'value' => ilya_html(isset($inname) ? $inname : @$editcategory['title']),
				'error' => ilya_html(@$errors['name']),
			),

			'questions' => array(),

			'delete' => array(),

			'reassign' => array(),

			'slug' => array(
				'id' => 'slug_display',
				'tags' => 'name="slug"',
				'label' => ilya_lang_html('admin/category_slug'),
				'value' => ilya_html(isset($inslug) ? $inslug : @$editcategory['tags']),
				'error' => ilya_html(@$errors['slug']),
			),

			'content' => array(
				'id' => 'content_display',
				'tags' => 'name="content"',
				'label' => ilya_lang_html('admin/category_description'),
				'value' => ilya_html(isset($incontent) ? $incontent : @$editcategory['content']),
				'error' => ilya_html(@$errors['content']),
				'rows' => 2,
			),
		),

		'buttons' => array(
			'save' => array(
				'tags' => 'id="dosaveoptions"', // just used for ilya_recalc_click
				'label' => ilya_lang_html(isset($editcategory['categoryid']) ? 'main/save_button' : 'admin/add_category_button'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => ilya_lang_html('main/cancel_button'),
			),
		),

		'hidden' => array(
			'dosavecategory' => '1', // for IE
			'edit' => @$editcategory['categoryid'],
			'parent' => @$editcategory['parentid'],
			'setparent' => (int)$setparent,
			'code' => ilya_get_form_security_code('admin/categories'),
		),
	);


	if ($setparent) {
		unset($ilya_content['form']['fields']['delete']);
		unset($ilya_content['form']['fields']['reassign']);
		unset($ilya_content['form']['fields']['questions']);
		unset($ilya_content['form']['fields']['content']);

		$ilya_content['form']['fields']['parent'] = array(
			'label' => ilya_lang_html('admin/category_parent'),
		);

		$childdepth = ilya_db_category_child_depth($editcategory['categoryid']);

		ilya_set_up_category_field($ilya_content, $ilya_content['form']['fields']['parent'], 'parent',
			isset($incategories) ? $incategories : $categories, isset($inparentid) ? $inparentid : @$editcategory['parentid'],
			true, true, ILYA_CATEGORY_DEPTH - 1 - $childdepth, @$editcategory['categoryid']);

		$ilya_content['form']['fields']['parent']['options'][''] = ilya_lang_html('admin/category_top_level');

		@$ilya_content['form']['fields']['parent']['note'] .= ilya_lang_html_sub('admin/category_max_depth_x', ILYA_CATEGORY_DEPTH);

	} elseif (isset($editcategory['categoryid'])) { // existing category
		if ($hassubcategory) {
			$ilya_content['form']['fields']['name']['note'] = ilya_lang_html('admin/category_no_delete_subs');
			unset($ilya_content['form']['fields']['delete']);
			unset($ilya_content['form']['fields']['reassign']);

		} else {
			$ilya_content['form']['fields']['delete'] = array(
				'tags' => 'name="dodelete" id="dodelete"',
				'label' =>
					'<span id="reassign_shown">' . ilya_lang_html('admin/delete_category_reassign') . '</span>' .
					'<span id="reassign_hidden" style="display:none;">' . ilya_lang_html('admin/delete_category') . '</span>',
				'value' => 0,
				'type' => 'checkbox',
			);

			$ilya_content['form']['fields']['reassign'] = array(
				'id' => 'reassign_display',
				'tags' => 'name="reassign"',
			);

			ilya_set_up_category_field($ilya_content, $ilya_content['form']['fields']['reassign'], 'reassign',
				$categories, $editcategory['parentid'], true, true, null, $editcategory['categoryid']);
		}

		$ilya_content['form']['fields']['questions'] = array(
			'label' => ilya_lang_html('admin/total_qs'),
			'type' => 'static',
			'value' => '<a href="' . ilya_path_html('questions/' . ilya_category_path_request($categories, $editcategory['categoryid'])) . '">' .
				($editcategory['qcount'] == 1
					? ilya_lang_html_sub('main/1_question', '1', '1')
					: ilya_lang_html_sub('main/x_questions', ilya_format_number($editcategory['qcount']))
				) . '</a>',
		);

		if ($hassubcategory && !ilya_opt('allow_no_sub_category')) {
			$nosubcount = ilya_db_count_categoryid_qs($editcategory['categoryid']);

			if ($nosubcount) {
				$ilya_content['form']['fields']['questions']['error'] =
					strtr(ilya_lang_html('admin/category_no_sub_error'), array(
						'^q' => ilya_format_number($nosubcount),
						'^1' => '<a href="' . ilya_path_html(ilya_request(), array('edit' => $editcategory['categoryid'], 'missing' => 1)) . '">',
						'^2' => '</a>',
					));
			}
		}

		ilya_set_display_rules($ilya_content, array(
			'position_display' => '!dodelete',
			'slug_display' => '!dodelete',
			'content_display' => '!dodelete',
			'parent_display' => '!dodelete',
			'children_display' => '!dodelete',
			'reassign_display' => 'dodelete',
			'reassign_shown' => 'dodelete',
			'reassign_hidden' => '!dodelete',
		));

	} else { // new category
		unset($ilya_content['form']['fields']['delete']);
		unset($ilya_content['form']['fields']['reassign']);
		unset($ilya_content['form']['fields']['slug']);
		unset($ilya_content['form']['fields']['questions']);

		$ilya_content['focusid'] = 'name';
	}

	if (!$setparent) {
		$pathhtml = ilya_category_path_html($categories, @$editcategory['parentid']);

		if (count($categories)) {
			$ilya_content['form']['fields']['parent'] = array(
				'id' => 'parent_display',
				'label' => ilya_lang_html('admin/category_parent'),
				'type' => 'static',
				'value' => (strlen($pathhtml) ? $pathhtml : ilya_lang_html('admin/category_top_level')),
			);

			$ilya_content['form']['fields']['parent']['value'] =
				'<a href="' . ilya_path_html(ilya_request(), array('edit' => @$editcategory['parentid'])) . '">' .
				$ilya_content['form']['fields']['parent']['value'] . '</a>';

			if (isset($editcategory['categoryid'])) {
				$ilya_content['form']['fields']['parent']['value'] .= ' - ' .
					'<a href="' . ilya_path_html(ilya_request(), array('edit' => $editcategory['categoryid'], 'setparent' => 1)) .
					'" style="white-space: nowrap;">' . ilya_lang_html('admin/category_move_parent') . '</a>';
			}
		}

		$positionoptions = array();

		$previous = null;
		$passedself = false;

		foreach ($categories as $key => $category) {
			if (!strcmp($category['parentid'], @$editcategory['parentid'])) {
				if (isset($previous))
					$positionhtml = ilya_lang_html_sub('admin/after_x', ilya_html($passedself ? $category['title'] : $previous['title']));
				else
					$positionhtml = ilya_lang_html('admin/first');

				$positionoptions[$category['position']] = $positionhtml;

				if (!strcmp($category['categoryid'], @$editcategory['categoryid']))
					$passedself = true;

				$previous = $category;
			}
		}

		if (isset($editcategory['position']))
			$positionvalue = $positionoptions[$editcategory['position']];

		else {
			$positionvalue = isset($previous) ? ilya_lang_html_sub('admin/after_x', ilya_html($previous['title'])) : ilya_lang_html('admin/first');
			$positionoptions[1 + @max(array_keys($positionoptions))] = $positionvalue;
		}

		$ilya_content['form']['fields']['position'] = array(
			'id' => 'position_display',
			'tags' => 'name="position"',
			'label' => ilya_lang_html('admin/position'),
			'type' => 'select',
			'options' => $positionoptions,
			'value' => $positionvalue,
		);

		if (isset($editcategory['categoryid'])) {
			$catdepth = count(ilya_category_path($categories, $editcategory['categoryid']));

			if ($catdepth < ILYA_CATEGORY_DEPTH) {
				$childrenhtml = '';

				foreach ($categories as $category) {
					if (!strcmp($category['parentid'], $editcategory['categoryid'])) {
						$childrenhtml .= (strlen($childrenhtml) ? ', ' : '') .
							'<a href="' . ilya_path_html(ilya_request(), array('edit' => $category['categoryid'])) . '">' . ilya_html($category['title']) . '</a>' .
							' (' . $category['qcount'] . ')';
					}
				}

				if (!strlen($childrenhtml))
					$childrenhtml = ilya_lang_html('admin/category_no_subs');

				$childrenhtml .= ' - <a href="' . ilya_path_html(ilya_request(), array('addsub' => $editcategory['categoryid'])) .
					'" style="white-space: nowrap;"><b>' . ilya_lang_html('admin/category_add_sub') . '</b></a>';

				$ilya_content['form']['fields']['children'] = array(
					'id' => 'children_display',
					'label' => ilya_lang_html('admin/category_subs'),
					'type' => 'static',
					'value' => $childrenhtml,
				);
			} else {
				$ilya_content['form']['fields']['name']['note'] = ilya_lang_html_sub('admin/category_no_add_subs_x', ILYA_CATEGORY_DEPTH);
			}

		}
	}

} else {
	$ilya_content['form'] = array(
		'tags' => 'method="post" action="' . ilya_path_html(ilya_request()) . '"',

		'ok' => $savedoptions ? ilya_lang_html('admin/options_saved') : null,

		'style' => 'tall',

		'fields' => array(
			'intro' => array(
				'label' => ilya_lang_html('admin/categories_introduction'),
				'type' => 'static',
			),
		),

		'buttons' => array(
			'save' => array(
				'tags' => 'name="dosaveoptions" id="dosaveoptions"',
				'label' => ilya_lang_html('main/save_button'),
			),

			'add' => array(
				'tags' => 'name="doaddcategory"',
				'label' => ilya_lang_html('admin/add_category_button'),
			),
		),

		'hidden' => array(
			'code' => ilya_get_form_security_code('admin/categories'),
		),
	);

	if (count($categories)) {
		unset($ilya_content['form']['fields']['intro']);

		$navcategoryhtml = '';

		foreach ($categories as $category) {
			if (!isset($category['parentid'])) {
				$navcategoryhtml .=
					'<a href="' . ilya_path_html('admin/categories', array('edit' => $category['categoryid'])) . '">' .
					ilya_html($category['title']) .
					'</a> - ' .
					($category['qcount'] == 1
						? ilya_lang_html_sub('main/1_question', '1', '1')
						: ilya_lang_html_sub('main/x_questions', ilya_format_number($category['qcount']))
					) . '<br/>';
			}
		}

		$ilya_content['form']['fields']['nav'] = array(
			'label' => ilya_lang_html('admin/top_level_categories'),
			'type' => 'static',
			'value' => $navcategoryhtml,
		);

		$ilya_content['form']['fields']['allow_no_category'] = array(
			'label' => ilya_lang_html('options/allow_no_category'),
			'tags' => 'name="option_allow_no_category"',
			'type' => 'checkbox',
			'value' => ilya_opt('allow_no_category'),
		);

		if (!ilya_opt('allow_no_category')) {
			$nocatcount = ilya_db_count_categoryid_qs(null);

			if ($nocatcount) {
				$ilya_content['form']['fields']['allow_no_category']['error'] =
					strtr(ilya_lang_html('admin/category_none_error'), array(
						'^q' => ilya_format_number($nocatcount),
						'^1' => '<a href="' . ilya_path_html(ilya_request(), array('missing' => 1)) . '">',
						'^2' => '</a>',
					));
			}
		}

		$ilya_content['form']['fields']['allow_no_sub_category'] = array(
			'label' => ilya_lang_html('options/allow_no_sub_category'),
			'tags' => 'name="option_allow_no_sub_category"',
			'type' => 'checkbox',
			'value' => ilya_opt('allow_no_sub_category'),
		);

	} else
		unset($ilya_content['form']['buttons']['save']);
}

if (ilya_get('recalc')) {
	$ilya_content['form']['ok'] = '<span id="recalc_ok">' . ilya_lang_html('admin/recalc_categories') . '</span>';
	$ilya_content['form']['hidden']['code_recalc'] = ilya_get_form_security_code('admin/recalc');

	$ilya_content['script_rel'][] = 'ilya-content/ilya-admin.js?' . ILYA_VERSION;
	$ilya_content['script_var']['ilya_warning_recalc'] = ilya_lang('admin/stop_recalc_warning');

	$ilya_content['script_onloads'][] = array(
		"ilya_recalc_click('dorecalccategories', document.getElementById('dosaveoptions'), null, 'recalc_ok');"
	);
}

$ilya_content['navigation']['sub'] = ilya_admin_sub_navigation();


return $ilya_content;
