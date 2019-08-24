<?php
/*
	IlyaIdea by Gideon Greenspan and contributors
	https://projekt.ir/

	File: ilya-include/ILYA/Storage/CacheManager.php
	Description: Handler for caching system.


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

/**
 * Caches data (typically from database queries) to the filesystem.
 */
class ILYA_Storage_CacheFactory
{
	private static $cacheDriver = null;

	/**
	 * Get the appropriate cache handler.
	 * @return ILYA_Storage_CacheDriver The cache handler.
	 */
	public static function getCacheDriver()
	{
		if (self::$cacheDriver === null) {
			$config = array(
				'enabled' => (int) ilya_opt('caching_enabled') === 1,
				'keyprefix' => ILYA_FINAL_MYSQL_DATABASE . '.' . ILYA_MYSQL_TABLE_PREFIX . '.',
				'dir' => defined('ILYA_CACHE_DIRECTORY') ? ILYA_CACHE_DIRECTORY : null,
			);

			$driver = ilya_opt('caching_driver');

			switch($driver)
			{
				case 'memcached':
					self::$cacheDriver = new ILYA_Storage_MemcachedDriver($config);
					break;

				case 'filesystem':
				default:
					self::$cacheDriver = new ILYA_Storage_FileCacheDriver($config);
					break;
			}

		}

		return self::$cacheDriver;
	}
}
