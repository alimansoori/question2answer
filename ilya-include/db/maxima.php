<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Definitions that determine database column size and rows retrieved


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


$maximaDefaults = array(
	// Maximum column sizes - any of these can be defined in ilya-config.php to override the defaults below,
	// but you need to do so before creating the database, otherwise it's too late.
	'ILYA__DB_MAX_EMAIL_LENGTH' => 80,
	'ILYA__DB_MAX_HANDLE_LENGTH' => 20,
	'ILYA__DB_MAX_TITLE_LENGTH' => 800,
	'ILYA__DB_MAX_CONTENT_LENGTH' => 12000,
	'ILYA__DB_MAX_FORMAT_LENGTH' => 20,
	'ILYA__DB_MAX_TAGS_LENGTH' => 800,
	'ILYA__DB_MAX_NAME_LENGTH' => 40,
	'ILYA__DB_MAX_WORD_LENGTH' => 80,
	'ILYA__DB_MAX_CAT_PAGE_TITLE_LENGTH' => 80,
	'ILYA__DB_MAX_CAT_PAGE_TAGS_LENGTH' => 200,
	'ILYA__DB_MAX_CAT_CONTENT_LENGTH' => 800,
	'ILYA__DB_MAX_WIDGET_TAGS_LENGTH' => 800,
	'ILYA__DB_MAX_WIDGET_TITLE_LENGTH' => 80,
	'ILYA__DB_MAX_OPTION_TITLE_LENGTH' => 40,
	'ILYA__DB_MAX_PROFILE_TITLE_LENGTH' => 40,
	'ILYA__DB_MAX_PROFILE_CONTENT_LENGTH' => 8000,
	'ILYA__DB_MAX_CACHE_AGE' => 86400,
	'ILYA__DB_MAX_BLOB_FILE_NAME_LENGTH' => 255,
	'ILYA__DB_MAX_META_TITLE_LENGTH' => 40,
	'ILYA__DB_MAX_META_CONTENT_LENGTH' => 8000,

	// How many records to retrieve for different circumstances. In many cases we retrieve more records than we
	// end up needing to display once we know the value of an option. Wasteful, but allows one query per page.
	'ILYA__DB_RETRIEVE_QS_AS' => 50,
	'ILYA__DB_RETRIEVE_TAGS' => 200,
	'ILYA__DB_RETRIEVE_USERS' => 200,
	'ILYA__DB_RETRIEVE_ASK_TAG_QS' => 500,
	'ILYA__DB_RETRIEVE_COMPLETE_TAGS' => 1000,
	'ILYA__DB_RETRIEVE_MESSAGES' => 20,

	// Keep event streams trimmed - not worth storing too many events per question because we only display the
	// most recent event for each question, that has not been invalidated due to hiding/unselection/etc...
	'ILYA__DB_MAX_EVENTS_PER_Q' => 5,
);

foreach ($maximaDefaults as $key => $def) {
	if (!defined($key)) {
		define($key, $def);
	}
}
