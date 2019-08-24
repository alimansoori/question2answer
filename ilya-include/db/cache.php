<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	Description: Database-level access to cache table


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

require_once ILYA_INCLUDE_DIR . 'db/maxima.php';


/**
 * Create (or replace) the item ($type, $cacheid) in the database cache table with $content
 * @param $type
 * @param $cacheid
 * @param $content
 * @return mixed
 */
function ilya_db_cache_set($type, $cacheid, $content)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	ilya_db_query_sub(
		'DELETE FROM ^cache WHERE lastread<NOW()-INTERVAL # SECOND',
		ILYA_DB_MAX_CACHE_AGE
	);

	ilya_db_query_sub(
		'INSERT INTO ^cache (type, cacheid, content, created, lastread) VALUES ($, #, $, NOW(), NOW()) ' .
		'ON DUPLICATE KEY UPDATE content = VALUES(content), created = VALUES(created), lastread = VALUES(lastread)',
		$type, $cacheid, $content
	);
}


/**
 * Retrieve the item ($type, $cacheid) from the database cache table
 * @param $type
 * @param $cacheid
 * @return mixed|null
 */
function ilya_db_cache_get($type, $cacheid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$content = ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT content FROM ^cache WHERE type=$ AND cacheid=#',
		$type, $cacheid
	), true);

	if (isset($content))
		ilya_db_query_sub(
			'UPDATE ^cache SET lastread=NOW() WHERE type=$ AND cacheid=#',
			$type, $cacheid
		);

	return $content;
}
