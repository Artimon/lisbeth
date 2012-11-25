<?php

/**
 * Every object that extends from the distributor has accessors
 * to database and memcache instances.
 */
abstract class Lisbeth_Distributor {
	/**
	 * @var Lisbeth_Database
	 */
	private $database;

	/**
	 * Get database instance.
	 *
	 * @return Lisbeth_Database
	 */
	public function database() {
		if ($this->database === null) {
			$this->database = new Lisbeth_Database();
		}

		return $this->database;
	}

	/**
	 * Get memcache instance.
	 *
	 * @return Lisbeth_Memcache
	 */
	public function memcache() {
		return Lisbeth_Memcache::getInstance();
	}

	/**
	 * @return Lisbeth_KeyGenerator
	 */
	public function keyGenerator() {
		return Lisbeth_ObjectPool::get('Lisbeth_KeyGenerator');
	}
}