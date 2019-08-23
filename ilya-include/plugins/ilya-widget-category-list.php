<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Widget module class for activity count plugin


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

class ilya_category_list
{
	private $themeobject;

	public function allow_template($template)
	{
		return true;
	}

	public function allow_region($region)
	{
		return $region == 'side';
	}

	public function output_widget($region, $place, $themeobject, $template, $request, $ilya_content)
	{
		$this->themeobject = $themeobject;

		if (isset($ilya_content['navigation']['cat'])) {
			$nav = $ilya_content['navigation']['cat'];
		} else {
			$selectspec = ilya_db_category_nav_selectspec(null, true, false, true);
			$selectspec['caching'] = array(
				'key' => 'ilya_db_category_nav_selectspec:default:full',
				'ttl' => ilya_opt('caching_catwidget_time'),
			);
			$navcategories = ilya_db_single_select($selectspec);
			$nav = ilya_category_navigation($navcategories);
		}

		$this->themeobject->output('<h2>' . ilya_lang_html('main/nav_categories') . '</h2>');
		$this->themeobject->set_context('nav_type', 'cat');
		$this->themeobject->nav_list($nav, 'nav-cat', 1);
		$this->themeobject->nav_clear('cat');
		$this->themeobject->clear_context('nav_type');
	}
}
