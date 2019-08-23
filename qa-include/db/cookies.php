<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Database access functions for user cookies


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

if (!defined('ILYA__VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


/**
 * Create a new random cookie for $ipaddress and insert into database, returning it
 * @param $ipaddress
 * @return null|string
 */
function ilya_db_cookie_create($ipaddress)
{
	for ($attempt = 0; $attempt < 10; $attempt++) {
		$cookieid = ilya_db_random_bigint();

		if (ilya_db_cookie_exists($cookieid))
			continue;

		ilya_db_query_sub(
			'INSERT INTO ^cookies (cookieid, created, createip) ' .
			'VALUES (#, NOW(), UNHEX($))',
			$cookieid, bin2hex(@inet_pton($ipaddress))
		);

		return $cookieid;
	}

	return null;
}


/**
 * Note in database that a write operation has been done by user identified by $cookieid and from $ipaddress
 * @param $cookieid
 * @param $ipaddress
 */
function ilya_db_cookie_written($cookieid, $ipaddress)
{
	ilya_db_query_sub(
		'UPDATE ^cookies SET written=NOW(), writeip=UNHEX($) WHERE cookieid=#',
		bin2hex(@inet_pton($ipaddress)), $cookieid
	);
}


/**
 * Return whether $cookieid exists in database
 * @param $cookieid
 * @return bool
 */
function ilya_db_cookie_exists($cookieid)
{
	$cookie = ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT COUNT(*) FROM ^cookies WHERE cookieid=#',
		$cookieid
	));

	return $cookie > 0;
}
