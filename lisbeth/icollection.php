<?php

interface Lisbeth_ICollection {
	/**
	 * @param int $id
	 */
	public function __construct($id);

	/**
	 * @param int $id
	 * @return Lisbeth_Distributor
	 */
	public static function getInstance($id);

	/**
	 * Initialize data entity.
	 *
	 * @param int $id
	 */
	public function init($id);

	/**
	 * Note:
	 * It would be the best to make this protected and remove the force
	 * parameter. This currently is just a legacy code fallback for
	 * SuK Clans (evil!).
	 *
	 * Load collection from storage and create entities.
	 *
	 * @param bool $force loading without memcache
	 * @return Lisbeth_Entity[]
	 */
	public function load($force = false);

	/**
	 * @return int
	 */
	public function id();

	/**
	 * @return string
	 */
	public function cacheKey();

	/**
	 * @return Lisbeth_Database
	 */
	public function database();

	/**
	 * @return Lisbeth_Memcache
	 */
	public function memcache();

	public function clearCache();

	/**
	 * @return Lisbeth_KeyGenerator
	 */
	public function keyGenerator();

	/**
	 * @param $entityId
	 * @return null|Lisbeth_Entity
	 */
	public function entity($entityId);

	/**
	 * Return entities of the collection.
	 *
	 * @return Lisbeth_Entity[]
	 */
	public function entities();

	/**
	 * @return int[]
	 */
	public function entityIds();

	/**
	 * Add entity to list.
	 *
	 * @param int $entityId
	 */
	public function addEntity($entityId);

	/**
	 * Remove entity from list.
	 *
	 * @param int $entityId
	 */
	public function removeEntity($entityId);

	/**
	 * @param int $entityId
	 * @param Lisbeth_Collection $collection
	 * @return bool
	 */
	public function moveEntityTo($entityId, Lisbeth_Collection $collection);
}