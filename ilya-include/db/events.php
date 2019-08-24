<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Database-level access to userevents and sharedevents tables


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

if (!defined('ILYA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


/**
 * Add an event to the event streams for entity $entitytype with $entityid. The event of type $updatetype relates to
 * $lastpostid whose antecedent question is $questionid, and was caused by $lastuserid. Pass a unix $timestamp for the
 * event time or leave as null to use now. This will add the event both to the entity's shared stream, and the
 * individual user streams for any users following the entity not via its shared stream (See long comment in
 * /ilya-include/db/favorites.php). Also handles truncation.
 * @param $entitytype
 * @param $entityid
 * @param $questionid
 * @param $lastpostid
 * @param $updatetype
 * @param $lastuserid
 * @param $timestamp
 */
function ilya_db_event_create_for_entity($entitytype, $entityid, $questionid, $lastpostid, $updatetype, $lastuserid, $timestamp = null)
{
	require_once ILYA_INCLUDE_DIR . 'db/maxima.php';
	require_once ILYA_INCLUDE_DIR . 'app/updates.php';

	$updatedsql = isset($timestamp) ? ('FROM_UNIXTIME(' . ilya_db_argument_to_mysql($timestamp, false) . ')') : 'NOW()';

	// Enter it into the appropriate shared event stream for that entity

	ilya_db_query_sub(
		'INSERT INTO ^sharedevents (entitytype, entityid, questionid, lastpostid, updatetype, lastuserid, updated) ' .
		'VALUES ($, #, #, #, $, $, ' . $updatedsql . ')',
		$entitytype, $entityid, $questionid, $lastpostid, $updatetype, $lastuserid
	);

	// If this is for a question entity, check the shared event stream doesn't have too many entries for that question

	$questiontruncated = false;

	if ($entitytype == ILYA_ENTITY_QUESTION) {
		$truncate = ilya_db_read_one_value(ilya_db_query_sub(
			'SELECT updated FROM ^sharedevents WHERE entitytype=$ AND entityid=# AND questionid=# ORDER BY updated DESC LIMIT #,1',
			$entitytype, $entityid, $questionid, ILYA_DB_MAX_EVENTS_PER_Q
		), true);

		if (isset($truncate)) {
			ilya_db_query_sub(
				'DELETE FROM ^sharedevents WHERE entitytype=$ AND entityid=# AND questionid=# AND updated<=$',
				$entitytype, $entityid, $questionid, $truncate
			);

			$questiontruncated = true;
		}
	}

	// If we didn't truncate due to a specific question, truncate the shared event stream for its overall length

	if (!$questiontruncated) {
		$truncate = ilya_db_read_one_value(ilya_db_query_sub(
			'SELECT updated FROM ^sharedevents WHERE entitytype=$ AND entityid=$ ORDER BY updated DESC LIMIT #,1',
			$entitytype, $entityid, (int)ilya_opt('max_store_user_updates')
		), true);

		if (isset($truncate))
			ilya_db_query_sub(
				'DELETE FROM ^sharedevents WHERE entitytype=$ AND entityid=$ AND updated<=$',
				$entitytype, $entityid, $truncate
			);
	}

	// See if we can identify a user who has favorited this entity, but is not using its shared event stream

	$randomuserid = ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT userid FROM ^userfavorites WHERE entitytype=$ AND entityid=# AND nouserevents=0 ORDER BY RAND() LIMIT 1',
		$entitytype, $entityid
	), true);

	if (isset($randomuserid)) {
		// If one was found, this means we have one or more individual event streams, so update them all
		ilya_db_query_sub(
			'INSERT INTO ^userevents (userid, entitytype, entityid, questionid, lastpostid, updatetype, lastuserid, updated) ' .
			'SELECT userid, $, #, #, #, $, $, ' . $updatedsql . ' FROM ^userfavorites WHERE entitytype=$ AND entityid=# AND nouserevents=0',
			$entitytype, $entityid, $questionid, $lastpostid, $updatetype, $lastuserid, $entitytype, $entityid
		);

		// Now truncate the random individual event stream that was found earlier
		// (in theory we should truncate them all, but truncation is just a 'housekeeping' activity, so it's not necessary)
		ilya_db_user_events_truncate($randomuserid, $questionid);
	}
}


/**
 * Add an event to the event stream for $userid which is not related to an entity they are following (but rather a
 * notification which is relevant for them, e.g. if someone answers their question). The event of type $updatetype
 * relates to $lastpostid whose antecedent question is $questionid, and was caused by $lastuserid. Pass a unix
 * $timestamp for the event time or leave as null to use now. Also handles truncation of event streams.
 * @param $userid
 * @param $questionid
 * @param $lastpostid
 * @param $updatetype
 * @param $lastuserid
 * @param $timestamp
 */
function ilya_db_event_create_not_entity($userid, $questionid, $lastpostid, $updatetype, $lastuserid, $timestamp = null)
{
	require_once ILYA_INCLUDE_DIR . 'app/updates.php';

	$updatedsql = isset($timestamp) ? ('FROM_UNIXTIME(' . ilya_db_argument_to_mysql($timestamp, false) . ')') : 'NOW()';

	ilya_db_query_sub(
		"INSERT INTO ^userevents (userid, entitytype, entityid, questionid, lastpostid, updatetype, lastuserid, updated) " .
		"VALUES ($, $, 0, #, #, $, $, " . $updatedsql . ")",
		$userid, ILYA_ENTITY_NONE, $questionid, $lastpostid, $updatetype, $lastuserid
	);

	ilya_db_user_events_truncate($userid, $questionid);
}


/**
 * Trim the number of events in the event stream for $userid. If an event was just added for a particular question,
 * pass the question's id in $questionid (to help focus the truncation).
 * @param $userid
 * @param $questionid
 */
function ilya_db_user_events_truncate($userid, $questionid = null)
{
	// First try truncating based on there being too many events for this question

	$questiontruncated = false;

	if (isset($questionid)) {
		$truncate = ilya_db_read_one_value(ilya_db_query_sub(
			'SELECT updated FROM ^userevents WHERE userid=$ AND questionid=# ORDER BY updated DESC LIMIT #,1',
			$userid, $questionid, ILYA_DB_MAX_EVENTS_PER_Q
		), true);

		if (isset($truncate)) {
			ilya_db_query_sub(
				'DELETE FROM ^userevents WHERE userid=$ AND questionid=# AND updated<=$',
				$userid, $questionid, $truncate
			);

			$questiontruncated = true;
		}
	}

	// If that didn't happen, try truncating the stream in general based on its total length

	if (!$questiontruncated) {
		$truncate = ilya_db_read_one_value(ilya_db_query_sub(
			'SELECT updated FROM ^userevents WHERE userid=$ ORDER BY updated DESC LIMIT #,1',
			$userid, (int)ilya_opt('max_store_user_updates')
		), true);

		if (isset($truncate))
			ilya_db_query_sub(
				'DELETE FROM ^userevents WHERE userid=$ AND updated<=$',
				$userid, $truncate
			);
	}
}
