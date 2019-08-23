<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Controller for user profile page, including wall


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

if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once ILYA__INCLUDE_DIR . 'db/selects.php';
require_once ILYA__INCLUDE_DIR . 'app/format.php';
require_once ILYA__INCLUDE_DIR . 'app/limits.php';
require_once ILYA__INCLUDE_DIR . 'app/updates.php';


// $handle, $userhtml are already set by /ilya-include/page/user.php - also $userid if using external user integration


// Redirect to 'My Account' page if button clicked

if (ilya_clicked('doaccount'))
	ilya_redirect('account');


// Find the user profile and questions and answers for this handle


$loginuserid = ilya_get_logged_in_userid();
$identifier = ILYA__FINAL_EXTERNAL_USERS ? $userid : $handle;

list($useraccount, $userprofile, $userfields, $usermessages, $userpoints, $userlevels, $navcategories, $userrank) =
	ilya_db_select_with_pending(
		ILYA__FINAL_EXTERNAL_USERS ? null : ilya_db_user_account_selectspec($handle, false),
		ILYA__FINAL_EXTERNAL_USERS ? null : ilya_db_user_profile_selectspec($handle, false),
		ILYA__FINAL_EXTERNAL_USERS ? null : ilya_db_userfields_selectspec(),
		ILYA__FINAL_EXTERNAL_USERS ? null : ilya_db_recent_messages_selectspec(null, null, $handle, false, ilya_opt_if_loaded('page_size_wall')),
		ilya_db_user_points_selectspec($identifier),
		ilya_db_user_levels_selectspec($identifier, ILYA__FINAL_EXTERNAL_USERS, true),
		ilya_db_category_nav_selectspec(null, true),
		ilya_db_user_rank_selectspec($identifier)
	);

if (!ILYA__FINAL_EXTERNAL_USERS && $handle !== ilya_get_logged_in_handle()) {
	foreach ($userfields as $index => $userfield) {
		if (isset($userfield['permit']) && ilya_permit_value_error($userfield['permit'], $loginuserid, ilya_get_logged_in_level(), ilya_get_logged_in_flags()))
			unset($userfields[$index]); // don't pay attention to user fields we're not allowed to view
	}
}


// Check the user exists and work out what can and can't be set (if not using single sign-on)

$errors = array();

$loginlevel = ilya_get_logged_in_level();

if (!ILYA__FINAL_EXTERNAL_USERS) { // if we're using integrated user management, we can know and show more
	require_once ILYA__INCLUDE_DIR . 'app/messages.php';

	if (!is_array($userpoints) && !is_array($useraccount))
		return include ILYA__INCLUDE_DIR . 'ilya-page-not-found.php';

	$userid = $useraccount['userid'];
	$fieldseditable = false;
	$maxlevelassign = null;

	$maxuserlevel = $useraccount['level'];
	foreach ($userlevels as $userlevel)
		$maxuserlevel = max($maxuserlevel, $userlevel['level']);

	if (isset($loginuserid) && $loginuserid != $userid &&
		($loginlevel >= ILYA__USER_LEVEL_SUPER || $loginlevel > $maxuserlevel) &&
		!ilya_user_permit_error()
	) { // can't change self - or someone on your level (or higher, obviously) unless you're a super admin
		if ($loginlevel >= ILYA__USER_LEVEL_SUPER)
			$maxlevelassign = ILYA__USER_LEVEL_SUPER;
		elseif ($loginlevel >= ILYA__USER_LEVEL_ADMIN)
			$maxlevelassign = ILYA__USER_LEVEL_MODERATOR;
		elseif ($loginlevel >= ILYA__USER_LEVEL_MODERATOR)
			$maxlevelassign = ILYA__USER_LEVEL_EXPERT;

		if ($loginlevel >= ILYA__USER_LEVEL_ADMIN)
			$fieldseditable = true;

		if (isset($maxlevelassign) && ($useraccount['flags'] & ILYA__USER_FLAGS_USER_BLOCKED))
			$maxlevelassign = min($maxlevelassign, ILYA__USER_LEVEL_EDITOR); // if blocked, can't promote too high
	}

	$approvebutton = isset($maxlevelassign)
		&& $useraccount['level'] < ILYA__USER_LEVEL_APPROVED
		&& $maxlevelassign >= ILYA__USER_LEVEL_APPROVED
		&& !($useraccount['flags'] & ILYA__USER_FLAGS_USER_BLOCKED)
		&& ilya_opt('moderate_users');
	$usereditbutton = $fieldseditable || isset($maxlevelassign);
	$userediting = $usereditbutton && (ilya_get_state() == 'edit');

	$wallposterrorhtml = ilya_wall_error_html($loginuserid, $useraccount['userid'], $useraccount['flags']);

	// This code is similar but not identical to that in to qq-page-user-wall.php

	$usermessages = array_slice($usermessages, 0, ilya_opt('page_size_wall'));
	$usermessages = ilya_wall_posts_add_rules($usermessages, 0);

	foreach ($usermessages as $message) {
		if ($message['deleteable'] && ilya_clicked('m' . $message['messageid'] . '_dodelete')) {
			if (!ilya_check_form_security_code('wall-' . $useraccount['handle'], ilya_post_text('code')))
				$errors['page'] = ilya_lang_html('misc/form_security_again');
			else {
				ilya_wall_delete_post($loginuserid, ilya_get_logged_in_handle(), ilya_cookie_get(), $message);
				ilya_redirect(ilya_request(), null, null, null, 'wall');
			}
		}
	}
}


