<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Event module for maintaining events tables


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

class ilya_event_updates
{
	public function process_event($event, $userid, $handle, $cookieid, $params)
	{
		if (@$params['silent']) // don't create updates about silent edits, and possibly other silent events in future
			return;

		require_once ILYA__INCLUDE_DIR . 'db/events.php';
		require_once ILYA__INCLUDE_DIR . 'app/events.php';

		switch ($event) {
			case 'q_post':
				if (isset($params['parent'])) // question is following an answer
					ilya_create_event_for_q_user($params['parent']['parentid'], $params['postid'], ILYA__UPDATE_FOLLOWS, $userid, $params['parent']['userid']);

				ilya_create_event_for_q_user($params['postid'], $params['postid'], null, $userid);
				ilya_create_event_for_tags($params['tags'], $params['postid'], null, $userid);
				ilya_create_event_for_category($params['categoryid'], $params['postid'], null, $userid);
				break;


			case 'a_post':
				ilya_create_event_for_q_user($params['parentid'], $params['postid'], null, $userid, $params['parent']['userid']);
				break;


			case 'c_post':
				$keyuserids = array();

				foreach ($params['thread'] as $comment) // previous comments in thread (but not author of parent again)
				{
					if (isset($comment['userid']))
						$keyuserids[$comment['userid']] = true;
				}

				foreach ($keyuserids as $keyuserid => $dummy) {
					if ($keyuserid != $userid)
						ilya_db_event_create_not_entity($keyuserid, $params['questionid'], $params['postid'], ILYA__UPDATE_FOLLOWS, $userid);
				}

				switch ($params['parent']['basetype']) {
					case 'Q':
						$updatetype = ILYA__UPDATE_C_FOR_Q;
						break;

					case 'A':
						$updatetype = ILYA__UPDATE_C_FOR_A;
						break;

					default:
						$updatetype = null;
						break;
				}

				// give precedence to 'your comment followed' rather than 'your Q/A commented' if both are true
				ilya_create_event_for_q_user($params['questionid'], $params['postid'], $updatetype, $userid,
					@$keyuserids[$params['parent']['userid']] ? null : $params['parent']['userid']);
				break;


			case 'q_edit':
				if ($params['titlechanged'] || $params['contentchanged'])
					$updatetype = ILYA__UPDATE_CONTENT;
				elseif ($params['tagschanged'])
					$updatetype = ILYA__UPDATE_TAGS;
				else
					$updatetype = null;

				if (isset($updatetype)) {
					ilya_create_event_for_q_user($params['postid'], $params['postid'], $updatetype, $userid, $params['oldquestion']['userid']);

					if ($params['tagschanged'])
						ilya_create_event_for_tags($params['tags'], $params['postid'], ILYA__UPDATE_TAGS, $userid);
				}
				break;


			case 'a_select':
				ilya_create_event_for_q_user($params['parentid'], $params['postid'], ILYA__UPDATE_SELECTED, $userid, $params['answer']['userid']);
				break;


			case 'q_reopen':
			case 'q_close':
				ilya_create_event_for_q_user($params['postid'], $params['postid'], ILYA__UPDATE_CLOSED, $userid, $params['oldquestion']['userid']);
				break;


			case 'q_hide':
				if (isset($params['oldquestion']['userid']))
					ilya_db_event_create_not_entity($params['oldquestion']['userid'], $params['postid'], $params['postid'], ILYA__UPDATE_VISIBLE, $userid);
				break;


			case 'q_reshow':
				ilya_create_event_for_q_user($params['postid'], $params['postid'], ILYA__UPDATE_VISIBLE, $userid, $params['oldquestion']['userid']);
				break;


			case 'q_move':
				ilya_create_event_for_q_user($params['postid'], $params['postid'], ILYA__UPDATE_CATEGORY, $userid, $params['oldquestion']['userid']);
				ilya_create_event_for_category($params['categoryid'], $params['postid'], ILYA__UPDATE_CATEGORY, $userid);
				break;


			case 'a_edit':
				if ($params['contentchanged'])
					ilya_create_event_for_q_user($params['parentid'], $params['postid'], ILYA__UPDATE_CONTENT, $userid, $params['oldanswer']['userid']);
				break;


			case 'a_hide':
				if (isset($params['oldanswer']['userid']))
					ilya_db_event_create_not_entity($params['oldanswer']['userid'], $params['parentid'], $params['postid'], ILYA__UPDATE_VISIBLE, $userid);
				break;


			case 'a_reshow':
				ilya_create_event_for_q_user($params['parentid'], $params['postid'], ILYA__UPDATE_VISIBLE, $userid, $params['oldanswer']['userid']);
				break;


			case 'c_edit':
				if ($params['contentchanged'])
					ilya_create_event_for_q_user($params['questionid'], $params['postid'], ILYA__UPDATE_CONTENT, $userid, $params['oldcomment']['userid']);
				break;


			case 'a_to_c':
				if ($params['contentchanged'])
					ilya_create_event_for_q_user($params['questionid'], $params['postid'], ILYA__UPDATE_CONTENT, $userid, $params['oldanswer']['userid']);
				else
					ilya_create_event_for_q_user($params['questionid'], $params['postid'], ILYA__UPDATE_TYPE, $userid, $params['oldanswer']['userid']);
				break;


			case 'c_hide':
				if (isset($params['oldcomment']['userid']))
					ilya_db_event_create_not_entity($params['oldcomment']['userid'], $params['questionid'], $params['postid'], ILYA__UPDATE_VISIBLE, $userid);
				break;


			case 'c_reshow':
				ilya_create_event_for_q_user($params['questionid'], $params['postid'], ILYA__UPDATE_VISIBLE, $userid, $params['oldcomment']['userid']);
				break;
		}
	}
}
