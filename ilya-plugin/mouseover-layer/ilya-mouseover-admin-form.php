<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: ilya-plugin/mouseover-layer/ilya-mouseover-admin-form.php
	Description: Generic module class for mouseover layer plugin to provide admin form and default option


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

class ilya_mouseover_admin_form
{
	public function option_default($option)
	{
		if ($option === 'mouseover_content_max_len')
			return 480;
	}


	public function admin_form(&$ilya_content)
	{
		$saved = ilya_clicked('mouseover_save_button');

		if ($saved) {
			ilya_opt('mouseover_content_on', (int) ilya_post_text('mouseover_content_on_field'));
			ilya_opt('mouseover_content_max_len', (int) ilya_post_text('mouseover_content_max_len_field'));
		}

		ilya_set_display_rules($ilya_content, array(
			'mouseover_content_max_len_display' => 'mouseover_content_on_field',
		));

		return array(
			'ok' => $saved ? 'Mouseover settings saved' : null,

			'fields' => array(
				array(
					'label' => 'Show content preview on mouseover in question lists',
					'type' => 'checkbox',
					'value' => ilya_opt('mouseover_content_on'),
					'tags' => 'name="mouseover_content_on_field" id="mouseover_content_on_field"',
				),

				array(
					'id' => 'mouseover_content_max_len_display',
					'label' => 'Maximum length of preview:',
					'suffix' => 'characters',
					'type' => 'number',
					'value' => (int) ilya_opt('mouseover_content_max_len'),
					'tags' => 'name="mouseover_content_max_len_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="mouseover_save_button"',
				),
			),
		);
	}
}