// Process edit or save button for user, and other actions

if (!ILYA__FINAL_EXTERNAL_USERS) {
	$reloaduser = false;

	if ($usereditbutton) {
		if (ilya_clicked('docancel')) {
			ilya_redirect(ilya_request());
		} elseif (ilya_clicked('doedit')) {
			ilya_redirect(ilya_request(), array('state' => 'edit'));
		} elseif (ilya_clicked('dosave')) {
			require_once ILYA__INCLUDE_DIR . 'app/users-edit.php';
			require_once ILYA__INCLUDE_DIR . 'db/users.php';

			$inemail = ilya_post_text('email');

			$inprofile = array();
			foreach ($userfields as $userfield)
				$inprofile[$userfield['fieldid']] = ilya_post_text('field_' . $userfield['fieldid']);

			if (!ilya_check_form_security_code('user-edit-' . $handle, ilya_post_text('code'))) {
				$errors['page'] = ilya_lang_html('misc/form_security_again');
				$userediting = true;
			} else {
				if (ilya_post_text('removeavatar')) {
					ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_SHOW_AVATAR, false);
					ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_SHOW_GRAVATAR, false);

					if (isset($useraccount['avatarblobid'])) {
						require_once ILYA__INCLUDE_DIR . 'app/blobs.php';

						ilya_db_user_set($userid, array(
							'avatarblobid' => null,
							'avatarwidth' => null,
							'avatarheight' => null,
						));

						ilya_delete_blob($useraccount['avatarblobid']);
					}
				}

				if ($fieldseditable) {
					$filterhandle = $handle; // we're not filtering the handle...
					$errors = ilya_handle_email_filter($filterhandle, $inemail, $useraccount);
					unset($errors['handle']); // ...and we don't care about any errors in it

					if (!isset($errors['email'])) {
						if ($inemail != $useraccount['email']) {
							ilya_db_user_set($userid, 'email', $inemail);
							ilya_db_user_set_flag($userid, ILYA__USER_FLAGS_EMAIL_CONFIRMED, false);
						}
					}

					if (count($inprofile)) {
						$filtermodules = ilya_load_modules_with('filter', 'filter_profile');
						foreach ($filtermodules as $filtermodule)
							$filtermodule->filter_profile($inprofile, $errors, $useraccount, $userprofile);
					}

					foreach ($userfields as $userfield) {
						if (!isset($errors[$userfield['fieldid']]))
							ilya_db_user_profile_set($userid, $userfield['title'], $inprofile[$userfield['fieldid']]);
					}

					if (count($errors))
						$userediting = true;

					ilya_report_event('u_edit', $loginuserid, ilya_get_logged_in_handle(), ilya_cookie_get(), array(
						'userid' => $userid,
						'handle' => $useraccount['handle'],
					));
				}

				if (isset($maxlevelassign)) {
					$inlevel = min($maxlevelassign, (int)ilya_post_text('level')); // constrain based on maximum permitted to prevent simple browser-based attack
					if ($inlevel != $useraccount['level'])
						ilya_set_user_level($userid, $useraccount['handle'], $inlevel, $useraccount['level']);

					if (ilya_using_categories()) {
						$inuserlevels = array();

						for ($index = 1; $index <= 999; $index++) {
							$inlevel = ilya_post_text('uc_' . $index . '_level');
							if (!isset($inlevel))
								break;

							$categoryid = ilya_get_category_field_value('uc_' . $index . '_cat');

							if (strlen($categoryid) && strlen($inlevel)) {
								$inuserlevels[] = array(
									'entitytype' => ILYA__ENTITY_CATEGORY,
									'entityid' => $categoryid,
									'level' => min($maxlevelassign, (int)$inlevel),
								);
							}
						}

						ilya_db_user_levels_set($userid, $inuserlevels);
					}
				}

				if (empty($errors))
					ilya_redirect(ilya_request());

				list($useraccount, $userprofile, $userlevels) = ilya_db_select_with_pending(
					ilya_db_user_account_selectspec($userid, true),
					ilya_db_user_profile_selectspec($userid, true),
					ilya_db_user_levels_selectspec($userid, true, true)
				);
			}
		}
	}

	if (ilya_clicked('doapprove') || ilya_clicked('doblock') || ilya_clicked('dounblock') || ilya_clicked('dohideall') || ilya_clicked('dodelete')) {
		if (!ilya_check_form_security_code('user-' . $handle, ilya_post_text('code')))
			$errors['page'] = ilya_lang_html('misc/form_security_again');

		else {
			if ($approvebutton && ilya_clicked('doapprove')) {
				require_once ILYA__INCLUDE_DIR . 'app/users-edit.php';
				ilya_set_user_level($userid, $useraccount['handle'], ILYA__USER_LEVEL_APPROVED, $useraccount['level']);
				ilya_redirect(ilya_request());
			}

			if (isset($maxlevelassign) && ($maxuserlevel < ILYA__USER_LEVEL_MODERATOR)) {
				if (ilya_clicked('doblock')) {
					require_once ILYA__INCLUDE_DIR . 'app/users-edit.php';

					ilya_set_user_blocked($userid, $useraccount['handle'], true);
					ilya_redirect(ilya_request());
				}

				if (ilya_clicked('dounblock')) {
					require_once ILYA__INCLUDE_DIR . 'app/users-edit.php';

					ilya_set_user_blocked($userid, $useraccount['handle'], false);
					ilya_redirect(ilya_request());
				}

				if (ilya_clicked('dohideall') && !ilya_user_permit_error('permit_hide_show')) {
					require_once ILYA__INCLUDE_DIR . 'db/admin.php';
					require_once ILYA__INCLUDE_DIR . 'app/posts.php';

					$postids = ilya_db_get_user_visible_postids($userid);

					foreach ($postids as $postid)
						ilya_post_set_status($postid, ILYA__POST_STATUS_HIDDEN, $loginuserid);

					ilya_redirect(ilya_request());
				}

				if (ilya_clicked('dodelete') && ($loginlevel >= ILYA__USER_LEVEL_ADMIN)) {
					require_once ILYA__INCLUDE_DIR . 'app/users-edit.php';

					ilya_delete_user($userid);

					ilya_report_event('u_delete', $loginuserid, ilya_get_logged_in_handle(), ilya_cookie_get(), array(
						'userid' => $userid,
						'handle' => $useraccount['handle'],
					));

					ilya_redirect('users');
				}
			}
		}
	}


	if (ilya_clicked('dowallpost')) {
		$inmessage = ilya_post_text('message');

		if (!strlen($inmessage)) {
			$errors['message'] = ilya_lang('profile/post_wall_empty');
		} elseif (!ilya_check_form_security_code('wall-' . $useraccount['handle'], ilya_post_text('code'))) {
			$errors['message'] = ilya_lang_html('misc/form_security_again');
		} elseif (!$wallposterrorhtml) {
			ilya_wall_add_post($loginuserid, ilya_get_logged_in_handle(), ilya_cookie_get(), $userid, $useraccount['handle'], $inmessage, '');
			ilya_redirect(ilya_request(), null, null, null, 'wall');
		}
	}
}


