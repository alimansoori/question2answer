<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Database-level access to metas tables


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
 * Set the metadata for user $userid with $key to $value. Keys beginning ilya_ are reserved for the Q2A core.
 * @param $userid
 * @param $key
 * @param $value
 */
function ilya_db_usermeta_set($userid, $key, $value)
{
	ilya_db_meta_set('usermetas', 'userid', $userid, $key, $value);
}


/**
 * Clear the metadata for user $userid with $key ($key can also be an array of keys)
 * @param $userid
 * @param $key
 */
function ilya_db_usermeta_clear($userid, $key)
{
	ilya_db_meta_clear('usermetas', 'userid', $userid, $key);
}


/**
 * Return the metadata value for user $userid with $key ($key can also be an array of keys in which case this
 * returns an array of metadata key => value).
 * @param $userid
 * @param $key
 * @return array|mixed|null
 */
function ilya_db_usermeta_get($userid, $key)
{
	return ilya_db_meta_get('usermetas', 'userid', $userid, $key);
}


/**
 * Set the metadata for post $postid with $key to $value. Keys beginning ilya_ are reserved for the Q2A core.
 * @param $postid
 * @param $key
 * @param $value
 */
function ilya_db_postmeta_set($postid, $key, $value)
{
	ilya_db_meta_set('postmetas', 'postid', $postid, $key, $value);
}


/**
 * Clear the metadata for post $postid with $key ($key can also be an array of keys)
 * @param $postid
 * @param $key
 */
function ilya_db_postmeta_clear($postid, $key)
{
	ilya_db_meta_clear('postmetas', 'postid', $postid, $key);
}


/**
 * Return the metadata value for post $postid with $key ($key can also be an array of keys in which case this
 * returns an array of metadata key => value).
 * @param $postid
 * @param $key
 * @return array|mixed|null
 */
function ilya_db_postmeta_get($postid, $key)
{
	return ilya_db_meta_get('postmetas', 'postid', $postid, $key);
}


/**
 * Set the metadata for category $categoryid with $key to $value. Keys beginning ilya_ are reserved for the Q2A core.
 * @param $categoryid
 * @param $key
 * @param $value
 */
function ilya_db_categorymeta_set($categoryid, $key, $value)
{
	ilya_db_meta_set('categorymetas', 'categoryid', $categoryid, $key, $value);
}


/**
 * Clear the metadata for category $categoryid with $key ($key can also be an array of keys)
 * @param $categoryid
 * @param $key
 */
function ilya_db_categorymeta_clear($categoryid, $key)
{
	ilya_db_meta_clear('categorymetas', 'categoryid', $categoryid, $key);
}


/**
 * Return the metadata value for category $categoryid with $key ($key can also be an array of keys in which
 * case this returns an array of metadata key => value).
 * @param $categoryid
 * @param $key
 * @return array|mixed|null
 */
function ilya_db_categorymeta_get($categoryid, $key)
{
	return ilya_db_meta_get('categorymetas', 'categoryid', $categoryid, $key);
}


/**
 * Set the metadata for tag $tag with $key to $value. Keys beginning ilya_ are reserved for the Q2A core.
 * @param $tag
 * @param $key
 * @param $value
 */
function ilya_db_tagmeta_set($tag, $key, $value)
{
	ilya_db_meta_set('tagmetas', 'tag', $tag, $key, $value);
}


/**
 * Clear the metadata for tag $tag with $key ($key can also be an array of keys)
 * @param $tag
 * @param $key
 */
function ilya_db_tagmeta_clear($tag, $key)
{
	ilya_db_meta_clear('tagmetas', 'tag', $tag, $key);
}


/**
 * Return the metadata value for tag $tag with $key ($key can also be an array of keys in which case this
 * returns an array of metadata key => value).
 * @param $tag
 * @param $key
 * @return array|mixed|null
 */
function ilya_db_tagmeta_get($tag, $key)
{
	return ilya_db_meta_get('tagmetas', 'tag', $tag, $key);
}


/**
 * Internal general function to set metadata
 * @param $metatable
 * @param $idcolumn
 * @param $idvalue
 * @param $title
 * @param $content
 */
function ilya_db_meta_set($metatable, $idcolumn, $idvalue, $title, $content)
{
	ilya_db_query_sub(
		'INSERT INTO ^' . $metatable . ' (' . $idcolumn . ', title, content) VALUES ($, $, $) ' .
		'ON DUPLICATE KEY UPDATE content = VALUES(content)',
		$idvalue, $title, $content
	);
}


/**
 * Internal general function to clear metadata
 * @param $metatable
 * @param $idcolumn
 * @param $idvalue
 * @param $title
 */
function ilya_db_meta_clear($metatable, $idcolumn, $idvalue, $title)
{
	if (is_array($title)) {
		if (count($title)) {
			ilya_db_query_sub(
				'DELETE FROM ^' . $metatable . ' WHERE ' . $idcolumn . '=$ AND title IN ($)',
				$idvalue, $title
			);
		}
	} else {
		ilya_db_query_sub(
			'DELETE FROM ^' . $metatable . ' WHERE ' . $idcolumn . '=$ AND title=$',
			$idvalue, $title
		);
	}
}


/**
 * Internal general function to return metadata
 * @param $metatable
 * @param $idcolumn
 * @param $idvalue
 * @param $title
 * @return array|mixed|null
 */
function ilya_db_meta_get($metatable, $idcolumn, $idvalue, $title)
{
	if (is_array($title)) {
		if (count($title)) {
			return ilya_db_read_all_assoc(ilya_db_query_sub(
				'SELECT title, content FROM ^' . $metatable . ' WHERE ' . $idcolumn . '=$ AND title IN($)',
				$idvalue, $title
			), 'title', 'content');
		} else {
			return array();
		}

	} else {
		return ilya_db_read_one_value(ilya_db_query_sub(
			'SELECT content FROM ^' . $metatable . ' WHERE ' . $idcolumn . '=$ AND title=$',
			$idvalue, $title
		), true);
	}
}
