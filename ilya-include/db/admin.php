<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Database access functions which are specific to the admin center


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
 * Return the current version of MySQL
 */
function ilya_db_mysql_version()
{
	return ilya_db_read_one_value(ilya_db_query_raw('SELECT VERSION()'));
}


/**
 * Return the total size in bytes of all relevant tables in the Q2A database
 */
function ilya_db_table_size()
{
	if (defined('ILYA__MYSQL_USERS_PREFIX')) { // check if one of the prefixes is a prefix itself of the other
		if (stripos(ILYA__MYSQL_USERS_PREFIX, ILYA__MYSQL_TABLE_PREFIX) === 0)
			$prefixes = array(ILYA__MYSQL_TABLE_PREFIX);
		elseif (stripos(ILYA__MYSQL_TABLE_PREFIX, ILYA__MYSQL_USERS_PREFIX) === 0)
			$prefixes = array(ILYA__MYSQL_USERS_PREFIX);
		else
			$prefixes = array(ILYA__MYSQL_TABLE_PREFIX, ILYA__MYSQL_USERS_PREFIX);

	} else
		$prefixes = array(ILYA__MYSQL_TABLE_PREFIX);

	$size = 0;
	foreach ($prefixes as $prefix) {
		$statuses = ilya_db_read_all_assoc(ilya_db_query_raw(
			"SHOW TABLE STATUS LIKE '" . $prefix . "%'"
		));

		foreach ($statuses as $status)
			$size += $status['Data_length'] + $status['Index_length'];
	}

	return $size;
}


/**
 * Return a count of the number of posts of $type in database.
 * Set $fromuser to true to only count non-anonymous posts, false to only count anonymous posts
 * @param $type
 * @param $fromuser
 * @return mixed|null
 */
function ilya_db_count_posts($type = null, $fromuser = null)
{
	$wheresql = '';

	if (isset($type))
		$wheresql .= ' WHERE type=' . ilya_db_argument_to_mysql($type, true);

	if (isset($fromuser))
		$wheresql .= (strlen($wheresql) ? ' AND' : ' WHERE') . ' userid ' . ($fromuser ? 'IS NOT' : 'IS') . ' NULL';

	return ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT COUNT(*) FROM ^posts' . $wheresql
	));
}


/**
 * Return number of registered users in database.
 */
function ilya_db_count_users()
{
	return ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT COUNT(*) FROM ^users'
	));
}


/**
 * Return number of active users in database $table
 * @param $table
 * @return mixed|null
 */
function ilya_db_count_active_users($table)
{
	switch ($table) {
		case 'posts':
		case 'uservotes':
		case 'userpoints':
			break;

		default:
			ilya_fatal_error('ilya_db_count_active_users() called for unknown table');
			break;
	}

	return ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT COUNT(DISTINCT(userid)) FROM ^' . $table
	));
}


/**
 * Return number of categories in the database
 */
function ilya_db_count_categories()
{
	return ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT COUNT(*) FROM ^categories'
	));
}


/**
 * Return number of questions in the database in $categoryid exactly, and not one of its subcategories
 * @param $categoryid
 * @return mixed|null
 */
function ilya_db_count_categoryid_qs($categoryid)
{
	return ilya_db_read_one_value(ilya_db_query_sub(
		"SELECT COUNT(*) FROM ^posts WHERE categoryid<=># AND type='Q'",
		$categoryid
	));
}


/**
 * Return list of postids of visible or queued posts by $userid
 * @param $userid
 * @return array
 */
function ilya_db_get_user_visible_postids($userid)
{
	return ilya_db_read_all_values(ilya_db_query_sub(
		"SELECT postid FROM ^posts WHERE userid=# AND type IN ('Q', 'A', 'C', 'Q_QUEUED', 'A_QUEUED', 'C_QUEUED')",
		$userid
	));
}


/**
 * Return list of postids of visible or queued posts from $ip address
 * @param $ip
 * @return array
 */
function ilya_db_get_ip_visible_postids($ip)
{
	return ilya_db_read_all_values(ilya_db_query_sub(
		"SELECT postid FROM ^posts WHERE createip=UNHEX($) AND type IN ('Q', 'A', 'C', 'Q_QUEUED', 'A_QUEUED', 'C_QUEUED')",
		bin2hex(@inet_pton($ip))
	));
}