// Process bonus setting button

if ($loginlevel >= ILYA__USER_LEVEL_ADMIN && ilya_clicked('dosetbonus')) {
	require_once ILYA__INCLUDE_DIR . 'db/points.php';

	$inbonus = (int)ilya_post_text('bonus');

	if (!ilya_check_form_security_code('user-activity-' . $handle, ilya_post_text('code'))) {
		$errors['page'] = ilya_lang_html('misc/form_security_again');
	} else {
		ilya_db_points_set_bonus($userid, $inbonus);
		ilya_db_points_update_ifuser($userid, null);
		ilya_redirect(ilya_request(), null, null, null, 'activity');
	}
}


// Prepare content for theme

$ilya_content = ilya_content_prepare();

$ilya_content['title'] = ilya_lang_html_sub('profile/user_x', $userhtml);
$ilya_content['error'] = @$errors['page'];

if (isset($loginuserid) && $loginuserid != $useraccount['userid'] && !ILYA__FINAL_EXTERNAL_USERS) {
	$favoritemap = ilya_get_favorite_non_qs_map();
	$favorite = @$favoritemap['user'][$useraccount['userid']];

	$ilya_content['favorite'] = ilya_favorite_form(ILYA__ENTITY_USER, $useraccount['userid'], $favorite,
		ilya_lang_sub($favorite ? 'main/remove_x_favorites' : 'users/add_user_x_favorites', $handle));
}


// General information about the user, only available if we're using internal user management

