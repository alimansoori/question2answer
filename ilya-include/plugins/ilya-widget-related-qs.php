<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Widget module class for related questions


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

class ilya_related_qs
{
	public function allow_template($template)
	{
		return $template == 'question';
	}

	public function allow_region($region)
	{
		return in_array($region, array('side', 'main', 'full'));
	}

	public function output_widget($region, $place, $themeobject, $template, $request, $ilya_content)
	{
		require_once ILYA_INCLUDE_DIR . 'db/selects.php';

		if (!isset($ilya_content['q_view']['raw']['type']) || $ilya_content['q_view']['raw']['type'] != 'Q') // question might not be visible, etc...
			return;

		$questionid = $ilya_content['q_view']['raw']['postid'];

		$userid = ilya_get_logged_in_userid();
		$cookieid = ilya_cookie_get();

		$questions = ilya_db_single_select(ilya_db_related_qs_selectspec($userid, $questionid, ilya_opt('page_size_related_qs')));

		$minscore = ilya_match_to_min_score(ilya_opt('match_related_qs'));

		foreach ($questions as $key => $question) {
			if ($question['score'] < $minscore)
				unset($questions[$key]);
		}

		$titlehtml = ilya_lang_html(count($questions) ? 'main/related_qs_title' : 'main/no_related_qs_title');

		if ($region == 'side') {
			$themeobject->output(
				'<div class="ilya-related-qs">',
				'<h2 style="margin-top:0; padding-top:0;">',
				$titlehtml,
				'</h2>'
			);

			$themeobject->output('<ul class="ilya-related-q-list">');

			foreach ($questions as $question) {
				$themeobject->output(
					'<li class="ilya-related-q-item">' .
					'<a href="' . ilya_q_path_html($question['postid'], $question['title']) . '">' .
					ilya_html($question['title']) .
					'</a>' .
					'</li>'
				);
			}

			$themeobject->output(
				'</ul>',
				'</div>'
			);
		} else {
			$themeobject->output(
				'<h2>',
				$titlehtml,
				'</h2>'
			);

			$q_list = array(
				'form' => array(
					'tags' => 'method="post" action="' . ilya_self_html() . '"',
					'hidden' => array(
						'code' => ilya_get_form_security_code('vote'),
					),
				),
				'qs' => array(),
			);

			$defaults = ilya_post_html_defaults('Q');
			$usershtml = ilya_userids_handles_html($questions);

			foreach ($questions as $question) {
				$q_list['qs'][] = ilya_post_html_fields($question, $userid, $cookieid, $usershtml, null, ilya_post_html_options($question, $defaults));
			}

			$themeobject->q_list_and_form($q_list);
		}
	}
}