/**
 * Return an array whose keys contain the $postids which exist, and whose elements contain the number of other posts depending on each one
 * @param $postids
 * @return array
 */
function ilya_db_postids_count_dependents($postids)
{
	if (count($postids))
		return ilya_db_read_all_assoc(ilya_db_query_sub(
			"SELECT postid, COALESCE(childcount, 0) AS count FROM ^posts LEFT JOIN (SELECT parentid, COUNT(*) AS childcount FROM ^posts WHERE parentid IN (#) AND LEFT(type, 1) IN ('A', 'C') GROUP BY parentid) x ON postid=x.parentid WHERE postid IN (#)",
			$postids, $postids
		), 'postid', 'count');
	else
		return array();
}


/**
 * Return an array of the (up to) $count most recently created users who are awaiting approval and have not been blocked.
 * The array element for each user includes a 'profile' key whose value is an array of non-empty profile fields of the user.
 * @param $count
 * @return array
 */
function ilya_db_get_unapproved_users($count)
{
	$results = ilya_db_read_all_assoc(ilya_db_query_sub(
		"SELECT ^users.userid, UNIX_TIMESTAMP(created) AS created, createip, email, handle, flags, title, content FROM ^users LEFT JOIN ^userprofile ON ^users.userid=^userprofile.userid AND LENGTH(content)>0 WHERE level<# AND NOT (flags&#) ORDER BY created DESC LIMIT #",
		ILYA__USER_LEVEL_APPROVED, ILYA__USER_FLAGS_USER_BLOCKED, $count
	));

	$users = array();

	foreach ($results as $result) {
		$userid = $result['userid'];

		if (!isset($users[$userid])) {
			$users[$result['userid']] = $result;
			$users[$result['userid']]['profile'] = array();
			unset($users[$userid]['title']);
			unset($users[$userid]['content']);
		}

		if (isset($result['title']) && isset($result['content']))
			$users[$userid]['profile'][$result['title']] = $result['content'];
	}

	return $users;
}


/**
 * Return whether there are any blobs whose content has been stored as a file on disk
 */
function ilya_db_has_blobs_on_disk()
{
	return ilya_db_read_one_value(ilya_db_query_sub('SELECT blobid FROM ^blobs WHERE content IS NULL LIMIT 1'), true) != null;
}


/**
 * Return whether there are any blobs whose content has been stored in the database
 */
function ilya_db_has_blobs_in_db()
{
	return ilya_db_read_one_value(ilya_db_query_sub('SELECT blobid FROM ^blobs WHERE content IS NOT NULL LIMIT 1'), true) != null;
}


/**
 * Return the maximum position of the categories with $parentid
 * @param $parentid
 * @return mixed|null
 */
function ilya_db_category_last_pos($parentid)
{
	return ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT COALESCE(MAX(position), 0) FROM ^categories WHERE parentid<=>#',
		$parentid
	));
}


/**
 * Return how many levels of subcategory there are below $categoryid
 * @param $categoryid
 * @return int
 */
function ilya_db_category_child_depth($categoryid)
{
	// This is potentially a very slow query since it counts all the multi-generational offspring of a particular category
	// But it's only used for admin purposes when moving a category around so I don't think it's worth making more efficient
	// (Incidentally, this could be done by keeping a count for every category of how many generations of offspring it has.)

	$result = ilya_db_read_one_assoc(ilya_db_query_sub(
		'SELECT COUNT(child1.categoryid) AS count1, COUNT(child2.categoryid) AS count2, COUNT(child3.categoryid) AS count3 FROM ^categories AS child1 LEFT JOIN ^categories AS child2 ON child2.parentid=child1.categoryid LEFT JOIN ^categories AS child3 ON child3.parentid=child2.categoryid WHERE child1.parentid=#;', // requires ILYA__CATEGORY_DEPTH=4
		$categoryid
	));

	for ($depth = ILYA__CATEGORY_DEPTH - 1; $depth >= 1; $depth--)
		if ($result['count' . $depth])
			return $depth;

	return 0;
}


/**
 * Create a new category with $parentid, $title (=name) and $tags (=slug) in the database
 * @param $parentid
 * @param $title
 * @param $tags
 * @return mixed
 */