if (!ILYA__FINAL_EXTERNAL_USERS) {
	$membertime = ilya_time_to_string(ilya_opt('db_time') - $useraccount['created']);
	$joindate = ilya_when_to_html($useraccount['created'], 0);

	$ilya_content['form_profile'] = array(
		'tags' => 'method="post" action="' . ilya_self_html() . '"',

		'style' => 'wide',

		'fields' => array(
			'avatar' => array(
				'type' => 'image',
				'style' => 'tall',
				'label' => '',
				'html' => ilya_get_user_avatar_html($useraccount['flags'], $useraccount['email'], $useraccount['handle'],
					$useraccount['avatarblobid'], $useraccount['avatarwidth'], $useraccount['avatarheight'], ilya_opt('avatar_profile_size')),
				'id' => 'avatar',
			),

			'removeavatar' => null,

			'duration' => array(
				'type' => 'static',
				'label' => ilya_lang_html('users/member_for'),
				'value' => ilya_html($membertime . ' (' . ilya_lang_sub('main/since_x', $joindate['data']) . ')'),
				'id' => 'duration',
			),

			'level' => array(
				'type' => 'static',
				'label' => ilya_lang_html('users/member_type'),
				'tags' => 'name="level"',
				'value' => ilya_html(ilya_user_level_string($useraccount['level'])),
				'note' => (($useraccount['flags'] & ILYA__USER_FLAGS_USER_BLOCKED) && isset($maxlevelassign)) ? ilya_lang_html('users/user_blocked') : '',
				'id' => 'level',
			),
		),
	);

	if (empty($ilya_content['form_profile']['fields']['avatar']['html']))
		unset($ilya_content['form_profile']['fields']['avatar']);


	// Private message link

	if (ilya_opt('allow_private_messages') && isset($loginuserid) && $loginuserid != $userid && !($useraccount['flags'] & ILYA__USER_FLAGS_NO_MESSAGES) && !$userediting) {
		$ilya_content['form_profile']['fields']['level']['value'] .= strtr(ilya_lang_html('profile/send_private_message'), array(
			'^1' => '<a href="' . ilya_path_html('message/' . $handle) . '">',
			'^2' => '</a>',
		));
	}


	// Levels editing or viewing (add category-specific levels)

	if ($userediting) {
		if (isset($maxlevelassign)) {
			$ilya_content['form_profile']['fields']['level']['type'] = 'select';

			$showlevels = array(ILYA__USER_LEVEL_BASIC);
			if (ilya_opt('moderate_users'))
				$showlevels[] = ILYA__USER_LEVEL_APPROVED;

			array_push($showlevels, ILYA__USER_LEVEL_EXPERT, ILYA__USER_LEVEL_EDITOR, ILYA__USER_LEVEL_MODERATOR, ILYA__USER_LEVEL_ADMIN, ILYA__USER_LEVEL_SUPER);

			$leveloptions = array();
			$catleveloptions = array('' => ilya_lang_html('users/category_level_none'));

			foreach ($showlevels as $showlevel) {
				if ($showlevel <= $maxlevelassign) {
					$leveloptions[$showlevel] = ilya_html(ilya_user_level_string($showlevel));
					if ($showlevel > ILYA__USER_LEVEL_BASIC)
						$catleveloptions[$showlevel] = $leveloptions[$showlevel];
				}
			}

			$ilya_content['form_profile']['fields']['level']['options'] = $leveloptions;


			// Category-specific levels

			if (ilya_using_categories()) {
				$catleveladd = strlen(ilya_get('catleveladd')) > 0;

				if (!$catleveladd && !count($userlevels)) {
					$ilya_content['form_profile']['fields']['level']['suffix'] = strtr(ilya_lang_html('users/category_level_add'), array(
						'^1' => '<a href="' . ilya_path_html(ilya_request(), array('state' => 'edit', 'catleveladd' => 1)) . '">',
						'^2' => '</a>',
					));
				} else {
					$ilya_content['form_profile']['fields']['level']['suffix'] = ilya_lang_html('users/level_in_general');
				}

				if ($catleveladd || count($userlevels))
					$userlevels[] = array('entitytype' => ILYA__ENTITY_CATEGORY);

				$index = 0;
				foreach ($userlevels as $userlevel) {
					if ($userlevel['entitytype'] == ILYA__ENTITY_CATEGORY) {
						$index++;
						$id = 'ls_' . +$index;

						$ilya_content['form_profile']['fields']['uc_' . $index . '_level'] = array(
							'label' => ilya_lang_html('users/category_level_label'),
							'type' => 'select',
							'tags' => 'name="uc_' . $index . '_level" id="' . ilya_html($id) . '" onchange="this.ilya_prev=this.options[this.selectedIndex].value;"',
							'options' => $catleveloptions,
							'value' => isset($userlevel['level']) ? ilya_html(ilya_user_level_string($userlevel['level'])) : '',
							'suffix' => ilya_lang_html('users/category_level_in'),
						);

						$ilya_content['form_profile']['fields']['uc_' . $index . '_cat'] = array();

						if (isset($userlevel['entityid']))
							$fieldnavcategories = ilya_db_select_with_pending(ilya_db_category_nav_selectspec($userlevel['entityid'], true));
						else
							$fieldnavcategories = $navcategories;

						ilya_set_up_category_field($ilya_content, $ilya_content['form_profile']['fields']['uc_' . $index . '_cat'],
							'uc_' . $index . '_cat', $fieldnavcategories, @$userlevel['entityid'], true, true);

						unset($ilya_content['form_profile']['fields']['uc_' . $index . '_cat']['note']);
					}
				}

				$ilya_content['script_lines'][] = array(
					"function ilya_update_category_levels()",
					"{",
					"\tglob=document.getElementById('level_select');",
					"\tif (!glob)",
					"\t\treturn;",
					"\tvar opts=glob.options;",
					"\tvar lev=parseInt(opts[glob.selectedIndex].value);",
					"\tfor (var i=1; i<9999; i++) {",
					"\t\tvar sel=document.getElementById('ls_'+i);",
					"\t\tif (!sel)",
					"\t\t\tbreak;",
					"\t\tsel.ilya_prev=sel.ilya_prev || sel.options[sel.selectedIndex].value;",
					"\t\tsel.options.length=1;", // just leaves "no upgrade" element
					"\t\tfor (var j=0; j<opts.length; j++)",
					"\t\t\tif (parseInt(opts[j].value)>lev)",
					"\t\t\t\tsel.options[sel.options.length]=new Option(opts[j].text, opts[j].value, false, (opts[j].value==sel.ilya_prev));",
					"\t}",
					"}",
				);

				$ilya_content['script_onloads'][] = array(
					"ilya_update_category_levels();",
				);

				$ilya_content['form_profile']['fields']['level']['tags'] .= ' id="level_select" onchange="ilya_update_category_levels();"';
			}
		}

	} else {
		foreach ($userlevels as $userlevel) {
			if ($userlevel['entitytype'] == ILYA__ENTITY_CATEGORY && $userlevel['level'] > $useraccount['level']) {
				$ilya_content['form_profile']['fields']['level']['value'] .= '<br/>' .
					strtr(ilya_lang_html('users/level_for_category'), array(
						'^1' => ilya_html(ilya_user_level_string($userlevel['level'])),
						'^2' => '<a href="' . ilya_path_html(implode('/', array_reverse(explode('/', $userlevel['backpath'])))) . '">' . ilya_html($userlevel['title']) . '</a>',
					));
			}
		}
	}


	// Show any extra privileges due to user's level or their points

	$showpermits = array();
	$permitoptions = ilya_get_permit_options();

	foreach ($permitoptions as $permitoption) {
		// if not available to approved and email confirmed users with no points, but yes available to the user, it's something special
		if (ilya_permit_error($permitoption, $userid, ILYA__USER_LEVEL_APPROVED, ILYA__USER_FLAGS_EMAIL_CONFIRMED, 0) &&
			!ilya_permit_error($permitoption, $userid, $useraccount['level'], $useraccount['flags'], $userpoints['points'])
		) {
			if ($permitoption == 'permit_retag_cat')
				$showpermits[] = ilya_lang(ilya_using_categories() ? 'profile/permit_recat' : 'profile/permit_retag');
			else
				$showpermits[] = ilya_lang('profile/' . $permitoption); // then show it as an extra priviliege
		}
	}

	if (count($showpermits)) {
		$ilya_content['form_profile']['fields']['permits'] = array(
			'type' => 'static',
			'label' => ilya_lang_html('profile/extra_privileges'),
			'value' => ilya_html(implode("\n", $showpermits), true),
			'rows' => count($showpermits),
			'id' => 'permits',
		);
	}


	// Show email address only if we're an administrator

	if ($loginlevel >= ILYA__USER_LEVEL_ADMIN && !ilya_user_permit_error()) {
		$doconfirms = ilya_opt('confirm_user_emails') && $useraccount['level'] < ILYA__USER_LEVEL_EXPERT;
		$isconfirmed = ($useraccount['flags'] & ILYA__USER_FLAGS_EMAIL_CONFIRMED) > 0;
		$htmlemail = ilya_html(isset($inemail) ? $inemail : $useraccount['email']);

		$ilya_content['form_profile']['fields']['email'] = array(
			'type' => $userediting ? 'text' : 'static',
			'label' => ilya_lang_html('users/email_label'),
			'tags' => 'name="email"',
			'value' => $userediting ? $htmlemail : ('<a href="mailto:' . $htmlemail . '">' . $htmlemail . '</a>'),
			'error' => ilya_html(@$errors['email']),
			'note' => ($doconfirms ? (ilya_lang_html($isconfirmed ? 'users/email_confirmed' : 'users/email_not_confirmed') . ' ') : '') .
				($userediting ? '' : ilya_lang_html('users/only_shown_admins')),
			'id' => 'email',
		);
	}


	// Show IP addresses and times for last login or write - only if we're a moderator or higher

	if ($loginlevel >= ILYA__USER_LEVEL_MODERATOR && !ilya_user_permit_error()) {
		$ilya_content['form_profile']['fields']['lastlogin'] = array(
			'type' => 'static',
			'label' => ilya_lang_html('users/last_login_label'),
			'value' =>
				strtr(ilya_lang_html('users/x_ago_from_y'), array(
					'^1' => ilya_time_to_string(ilya_opt('db_time') - $useraccount['loggedin']),
					'^2' => ilya_ip_anchor_html(@inet_ntop($useraccount['loginip'])),
				)),
			'note' => $userediting ? null : ilya_lang_html('users/only_shown_moderators'),
			'id' => 'lastlogin',
		);

		if (isset($useraccount['written'])) {
			$ilya_content['form_profile']['fields']['lastwrite'] = array(
				'type' => 'static',
				'label' => ilya_lang_html('users/last_write_label'),
				'value' =>
					strtr(ilya_lang_html('users/x_ago_from_y'), array(
						'^1' => ilya_time_to_string(ilya_opt('db_time') - $useraccount['written']),
						'^2' => ilya_ip_anchor_html(@inet_ntop($useraccount['writeip'])),
					)),
				'note' => $userediting ? null : ilya_lang_html('users/only_shown_moderators'),
				'id' => 'lastwrite',
			);
		} else {
			unset($ilya_content['form_profile']['fields']['lastwrite']);
		}
	}


	// Show other profile fields

	$fieldsediting = $fieldseditable && $userediting;

	foreach ($userfields as $userfield) {
		if (($userfield['flags'] & ILYA__FIELD_FLAGS_LINK_URL) && !$fieldsediting) {
			$valuehtml = ilya_url_to_html_link(@$userprofile[$userfield['title']], ilya_opt('links_in_new_window'));
		} else {
			$value = @$inprofile[$userfield['fieldid']];
			if (!isset($value))
				$value = @$userprofile[$userfield['title']];

			$valuehtml = ilya_html($value, (($userfield['flags'] & ILYA__FIELD_FLAGS_MULTI_LINE) && !$fieldsediting));
		}

		$label = trim(ilya_user_userfield_label($userfield), ':');
		if (strlen($label))
			$label .= ':';

		$notehtml = null;
		if (isset($userfield['permit']) && !$userediting) {
			if ($userfield['permit'] <= ILYA__PERMIT_ADMINS)
				$notehtml = ilya_lang_html('users/only_shown_admins');
			elseif ($userfield['permit'] <= ILYA__PERMIT_MODERATORS)
				$notehtml = ilya_lang_html('users/only_shown_moderators');
			elseif ($userfield['permit'] <= ILYA__PERMIT_EDITORS)
				$notehtml = ilya_lang_html('users/only_shown_editors');
			elseif ($userfield['permit'] <= ILYA__PERMIT_EXPERTS)
				$notehtml = ilya_lang_html('users/only_shown_experts');
		}

		$ilya_content['form_profile']['fields'][$userfield['title']] = array(
			'type' => $fieldsediting ? 'text' : 'static',
			'label' => ilya_html($label),
			'tags' => 'name="field_' . $userfield['fieldid'] . '"',
			'value' => $valuehtml,
			'error' => ilya_html(@$errors[$userfield['fieldid']]),
			'note' => $notehtml,
			'rows' => ($userfield['flags'] & ILYA__FIELD_FLAGS_MULTI_LINE) ? 8 : null,
			'id' => 'userfield-' . $userfield['fieldid'],
		);
	}


	// Edit form or button, if appropriate

	if ($userediting) {
		if ((ilya_opt('avatar_allow_gravatar') && ($useraccount['flags'] & ILYA__USER_FLAGS_SHOW_GRAVATAR)) ||
			(ilya_opt('avatar_allow_upload') && ($useraccount['flags'] & ILYA__USER_FLAGS_SHOW_AVATAR) && isset($useraccount['avatarblobid']))
		) {
			$ilya_content['form_profile']['fields']['removeavatar'] = array(
				'type' => 'checkbox',
				'label' => ilya_lang_html('users/remove_avatar'),
				'tags' => 'name="removeavatar"',
			);
		}

		$ilya_content['form_profile']['buttons'] = array(
			'save' => array(
				'tags' => 'onclick="ilya_show_waiting_after(this, false);"',
				'label' => ilya_lang_html('users/save_user'),
			),

			'cancel' => array(
				'tags' => 'name="docancel"',
				'label' => ilya_lang_html('main/cancel_button'),
			),
		);

		$ilya_content['form_profile']['hidden'] = array(
			'dosave' => '1',
			'code' => ilya_get_form_security_code('user-edit-' . $handle),
		);

	} elseif ($usereditbutton) {
		$ilya_content['form_profile']['buttons'] = array();

		if ($approvebutton) {
			$ilya_content['form_profile']['buttons']['approve'] = array(
				'tags' => 'name="doapprove"',
				'label' => ilya_lang_html('users/approve_user_button'),
			);
		}

		$ilya_content['form_profile']['buttons']['edit'] = array(
			'tags' => 'name="doedit"',
			'label' => ilya_lang_html('users/edit_user_button'),
		);

		if (isset($maxlevelassign) && $useraccount['level'] < ILYA__USER_LEVEL_MODERATOR) {
			if ($useraccount['flags'] & ILYA__USER_FLAGS_USER_BLOCKED) {
				$ilya_content['form_profile']['buttons']['unblock'] = array(
					'tags' => 'name="dounblock"',
					'label' => ilya_lang_html('users/unblock_user_button'),
				);

				if (!ilya_user_permit_error('permit_hide_show')) {
					$ilya_content['form_profile']['buttons']['hideall'] = array(
						'tags' => 'name="dohideall" onclick="ilya_show_waiting_after(this, false);"',
						'label' => ilya_lang_html('users/hide_all_user_button'),
					);
				}

				if ($loginlevel >= ILYA__USER_LEVEL_ADMIN) {
					$ilya_content['form_profile']['buttons']['delete'] = array(
						'tags' => 'name="dodelete" onclick="ilya_show_waiting_after(this, false);"',
						'label' => ilya_lang_html('users/delete_user_button'),
					);
				}

			} else {
				$ilya_content['form_profile']['buttons']['block'] = array(
					'tags' => 'name="doblock"',
					'label' => ilya_lang_html('users/block_user_button'),
				);
			}

			$ilya_content['form_profile']['hidden'] = array(
				'code' => ilya_get_form_security_code('user-' . $handle),
			);
		}

	} elseif (isset($loginuserid) && ($loginuserid == $userid)) {
		$ilya_content['form_profile']['buttons'] = array(
			'account' => array(
				'tags' => 'name="doaccount"',
				'label' => ilya_lang_html('users/edit_profile'),
			),
		);
	}


	if (!is_array($ilya_content['form_profile']['fields']['removeavatar']))
		unset($ilya_content['form_profile']['fields']['removeavatar']);

	$ilya_content['raw']['account'] = $useraccount; // for plugin layers to access
	$ilya_content['raw']['profile'] = $userprofile;
}


