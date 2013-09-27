<?php

interface Lisbeth_IEntity {
	/**
	 * @param int $id
	 * @param bool $load
	 */
	public function __construct($id, $load = true);

	/**
	 * Creates a blank entity, to load by other criteria for example.
	 *
	 * Example:
	 * Entity::blank()->by('name', 'username');
	 *
	 * @return Lisbeth_Entity
	 */
	public static function blank();

	/**
	 * Note:
	 * Does not fulfill the conditions to be a static function.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return null|Lisbeth_Entity
	 */
	public function by($key, $value);

	/**
	 * Note:
	 * Could (maybe) be removed, too.
	 *
	 * @return string
	 */
	public function primary();

	/**
	 * No reference, should be a copy within the entity.
	 *
	 * @param null|array $data
	 */
	public function injectData($data);

	/**
	 * Load data from storage.
	 *
	 * @param bool $force loading without memcache
	 */
	public function load($force = false);

	/**
	 * Updates all changed data.
	 *
	 * @return	bool	true if updated
	 */
	public function update();

	/**
	 * Delete the entity.
	 */
	public function delete();

	/**
	 * Return if the entity was loaded successfully.
	 *
	 * @return bool true on valid state
	 */
	public function valid();

	/**
	 * @deprecated Use self::data() instead.
	 */
	public function getData();

	/**
	 * Return plain data of the entity.
	 *
	 * @return array
	 */
	public function data();

	/**
	 * Return an entity value.
	 *
	 * @param string $index
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function get($index);

	/**
	 * Return an account value.
	 *
	 * @param string $index
	 * @param int|string $value
	 * @param bool $noUpdate
	 * @throws InvalidArgumentException
	 * @return Lisbeth_Entity
	 */
	public function set($index, $value, $noUpdate = false);

	/**
	 * @param string $index
	 * @param int|float $value
	 * @return Lisbeth_Entity
	 */
	public function increment($index, $value);

	/**
	 * @param string $index
	 * @param int|float $value
	 * @return Lisbeth_Entity
	 */
	public function decrement($index, $value);

	/**
	 * Force writing of a none-existing value to use object without real data.
	 *
	 * @param string $index
	 * @param int|string $value
	 */
	public function inject($index, $value);

	/**
	 * Puts non-numerical values into quotation marks.
	 *
	 * @param mixed $value
	 * @return float|string
	 */
	public function quote($value);
}