function ilya_db_category_create($parentid, $title, $tags)
{
	$lastpos = ilya_db_category_last_pos($parentid);

	ilya_db_query_sub(
		'INSERT INTO ^categories (parentid, title, tags, position) VALUES (#, $, $, #)',
		$parentid, $title, $tags, 1 + $lastpos
	);

	$categoryid = ilya_db_last_insert_id();

	ilya_db_categories_recalc_backpaths($categoryid);

	return $categoryid;
}


/**
 * Recalculate the backpath columns for all categories from $firstcategoryid to $lastcategoryid (if specified)
 * @param $firstcategoryid
 * @param $lastcategoryid
 */
function ilya_db_categories_recalc_backpaths($firstcategoryid, $lastcategoryid = null)
{
	if (!isset($lastcategoryid))
		$lastcategoryid = $firstcategoryid;

	ilya_db_query_sub(
		"UPDATE ^categories AS x, (SELECT cat1.categoryid, CONCAT_WS('/', cat1.tags, cat2.tags, cat3.tags, cat4.tags) AS backpath FROM ^categories AS cat1 LEFT JOIN ^categories AS cat2 ON cat1.parentid=cat2.categoryid LEFT JOIN ^categories AS cat3 ON cat2.parentid=cat3.categoryid LEFT JOIN ^categories AS cat4 ON cat3.parentid=cat4.categoryid WHERE cat1.categoryid BETWEEN # AND #) AS a SET x.backpath=a.backpath WHERE x.categoryid=a.categoryid",
		$firstcategoryid, $lastcategoryid // requires ILYA__CATEGORY_DEPTH=4
	);
}


/**
 * Set the name of $categoryid to $title and its slug to $tags in the database
 * @param $categoryid
 * @param $title
 * @param $tags
 */
function ilya_db_category_rename($categoryid, $title, $tags)
{
	ilya_db_query_sub(
		'UPDATE ^categories SET title=$, tags=$ WHERE categoryid=#',
		$title, $tags, $categoryid
	);

	ilya_db_categories_recalc_backpaths($categoryid); // may also require recalculation of its offspring's backpaths
}


/**
 * Set the content (=description) of $categoryid to $content
 * @param $categoryid
 * @param $content
 */
function ilya_db_category_set_content($categoryid, $content)
{
	ilya_db_query_sub(
		'UPDATE ^categories SET content=$ WHERE categoryid=#',
		$content, $categoryid
	);
}


/**
 * Return the parentid of $categoryid
 * @param $categoryid
 * @return mixed|null
 */
function ilya_db_category_get_parent($categoryid)
{
	return ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT parentid FROM ^categories WHERE categoryid=#',
		$categoryid
	));
}


/**
 * Move the category $categoryid into position $newposition under its parent
 * @param $categoryid
 * @param $newposition
 */
function ilya_db_category_set_position($categoryid, $newposition)
{
	ilya_db_ordered_move('categories', 'categoryid', $categoryid, $newposition,
		ilya_db_apply_sub('parentid<=>#', array(ilya_db_category_get_parent($categoryid))));
}


/**
 * Set the parent of $categoryid to $newparentid, placing it in last position (doesn't do necessary recalculations)
 * @param $categoryid
 * @param $newparentid
 */
function ilya_db_category_set_parent($categoryid, $newparentid)
{
	$oldparentid = ilya_db_category_get_parent($categoryid);

	if (strcmp($oldparentid, $newparentid)) { // if we're changing parent, move to end of old parent, then end of new parent
		$lastpos = ilya_db_category_last_pos($oldparentid);

		ilya_db_ordered_move('categories', 'categoryid', $categoryid, $lastpos, ilya_db_apply_sub('parentid<=>#', array($oldparentid)));

		$lastpos = ilya_db_category_last_pos($newparentid);

		ilya_db_query_sub(
			'UPDATE ^categories SET parentid=#, position=# WHERE categoryid=#',
			$newparentid, 1 + $lastpos, $categoryid
		);
	}
}


/**
 * Change the categoryid of any posts with (exact) $categoryid to $reassignid
 * @param $categoryid
 * @param $reassignid
 */
function ilya_db_category_reassign($categoryid, $reassignid)
{
	ilya_db_query_sub('UPDATE ^posts SET categoryid=# WHERE categoryid<=>#', $reassignid, $categoryid);
}


/**
 * Delete the category $categoryid in the database
 * @param $categoryid
 */