// Information about user activity, available also with single sign-on integration

$ilya_content['form_activity'] = array(
	'title' => '<span id="activity">' . ilya_lang_html_sub('profile/activity_by_x', $userhtml) . '</span>',

	'style' => 'wide',

	'fields' => array(
		'bonus' => array(
			'label' => ilya_lang_html('profile/bonus_points'),
			'tags' => 'name="bonus"',
			'value' => ilya_html(isset($inbonus) ? $inbonus : $userpoints['bonus']),
			'type' => 'number',
			'note' => ilya_lang_html('users/only_shown_admins'),
			'id' => 'bonus',
		),

		'points' => array(
			'type' => 'static',
			'label' => ilya_lang_html('profile/score'),
			'value' => (@$userpoints['points'] == 1)
				? ilya_lang_html_sub('main/1_point', '<span class="ilya-uf-user-points">1</span>', '1')
				: ilya_lang_html_sub('main/x_points', '<span class="ilya-uf-user-points">' . ilya_html(ilya_format_number(@$userpoints['points'])) . '</span>'),
			'id' => 'points',
		),

		'title' => array(
			'type' => 'static',
			'label' => ilya_lang_html('profile/title'),
			'value' => ilya_get_points_title_html(@$userpoints['points'], ilya_get_points_to_titles()),
			'id' => 'title',
		),

		'questions' => array(
			'type' => 'static',
			'label' => ilya_lang_html('profile/questions'),
			'value' => '<span class="ilya-uf-user-q-posts">' . ilya_html(ilya_format_number(@$userpoints['qposts'])) . '</span>',
			'id' => 'questions',
		),

		'answers' => array(
			'type' => 'static',
			'label' => ilya_lang_html('profile/answers'),
			'value' => '<span class="ilya-uf-user-a-posts">' . ilya_html(ilya_format_number(@$userpoints['aposts'])) . '</span>',
			'id' => 'answers',
		),
	),
);

