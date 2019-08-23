<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Event module for sending notification emails


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

class ilya_event_notify
{
	public function process_event($event, $userid, $handle, $cookieid, $params)
	{
		require_once QA_INCLUDE_DIR . 'app/emails.php';
		require_once QA_INCLUDE_DIR . 'app/format.php';
		require_once QA_INCLUDE_DIR . 'util/string.php';


		switch ($event) {
			case 'q_post':
				$followanswer = @$params['followanswer'];
				$sendhandle = isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] : ilya_lang('main/anonymous'));

				if (isset($followanswer['notify']) && !ilya_post_is_by_user($followanswer, $userid, $cookieid)) {
					$blockwordspreg = ilya_get_block_words_preg();
					$sendtext = ilya_viewer_text($followanswer['content'], $followanswer['format'], array('blockwordspreg' => $blockwordspreg));

					ilya_send_notification($followanswer['userid'], $followanswer['notify'], @$followanswer['handle'], ilya_lang('emails/a_followed_subject'), ilya_lang('emails/a_followed_body'), array(
						'^q_handle' => $sendhandle,
						'^q_title' => ilya_block_words_replace($params['title'], $blockwordspreg),
						'^a_content' => $sendtext,
						'^url' => ilya_q_path($params['postid'], $params['title'], true),
					));
				}

				if (ilya_opt('notify_admin_q_post'))
					ilya_send_notification(null, ilya_opt('feedback_email'), null, ilya_lang('emails/q_posted_subject'), ilya_lang('emails/q_posted_body'), array(
						'^q_handle' => $sendhandle,
						'^q_title' => $params['title'], // don't censor title or content here since we want the admin to see bad words
						'^q_content' => $params['text'],
						'^url' => ilya_q_path($params['postid'], $params['title'], true),
					));

				break;


			case 'a_post':
				$question = $params['parent'];

