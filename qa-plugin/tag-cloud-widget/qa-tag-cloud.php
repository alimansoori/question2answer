<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: ilya-plugin/tag-cloud-widget/ilya-tag-cloud.php
	Description: Widget module class for tag cloud plugin


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

class ilya_tag_cloud
{
	public function option_default($option)
	{
		switch ($option) {
			case 'tag_cloud_count_tags':
				return 100;
			case 'tag_cloud_font_size':
				return 24;
			case 'tag_cloud_minimal_font_size':
				return 10;
			case 'tag_cloud_size_popular':
				return true;
		}
	}


	public function admin_form()
	{
		$saved = ilya_clicked('tag_cloud_save_button');

		if ($saved) {
			ilya_opt('tag_cloud_count_tags', (int) ilya_post_text('tag_cloud_count_tags_field'));
			ilya_opt('tag_cloud_font_size', (int) ilya_post_text('tag_cloud_font_size_field'));
			ilya_opt('tag_cloud_minimal_font_size', (int) ilya_post_text('tag_cloud_minimal_font_size_field'));
			ilya_opt('tag_cloud_size_popular', (int) ilya_post_text('tag_cloud_size_popular_field'));
		}

		return array(
			'ok' => $saved ? 'Tag cloud settings saved' : null,

			'fields' => array(
				array(
					'label' => 'Maximum tags to show:',
					'type' => 'number',
					'value' => (int) ilya_opt('tag_cloud_count_tags'),
					'suffix' => 'tags',
					'tags' => 'name="tag_cloud_count_tags_field"',
				),

				array(
					'label' => 'Biggest font size:',
					'suffix' => 'pixels',
					'type' => 'number',
					'value' => (int) ilya_opt('tag_cloud_font_size'),
					'tags' => 'name="tag_cloud_font_size_field"',
				),

				array(
					'label' => 'Smallest allowed font size:',
					'suffix' => 'pixels',
					'type' => 'number',
					'value' => (int) ilya_opt('tag_cloud_minimal_font_size'),
					'tags' => 'name="tag_cloud_minimal_font_size_field"',
				),

				array(
					'label' => 'Font size represents tag popularity',
					'type' => 'checkbox',
					'value' => ilya_opt('tag_cloud_size_popular'),
					'tags' => 'name="tag_cloud_size_popular_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="tag_cloud_save_button"',
				),
			),
		);
	}


	public function allow_template($template)
	{
		$allowed = array(
			'activity', 'qa', 'questions', 'hot', 'ask', 'categories', 'question',
			'tag', 'tags', 'unanswered', 'user', 'users', 'search', 'admin', 'custom',
		);
		return in_array($template, $allowed);
	}


	public function allow_region($region)
	{
		return ($region === 'side');
	}


	public function output_widget($region, $place, $themeobject, $template, $request, $ilya_content)
	{
		require_once ILYA__INCLUDE_DIR.'db/selects.php';

		$populartags = ilya_db_single_select(ilya_db_popular_tags_selectspec(0, (int) ilya_opt('tag_cloud_count_tags')));

		$populartagslog = array_map(array($this, 'log_callback'), $populartags);

		$maxcount = reset($populartagslog);

		$themeobject->output(sprintf('<h2 style="margin-top: 0; padding-top: 0;">%s</h2>', ilya_lang_html('main/popular_tags')));

		$themeobject->output('<div style="font-size: 10px;">');

		$maxsize = ilya_opt('tag_cloud_font_size');
		$minsize = ilya_opt('tag_cloud_minimal_font_size');
		$scale = ilya_opt('tag_cloud_size_popular');
		$blockwordspreg = ilya_get_block_words_preg();

		foreach ($populartagslog as $tag => $count) {
			$matches = ilya_block_words_match_all($tag, $blockwordspreg);
			if (!empty($matches)) {
				continue;
			}

			if ($scale) {
				$size = number_format($maxsize * $count / $maxcount, 1);
				if ($size < $minsize) {
					$size = $minsize;
				}
			} else {
				$size = $maxsize;
			}

			$themeobject->output(sprintf('<a href="%s" style="font-size: %dpx; vertical-align: baseline;">%s</a>', ilya_path_html('tag/' . $tag), $size, ilya_html($tag)));
		}

		$themeobject->output('</div>');
	}

	private function log_callback($e)
	{
		return log($e) + 1;
	}
}
