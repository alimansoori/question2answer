<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Widget module class for ask a question box


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

class ilya_ask_box
{
	public function allow_template($template)
	{
		$allowed = array(
			'activity', 'categories', 'custom', 'feedback', 'ilya', 'questions',
			'hot', 'search', 'tag', 'tags', 'unanswered',
		);
		return in_array($template, $allowed);
	}

	public function allow_region($region)
	{
		return in_array($region, array('main', 'side', 'full'));
	}

	public function output_widget($region, $place, $themeobject, $template, $request, $ilya_content)
	{
		if (isset($ilya_content['categoryids']))
			$params = array('cat' => end($ilya_content['categoryids']));
		else
			$params = null;

		?>
<div class="ilya-ask-box">
	<form method="post" action="<?php echo ilya_path_html('ask', $params); ?>">
		<table class="ilya-form-tall-table" style="width:100%">
			<tr style="vertical-align:middle;">
				<td class="ilya-form-tall-label" style="width: 1px; padding:8px; white-space:nowrap; <?php echo ($region=='side') ? 'padding-bottom:0;' : 'text-align:right;'?>">
					<?php echo strtr(ilya_lang_html('question/ask_title'), array(' ' => '&nbsp;'))?>:
				</td>
		<?php if ($region=='side') : ?>
			</tr>
			<tr>
		<?php endif; ?>
				<td class="ilya-form-tall-data" style="padding:8px;">
					<input name="title" type="text" class="ilya-form-tall-text" style="width:95%;">
				</td>
			</tr>
		</table>
		<input type="hidden" name="doask1" value="1">
	</form>
</div>
		<?php
	}
}