				if (isset($question['notify']) && !ilya_post_is_by_user($question, $userid, $cookieid))
					ilya_send_notification($question['userid'], $question['notify'], @$question['handle'], ilya_lang('emails/q_answered_subject'), ilya_lang('emails/q_answered_body'), array(
						'^a_handle' => isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] : ilya_lang('main/anonymous')),
						'^q_title' => $question['title'],
						'^a_content' => ilya_block_words_replace($params['text'], ilya_get_block_words_preg()),
						'^url' => ilya_q_path($question['postid'], $question['title'], true, 'A', $params['postid']),
					));
				break;


			case 'c_post':
				$parent = $params['parent'];
				$question = $params['question'];

				$senttoemail = array(); // to ensure each user or email gets only one notification about an added comment
				$senttouserid = array();

				switch ($parent['basetype']) {
					case 'Q':
						$subject = ilya_lang('emails/q_commented_subject');
						$body = ilya_lang('emails/q_commented_body');
						$context = $parent['title'];
						break;

					case 'A':
						$subject = ilya_lang('emails/a_commented_subject');
						$body = ilya_lang('emails/a_commented_body');
						$context = ilya_viewer_text($parent['content'], $parent['format']);
						break;
				}

				$blockwordspreg = ilya_get_block_words_preg();
				$sendhandle = isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] : ilya_lang('main/anonymous'));
				$sendcontext = ilya_block_words_replace($context, $blockwordspreg);
				$sendtext = ilya_block_words_replace($params['text'], $blockwordspreg);
				$sendurl = ilya_q_path($question['postid'], $question['title'], true, 'C', $params['postid']);

				if (isset($parent['notify']) && !ilya_post_is_by_user($parent, $userid, $cookieid)) {
					$senduserid = $parent['userid'];
					$sendemail = @$parent['notify'];

					if (ilya_email_validate($sendemail))
						$senttoemail[$sendemail] = true;
					elseif (isset($senduserid))
						$senttouserid[$senduserid] = true;

					ilya_send_notification($senduserid, $sendemail, @$parent['handle'], $subject, $body, array(
						'^c_handle' => $sendhandle,
						'^c_context' => $sendcontext,
						'^c_content' => $sendtext,
						'^url' => $sendurl,
					));
				}

				foreach ($params['thread'] as $comment) {
					if (isset($comment['notify']) && !ilya_post_is_by_user($comment, $userid, $cookieid)) {
						$senduserid = $comment['userid'];
						$sendemail = @$comment['notify'];

						if (ilya_email_validate($sendemail)) {
							if (@$senttoemail[$sendemail])
								continue;

							$senttoemail[$sendemail] = true;

						} elseif (isset($senduserid)) {
							if (@$senttouserid[$senduserid])
								continue;

							$senttouserid[$senduserid] = true;
						}

						ilya_send_notification($senduserid, $sendemail, @$comment['handle'], ilya_lang('emails/c_commented_subject'), ilya_lang('emails/c_commented_body'), array(
							'^c_handle' => $sendhandle,
							'^c_context' => $sendcontext,
							'^c_content' => $sendtext,
							'^url' => $sendurl,
						));
					}
				}
				break;


			case 'q_queue':
			case 'q_requeue':
				if (ilya_opt('moderate_notify_admin')) {
					ilya_send_notification(null, ilya_opt('feedback_email'), null,
						($event == 'q_requeue') ? ilya_lang('emails/remoderate_subject') : ilya_lang('emails/moderate_subject'),
						($event == 'q_requeue') ? ilya_lang('emails/remoderate_body') : ilya_lang('emails/moderate_body'),
						array(
							'^p_handle' => isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] :
								(strlen(@$oldquestion['name']) ? $oldquestion['name'] : ilya_lang('main/anonymous'))),
							'^p_context' => trim(@$params['title'] . "\n\n" . $params['text']), // don't censor for admin
							'^url' => ilya_q_path($params['postid'], $params['title'], true),
							'^a_url' => ilya_path_absolute('admin/moderate'),
						)
					);
				}
				break;


			case 'a_queue':
			case 'a_requeue':
				if (ilya_opt('moderate_notify_admin')) {
					ilya_send_notification(null, ilya_opt('feedback_email'), null,
						($event == 'a_requeue') ? ilya_lang('emails/remoderate_subject') : ilya_lang('emails/moderate_subject'),
						($event == 'a_requeue') ? ilya_lang('emails/remoderate_body') : ilya_lang('emails/moderate_body'),
						array(
							'^p_handle' => isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] :
								(strlen(@$oldanswer['name']) ? $oldanswer['name'] : ilya_lang('main/anonymous'))),
							'^p_context' => $params['text'], // don't censor for admin
							'^url' => ilya_q_path($params['parentid'], $params['parent']['title'], true, 'A', $params['postid']),
							'^a_url' => ilya_path_absolute('admin/moderate'),
						)
					);
				}
				break;


			case 'c_queue':
			case 'c_requeue':
				if (ilya_opt('moderate_notify_admin')) {
					ilya_send_notification(null, ilya_opt('feedback_email'), null,
						($event == 'c_requeue') ? ilya_lang('emails/remoderate_subject') : ilya_lang('emails/moderate_subject'),
						($event == 'c_requeue') ? ilya_lang('emails/remoderate_body') : ilya_lang('emails/moderate_body'),
						array(
							'^p_handle' => isset($handle) ? $handle : (strlen($params['name']) ? $params['name'] :
								(strlen(@$oldcomment['name']) ? $oldcomment['name'] : // could also be after answer converted to comment
									(strlen(@$oldanswer['name']) ? $oldanswer['name'] : ilya_lang('main/anonymous')))),
							'^p_context' => $params['text'], // don't censor for admin
							'^url' => ilya_q_path($params['questionid'], $params['question']['title'], true, 'C', $params['postid']),
							'^a_url' => ilya_path_absolute('admin/moderate'),
						)
					);
				}
				break;


			case 'q_flag':
			case 'a_flag':
			case 'c_flag':
				$flagcount = $params['flagcount'];
				$oldpost = $params['oldpost'];
				$notifycount = $flagcount - ilya_opt('flagging_notify_first');

				if ($notifycount >= 0 && ($notifycount % ilya_opt('flagging_notify_every')) == 0) {
					ilya_send_notification(null, ilya_opt('feedback_email'), null, ilya_lang('emails/flagged_subject'), ilya_lang('emails/flagged_body'), array(
						'^p_handle' => isset($oldpost['handle']) ? $oldpost['handle'] :
							(strlen($oldpost['name']) ? $oldpost['name'] : ilya_lang('main/anonymous')),
						'^flags' => ($flagcount == 1) ? ilya_lang_html_sub('main/1_flag', '1', '1') : ilya_lang_html_sub('main/x_flags', $flagcount),
						'^p_context' => trim(@$oldpost['title'] . "\n\n" . ilya_viewer_text($oldpost['content'], $oldpost['format'])), // don't censor for admin
						'^url' => ilya_q_path($params['questionid'], $params['question']['title'], true, $oldpost['basetype'], $oldpost['postid']),
						'^a_url' => ilya_path_absolute('admin/flagged'),
					));
				}
				break;


			case 'a_select':
				$answer = $params['answer'];

				if (isset($answer['notify']) && !ilya_post_is_by_user($answer, $userid, $cookieid)) {
					$blockwordspreg = ilya_get_block_words_preg();
					$sendcontent = ilya_viewer_text($answer['content'], $answer['format'], array('blockwordspreg' => $blockwordspreg));

					ilya_send_notification($answer['userid'], $answer['notify'], @$answer['handle'], ilya_lang('emails/a_selected_subject'), ilya_lang('emails/a_selected_body'), array(
						'^s_handle' => isset($handle) ? $handle : ilya_lang('main/anonymous'),
						'^q_title' => ilya_block_words_replace($params['parent']['title'], $blockwordspreg),
						'^a_content' => $sendcontent,
						'^url' => ilya_q_path($params['parentid'], $params['parent']['title'], true, 'A', $params['postid']),
					));
				}
				break;


			case 'u_register':
				if (ilya_opt('register_notify_admin')) {
					ilya_send_notification(null, ilya_opt('feedback_email'), null, ilya_lang('emails/u_registered_subject'),
						ilya_opt('moderate_users') ? ilya_lang('emails/u_to_approve_body') : ilya_lang('emails/u_registered_body'), array(
							'^u_handle' => $handle,
							'^url' => ilya_path_absolute('user/' . $handle),
							'^a_url' => ilya_path_absolute('admin/approve'),
						));
				}
				break;


			case 'u_level':
				if ($params['level'] >= QA_USER_LEVEL_APPROVED && $params['oldlevel'] < QA_USER_LEVEL_APPROVED) {
					ilya_send_notification($params['userid'], null, $params['handle'], ilya_lang('emails/u_approved_subject'), ilya_lang('emails/u_approved_body'), array(
						'^url' => ilya_path_absolute('user/' . $params['handle']),
					));
				}
				break;


			case 'u_wall_post':
				if ($userid != $params['userid']) {
					$blockwordspreg = ilya_get_block_words_preg();

					ilya_send_notification($params['userid'], null, $params['handle'], ilya_lang('emails/wall_post_subject'), ilya_lang('emails/wall_post_body'), array(
						'^f_handle' => isset($handle) ? $handle : ilya_lang('main/anonymous'),
						'^post' => ilya_block_words_replace($params['text'], $blockwordspreg),
						'^url' => ilya_path_absolute('user/' . $params['handle'], null, 'wall'),
					));
				}
				break;
		}
	}
}
