<?php
require_once ILYA__INCLUDE_DIR.'app/users.php';

class AppUsersTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test logic of permissions function.
	 * User level values: ILYA__USER_LEVEL_* in app/users.php [BASIC..SUPER]
	 * Permission values: ILYA__PERMIT_* in app/options.php [ALL..SUPERS]
	 * User flag values: ILYA__USER_FLAGS_* in app/users.php
	 */
	public function test__ilya_permit_value_error()
	{
		// set options cache to bypass database
		global $ilya_options_cache;
		$ilya_options_cache['confirm_user_emails'] = '1';
		$ilya_options_cache['moderate_users'] = '0';

		$userFlags = ILYA__USER_FLAGS_EMAIL_CONFIRMED;
		$blockedFlags = ILYA__USER_FLAGS_EMAIL_CONFIRMED | ILYA__USER_FLAGS_USER_BLOCKED;

		// Admin trying to do Super stuff
		$error = ilya_permit_value_error(ILYA__PERMIT_SUPERS, 1, ILYA__USER_LEVEL_ADMIN, $userFlags);
		$this->assertSame('level', $error);

		// Admin trying to do Admin stuff
		$error = ilya_permit_value_error(ILYA__PERMIT_ADMINS, 1, ILYA__USER_LEVEL_ADMIN, $userFlags);
		$this->assertSame(false, $error);

		// Admin trying to do Editor stuff
		$error = ilya_permit_value_error(ILYA__PERMIT_EDITORS, 1, ILYA__USER_LEVEL_ADMIN, $userFlags);
		$this->assertSame(false, $error);

		// Expert trying to do Moderator stuff
		$error = ilya_permit_value_error(ILYA__PERMIT_MODERATORS, 1, ILYA__USER_LEVEL_EXPERT, $userFlags);
		$this->assertSame('level', $error);

		// Unconfirmed User trying to do Confirmed stuff
		$error = ilya_permit_value_error(ILYA__PERMIT_CONFIRMED, 1, ILYA__USER_LEVEL_BASIC, 0);
		$this->assertSame('confirm', $error);

		// Blocked User trying to do anything
		$error = ilya_permit_value_error(ILYA__PERMIT_ALL, 1, ILYA__USER_LEVEL_BASIC, $blockedFlags);
		$this->assertSame('userblock', $error);

		// Logged Out User trying to do User stuff
		$error = ilya_permit_value_error(ILYA__PERMIT_USERS, null, null, 0);
		$this->assertSame('login', $error);

		// Logged Out User trying to do Moderator stuff
		$error = ilya_permit_value_error(ILYA__PERMIT_MODERATORS, null, null, 0);
		$this->assertSame('login', $error);
	}
}
