<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	Description: Application-level blob-management functions


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
 * Return the URL which will output $blobid from the database when requested, $absolute or relative
 * @param $blobid
 * @param bool $absolute
 * @return mixed|string
 */
function ilya_get_blob_url($blobid, $absolute = false)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return ilya_path('blob', array('ilya_blobid' => $blobid), $absolute ? ilya_opt('site_url') : null, ILYA__URL_FORMAT_PARAMS);
}


/**
 * Return the full path to the on-disk directory for blob $blobid (subdirectories are named by the first 3 digits of $blobid)
 * @param $blobid
 * @return mixed|string
 */
function ilya_get_blob_directory($blobid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return rtrim(ILYA__BLOBS_DIRECTORY, '/') . '/' . substr(str_pad($blobid, 20, '0', STR_PAD_LEFT), 0, 3);
}


/**
 * Return the full page and filename of blob $blobid which is in $format ($format is used as the file name suffix e.g. .jpg)
 * @param $blobid
 * @param $format
 * @return mixed|string
 */
function ilya_get_blob_filename($blobid, $format)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	return ilya_get_blob_directory($blobid) . '/' . $blobid . '.' . preg_replace('/[^A-Za-z0-9]/', '', $format);
}


/**
 * Create a new blob (storing the content in the database or on disk as appropriate) with $content and $format, returning its blobid.
 * Pass the original name of the file uploaded in $sourcefilename and the $userid, $cookieid and $ip of the user creating it
 * @param $content
 * @param $format
 * @param $sourcefilename
 * @param $userid
 * @param $cookieid
 * @param $ip
 * @return mixed|null|string
 */
function ilya_create_blob($content, $format, $sourcefilename = null, $userid = null, $cookieid = null, $ip = null)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/blobs.php';

	$blobid = ilya_db_blob_create(defined('ILYA__BLOBS_DIRECTORY') ? null : $content, $format, $sourcefilename, $userid, $cookieid, $ip);

	if (isset($blobid) && defined('ILYA__BLOBS_DIRECTORY')) {
		// still write content to the database if writing to disk failed
		if (!ilya_write_blob_file($blobid, $content, $format))
			ilya_db_blob_set_content($blobid, $content);
	}

	return $blobid;
}


/**
 * Write the on-disk file for blob $blobid with $content and $format. Returns true if the write succeeded, false otherwise.
 * @param $blobid
 * @param $content
 * @param $format
 * @return bool|mixed
 */
function ilya_write_blob_file($blobid, $content, $format)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$written = false;

	$directory = ilya_get_blob_directory($blobid);
	if (is_dir($directory) || mkdir($directory, fileperms(rtrim(ILYA__BLOBS_DIRECTORY, '/')) & 0777)) {
		$filename = ilya_get_blob_filename($blobid, $format);

		$file = fopen($filename, 'xb');
		if (is_resource($file)) {
			if (fwrite($file, $content) >= strlen($content))
				$written = true;

			fclose($file);

			if (!$written)
				unlink($filename);
		}
	}

	return $written;
}


/**
 * Retrieve blob $blobid from the database, reading the content from disk if appropriate
 * @param $blobid
 * @return array|mixed|null
 */
function ilya_read_blob($blobid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/blobs.php';

	$blob = ilya_db_blob_read($blobid);

	if (isset($blob) && defined('ILYA__BLOBS_DIRECTORY') && !isset($blob['content']))
		$blob['content'] = ilya_read_blob_file($blobid, $blob['format']);

	return $blob;
}


/**
 * Read the content of blob $blobid in $format from disk. On failure, it will return false.
 * @param $blobid
 * @param $format
 * @return mixed|null|string
 */
function ilya_read_blob_file($blobid, $format)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	$filename = ilya_get_blob_filename($blobid, $format);
	if (is_readable($filename))
		return file_get_contents($filename);
	else
		return null;
}


/**
 * Delete blob $blobid from the database, and remove the on-disk file if appropriate
 * @param $blobid
 * @return mixed
 */
function ilya_delete_blob($blobid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/blobs.php';

	if (defined('ILYA__BLOBS_DIRECTORY')) {
		$blob = ilya_db_blob_read($blobid);

		if (isset($blob) && !isset($blob['content']))
			unlink(ilya_get_blob_filename($blobid, $blob['format']));
	}

	ilya_db_blob_delete($blobid);
}


/**
 * Delete the on-disk file for blob $blobid in $format
 * @param $blobid
 * @param $format
 * @return mixed
 */
function ilya_delete_blob_file($blobid, $format)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	unlink(ilya_get_blob_filename($blobid, $format));
}


/**
 * Check if blob $blobid exists
 * @param $blobid
 * @return bool|mixed
 */
function ilya_blob_exists($blobid)
{
	if (ilya_to_override(__FUNCTION__)) { $args=func_get_args(); return ilya_call_override(__FUNCTION__, $args); }

	require_once ILYA__INCLUDE_DIR . 'db/blobs.php';

	return ilya_db_blob_exists($blobid);
}