if ($loginlevel >= ILYA__USER_LEVEL_ADMIN) {
	$ilya_content['form_activity']['tags'] = 'method="post" action="' . ilya_self_html() . '"';

	$ilya_content['form_activity']['buttons'] = array(
		'setbonus' => array(
			'tags' => 'name="dosetbonus"',
			'label' => ilya_lang_html('profile/set_bonus_button'),
		),
	);

	$ilya_content['form_activity']['hidden'] = array(
		'code' => ilya_get_form_security_code('user-activity-' . $handle),
	);

} else {
	unset($ilya_content['form_activity']['fields']['bonus']);
}

if (!isset($ilya_content['form_activity']['fields']['title']['value']))
	unset($ilya_content['form_activity']['fields']['title']);

if (ilya_opt('comment_on_qs') || ilya_opt('comment_on_as')) { // only show comment count if comments are enabled
	$ilya_content['form_activity']['fields']['comments'] = array(
		'type' => 'static',
		'label' => ilya_lang_html('profile/comments'),
		'value' => '<span class="ilya-uf-user-c-posts">' . ilya_html(ilya_format_number(@$userpoints['cposts'])) . '</span>',
		'id' => 'comments',
	);
}

if (ilya_opt('voting_on_qs') || ilya_opt('voting_on_as')) { // only show vote record if voting is enabled
	$votedonvalue = '';

	if (ilya_opt('voting_on_qs')) {
		$qvotes = @$userpoints['qupvotes'] + @$userpoints['qdownvotes'];

		$innervalue = '<span class="ilya-uf-user-q-votes">' . ilya_format_number($qvotes) . '</span>';
		$votedonvalue .= ($qvotes == 1) ? ilya_lang_html_sub('main/1_question', $innervalue, '1')
			: ilya_lang_html_sub('main/x_questions', $innervalue);

		if (ilya_opt('voting_on_as'))
			$votedonvalue .= ', ';
	}

	if (ilya_opt('voting_on_as')) {
		$avotes = @$userpoints['aupvotes'] + @$userpoints['adownvotes'];

		$innervalue = '<span class="ilya-uf-user-a-votes">' . ilya_format_number($avotes) . '</span>';
		$votedonvalue .= ($avotes == 1) ? ilya_lang_html_sub('main/1_answer', $innervalue, '1')
			: ilya_lang_html_sub('main/x_answers', $innervalue);
	}

	$ilya_content['form_activity']['fields']['votedon'] = array(
		'type' => 'static',
		'label' => ilya_lang_html('profile/voted_on'),
		'value' => $votedonvalue,
		'id' => 'votedon',
	);

	$upvotes = @$userpoints['qupvotes'] + @$userpoints['aupvotes'];
	$innervalue = '<span class="ilya-uf-user-upvotes">' . ilya_format_number($upvotes) . '</span>';
	$votegavevalue = (($upvotes == 1) ? ilya_lang_html_sub('profile/1_up_vote', $innervalue, '1') : ilya_lang_html_sub('profile/x_up_votes', $innervalue)) . ', ';

	$downvotes = @$userpoints['qdownvotes'] + @$userpoints['adownvotes'];
	$innervalue = '<span class="ilya-uf-user-downvotes">' . ilya_format_number($downvotes) . '</span>';
	$votegavevalue .= ($downvotes == 1) ? ilya_lang_html_sub('profile/1_down_vote', $innervalue, '1') : ilya_lang_html_sub('profile/x_down_votes', $innervalue);

	$ilya_content['form_activity']['fields']['votegave'] = array(
		'type' => 'static',
		'label' => ilya_lang_html('profile/gave_out'),
		'value' => $votegavevalue,
		'id' => 'votegave',
	);

	$innervalue = '<span class="ilya-uf-user-upvoteds">' . ilya_format_number(@$userpoints['upvoteds']) . '</span>';
	$votegotvalue = ((@$userpoints['upvoteds'] == 1) ? ilya_lang_html_sub('profile/1_up_vote', $innervalue, '1')
			: ilya_lang_html_sub('profile/x_up_votes', $innervalue)) . ', ';

	$innervalue = '<span class="ilya-uf-user-downvoteds">' . ilya_format_number(@$userpoints['downvoteds']) . '</span>';
	$votegotvalue .= (@$userpoints['downvoteds'] == 1) ? ilya_lang_html_sub('profile/1_down_vote', $innervalue, '1')
		: ilya_lang_html_sub('profile/x_down_votes', $innervalue);

	$ilya_content['form_activity']['fields']['votegot'] = array(
		'type' => 'static',
		'label' => ilya_lang_html('profile/received'),
		'value' => $votegotvalue,
		'id' => 'votegot',
	);
}

