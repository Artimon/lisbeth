<?php

/**
 * Handles automated generation of database-specific cache keys,
 * and thus prevents intermix of cache keys from different databases
 * on the same memcache server.
 *
 * Example:
 * Lisbeth_CacheKey::getInstance('database_1')->createKey('users', 1);
 * Lisbeth_CacheKey::getInstance('database_2')->createKey('users', 1);
 */
class Lisbeth_KeyGenerator {
	/**
	 * @var int
	 */
	private static $cacheSpaceId = 0;

	/**
	 * @return Lisbeth_KeyGenerator
	 */
	public static function getInstance() {
		return Lisbeth_ObjectPool::get(
			'Lisbeth_KeyGenerator',
			self::$cacheSpaceId
		);
	}

	/**
	 * @param string $cacheSpace database name for example
	 */
	public static function setCacheSpace($cacheSpace) {
		self::$cacheSpaceId = crc32($cacheSpace);
	}

	/**
	 * @param string $cacheIndex
	 * @param int|float|string $id
	 * @return string
	 */
	public function createKey($cacheIndex, $id = null) {
		$cacheSpace = self::$cacheSpaceId;

		$cacheKey = "space_{$cacheSpace}_{$cacheIndex}";

		if ($id !== null) {
			$cacheKey .= "_{$id}";
		}

		return $cacheKey;
	}
}