function ilya_db_category_delete($categoryid)
{
	ilya_db_ordered_delete('categories', 'categoryid', $categoryid,
		ilya_db_apply_sub('parentid<=>#', array(ilya_db_category_get_parent($categoryid))));
}


/**
 * Return the categoryid for the category with parent $parentid and $slug
 * @param $parentid
 * @param $slug
 * @return mixed|null
 */
function ilya_db_category_slug_to_id($parentid, $slug)
{
	return ilya_db_read_one_value(ilya_db_query_sub(
		'SELECT categoryid FROM ^categories WHERE parentid<=># AND tags=$',
		$parentid, $slug
	), true);
}


/**
 * Create a new custom page (or link) in the database
 * @param $title
 * @param $flags
 * @param $tags
 * @param $heading
 * @param $content
 * @param $permit
 * @return mixed
 */
function ilya_db_page_create($title, $flags, $tags, $heading, $content, $permit = null)
{
	$position = ilya_db_read_one_value(ilya_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^pages'));

	ilya_db_query_sub(
		'INSERT INTO ^pages (title, nav, flags, permit, tags, heading, content, position) VALUES ($, \'\', #, #, $, $, $, #)',
		$title, $flags, $permit, $tags, $heading, $content, $position
	);

	return ilya_db_last_insert_id();
}


/**
 * Set the fields of $pageid to the values provided in the database
 * @param $pageid
 * @param $title
 * @param $flags
 * @param $tags
 * @param $heading
 * @param $content
 * @param $permit
 */
function ilya_db_page_set_fields($pageid, $title, $flags, $tags, $heading, $content, $permit = null)
{
	ilya_db_query_sub(
		'UPDATE ^pages SET title=$, flags=#, permit=#, tags=$, heading=$, content=$ WHERE pageid=#',
		$title, $flags, $permit, $tags, $heading, $content, $pageid
	);
}


/**
 * Move the page $pageid into navigation menu $nav and position $newposition in the database
 * @param $pageid
 * @param $nav
 * @param $newposition
 */
function ilya_db_page_move($pageid, $nav, $newposition)
{
	ilya_db_query_sub(
		'UPDATE ^pages SET nav=$ WHERE pageid=#',
		$nav, $pageid
	);

	ilya_db_ordered_move('pages', 'pageid', $pageid, $newposition);
}


/**
 * Delete the page $pageid in the database
 * @param $pageid
 */
function ilya_db_page_delete($pageid)
{
	ilya_db_ordered_delete('pages', 'pageid', $pageid);
}


/**
 * Move the entity identified by $idcolumn=$id into position $newposition (within optional $conditionsql) in $table in the database
 * @param $table
 * @param $idcolumn
 * @param $id
 * @param $newposition
 * @param $conditionsql
 */
function ilya_db_ordered_move($table, $idcolumn, $id, $newposition, $conditionsql = null)
{
	$andsql = isset($conditionsql) ? (' AND ' . $conditionsql) : '';

	ilya_db_query_sub('LOCK TABLES ^' . $table . ' WRITE');

	$oldposition = ilya_db_read_one_value(ilya_db_query_sub('SELECT position FROM ^' . $table . ' WHERE ' . $idcolumn . '=#' . $andsql, $id));

	if ($newposition != $oldposition) {
		$lastposition = ilya_db_read_one_value(ilya_db_query_sub('SELECT MAX(position) FROM ^' . $table . ' WHERE TRUE' . $andsql));

		$newposition = max(1, min($newposition, $lastposition)); // constrain it to within range

		// move it temporarily off the top because we have a unique key on the position column
		ilya_db_query_sub('UPDATE ^' . $table . ' SET position=# WHERE ' . $idcolumn . '=#' . $andsql, 1 + $lastposition, $id);

		if ($newposition < $oldposition)
			ilya_db_query_sub('UPDATE ^' . $table . ' SET position=position+1 WHERE position BETWEEN # AND #' . $andsql . ' ORDER BY position DESC', $newposition, $oldposition);
		else
			ilya_db_query_sub('UPDATE ^' . $table . ' SET position=position-1 WHERE position BETWEEN # AND #' . $andsql . ' ORDER BY position', $oldposition, $newposition);

		ilya_db_query_sub('UPDATE ^' . $table . ' SET position=# WHERE ' . $idcolumn . '=#' . $andsql, $newposition, $id);
	}

	ilya_db_query_sub('UNLOCK TABLES');
}


/**
 * Delete the entity identified by $idcolumn=$id (and optional $conditionsql) in $table in the database
 * @param $table
 * @param $idcolumn
 * @param $id
 * @param $conditionsql
 */
function ilya_db_ordered_delete($table, $idcolumn, $id, $conditionsql = null)
{
	$andsql = isset($conditionsql) ? (' AND ' . $conditionsql) : '';

	ilya_db_query_sub('LOCK TABLES ^' . $table . ' WRITE');

	$oldposition = ilya_db_read_one_value(ilya_db_query_sub('SELECT position FROM ^' . $table . ' WHERE ' . $idcolumn . '=#' . $andsql, $id));

	ilya_db_query_sub('DELETE FROM ^' . $table . ' WHERE ' . $idcolumn . '=#' . $andsql, $id);

	ilya_db_query_sub('UPDATE ^' . $table . ' SET position=position-1 WHERE position>#' . $andsql . ' ORDER BY position', $oldposition);

	ilya_db_query_sub('UNLOCK TABLES');
}


/**
 * Create a new user field with (internal) tag $title, label $content, $flags and $permit in the database.
 * @param $title
 * @param $content
 * @param $flags
 * @param $permit
 * @return mixed
 */
function ilya_db_userfield_create($title, $content, $flags, $permit = null)
{
	$position = ilya_db_read_one_value(ilya_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^userfields'));

	ilya_db_query_sub(
		'INSERT INTO ^userfields (title, content, position, flags, permit) VALUES ($, $, #, #, #)',
		$title, $content, $position, $flags, $permit
	);

	return ilya_db_last_insert_id();
}


/**
 * Change the user field $fieldid to have label $content, $flags and $permit in the database (the title column cannot be changed once set)
 * @param $fieldid
 * @param $content
 * @param $flags
 * @param $permit
 */
function ilya_db_userfield_set_fields($fieldid, $content, $flags, $permit = null)
{
	ilya_db_query_sub(
		'UPDATE ^userfields SET content=$, flags=#, permit=# WHERE fieldid=#',
		$content, $flags, $permit, $fieldid
	);
}


/**
 * Move the user field $fieldid into position $newposition in the database
 * @param $fieldid
 * @param $newposition
 */
function ilya_db_userfield_move($fieldid, $newposition)
{
	ilya_db_ordered_move('userfields', 'fieldid', $fieldid, $newposition);
}


/**
 * Delete the user field $fieldid in the database
 * @param $fieldid
 */
function ilya_db_userfield_delete($fieldid)
{
	ilya_db_ordered_delete('userfields', 'fieldid', $fieldid);
}


/**
 * Return the ID of a new widget, to be displayed by the widget module named $title on templates within $tags (comma-separated list)
 * @param $title
 * @param $tags
 * @return mixed
 */
function ilya_db_widget_create($title, $tags)
{
	$position = ilya_db_read_one_value(ilya_db_query_sub('SELECT 1+COALESCE(MAX(position), 0) FROM ^widgets'));

	ilya_db_query_sub(
		'INSERT INTO ^widgets (place, position, tags, title) VALUES (\'\', #, $, $)',
		$position, $tags, $title
	);

	return ilya_db_last_insert_id();
}


/**
 * Set the comma-separated list of templates for $widgetid to $tags
 * @param $widgetid
 * @param $tags
 */
function ilya_db_widget_set_fields($widgetid, $tags)
{
	ilya_db_query_sub(
		'UPDATE ^widgets SET tags=$ WHERE widgetid=#',
		$tags, $widgetid
	);
}


/**
 * Move the widget $widgetit into position $position in the database's order, and show it in $place on the page
 * @param $widgetid
 * @param $place
 * @param $newposition
 */
function ilya_db_widget_move($widgetid, $place, $newposition)
{
	ilya_db_query_sub(
		'UPDATE ^widgets SET place=$ WHERE widgetid=#',
		$place, $widgetid
	);

	ilya_db_ordered_move('widgets', 'widgetid', $widgetid, $newposition);
}


/**
 * Delete the widget $widgetid in the database
 * @param $widgetid
 */
function ilya_db_widget_delete($widgetid)
{
	ilya_db_ordered_delete('widgets', 'widgetid', $widgetid);
}