if (@$userpoints['points']) {
	$ilya_content['form_activity']['fields']['points']['value'] .=
		ilya_lang_html_sub('profile/ranked_x', '<span class="ilya-uf-user-rank">' . ilya_format_number($userrank) . '</span>');
}

if (@$userpoints['aselects']) {
	$ilya_content['form_activity']['fields']['questions']['value'] .= ($userpoints['aselects'] == 1)
		? ilya_lang_html_sub('profile/1_with_best_chosen', '<span class="ilya-uf-user-q-selects">1</span>', '1')
		: ilya_lang_html_sub('profile/x_with_best_chosen', '<span class="ilya-uf-user-q-selects">' . ilya_format_number($userpoints['aselects']) . '</span>');
}

if (@$userpoints['aselecteds']) {
	$ilya_content['form_activity']['fields']['answers']['value'] .= ($userpoints['aselecteds'] == 1)
		? ilya_lang_html_sub('profile/1_chosen_as_best', '<span class="ilya-uf-user-a-selecteds">1</span>', '1')
		: ilya_lang_html_sub('profile/x_chosen_as_best', '<span class="ilya-uf-user-a-selecteds">' . ilya_format_number($userpoints['aselecteds']) . '</span>');
}


// For plugin layers to access

$ilya_content['raw']['userid'] = $userid;
$ilya_content['raw']['points'] = $userpoints;
$ilya_content['raw']['rank'] = $userrank;


