<?php

/**
 * Every object that extends from the distributor has accessors
 * to database, memcache and cache key generator instances.
 */
abstract class Lisbeth_Distributor {
	/**
	 * @var int primary key value
	 */
	protected $id;

	/**
	 * @var string cache key
	 */
	protected $cacheKey;

	/**
	 * @var Lisbeth_Database
	 */
	private $database;

	/**
	 * @param int $id
	 * @return Lisbeth_Distributor
	 */
	public static function getInstance($id) {
		$className = get_called_class();

		return Lisbeth_ObjectPool::get($className, $id);
	}

	/**
	 * Initialize data entity.
	 *
	 * @param int $id
	 */
	public function init($id) {
		$this->id = (int)$id;
		$this->cacheKey = $this->keyGenerator()->createKey(
			get_called_class(),
			$this->id
		);
	}

	/**
	 * @return int
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function cacheKey() {
		return $this->cacheKey;
	}

	/**
	 * @return Lisbeth_Database
	 */
	public function database() {
		if ($this->database === null) {
			$this->database = new Lisbeth_Database();
		}

		return $this->database;
	}

	/**
	 * @return Lisbeth_Memcache
	 */
	public function memcache() {
		return Lisbeth_Memcache::getSingleton();
	}

	public function clearCache() {
		$this->memcache()->delete($this->cacheKey);
	}

	/**
	 * @return Lisbeth_KeyGenerator
	 */
	public function keyGenerator() {
		return Lisbeth_KeyGenerator::getSingleton();
	}
}