// Wall posts

if (!ILYA__FINAL_EXTERNAL_USERS && ilya_opt('allow_user_walls')) {
	$ilya_content['message_list'] = array(
		'title' => '<span id="wall">' . ilya_lang_html_sub('profile/wall_for_x', $userhtml) . '</span>',

		'tags' => 'id="wallmessages"',

		'form' => array(
			'tags' => 'name="wallpost" method="post" action="' . ilya_self_html() . '#wall"',
			'style' => 'tall',
			'hidden' => array(
				'ilya_click' => '', // for simulating clicks in Javascript
				'handle' => ilya_html($useraccount['handle']),
				'start' => 0,
				'code' => ilya_get_form_security_code('wall-' . $useraccount['handle']),
			),
		),

		'messages' => array(),
	);

	if ($wallposterrorhtml) {
		$ilya_content['message_list']['error'] = $wallposterrorhtml; // an error that means we are not allowed to post
	} else {
		$ilya_content['message_list']['form']['fields'] = array(
			'message' => array(
				'tags' => 'name="message" id="message"',
				'value' => ilya_html(@$inmessage, false),
				'rows' => 2,
				'error' => ilya_html(@$errors['message']),
			),
		);

		$ilya_content['message_list']['form']['buttons'] = array(
			'post' => array(
				'tags' => 'name="dowallpost" onclick="return ilya_submit_wall_post(this, true);"',
				'label' => ilya_lang_html('profile/post_wall_button'),
			),
		);
	}

	foreach ($usermessages as $message)
		$ilya_content['message_list']['messages'][] = ilya_wall_post_view($message);

	if ($useraccount['wallposts'] > count($usermessages))
		$ilya_content['message_list']['messages'][] = ilya_wall_view_more_link($handle, count($usermessages));
}


// Sub menu for navigation in user pages

$ismyuser = isset($loginuserid) && $loginuserid == (ILYA__FINAL_EXTERNAL_USERS ? $userid : $useraccount['userid']);
$ilya_content['navigation']['sub'] = ilya_user_sub_navigation($handle, 'profile', $ismyuser);


return $ilya_content;
