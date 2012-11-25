<?php

/**
 * Provides data entity functionality.
 */
abstract class Lisbeth_Entity extends Lisbeth_Distributor {
	/**
	 * @var string database table name
	 */
	protected $table;

	/**
	 * @var string primary key name
	 */
	protected $primary = 'id';

	/**
	 * @var string individual sql
	 */
	protected $sql;

	/**
	 * @var bool flag to only load data from cache
	 */
	protected $noCache = false;

	/**
	 * Default for unit testing.
	 *
	 * @var string entity cache key index
	 */
	protected $cacheIndex = 'globals';

	/**
	 * @var string cache key
	 */
	private $cacheKey;

	/**
	 * @var int primary key value
	 */
	private $id;

	/**
	 * @var bool
	 */
	private $valid = false;

	/**
	 * @var null|array of account data
	 */
	private $data = array();

	/**
	 * @var array of account data indices
	 */
	private $changedData = array();


	/**
	 * Constructor
	 *
	 * @param int $id
	 * @param bool $load
	 */
	public function __construct($id, $load = true) {
		$this->init($id);

		if ($load) {
			$this->load();
		}
	}

	/**
	 * @param int $id
	 * @return Lisbeth_Entity
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
		$this->cacheKey = $this->keyGenerator()->createKey(__CLASS__, $this->id);
	}

	/**
	 * @return string
	 */
	public function cacheKey() {
		return $this->cacheKey;
	}

	/**
	 * @return string
	 */
	public function primary() {
		return $this->primary;
	}

	/**
	 * No reference, should be a copy within the entity.
	 *
	 * @param null|array $data
	 */
	public function injectData($data) {
		$this->data = $data;
		$this->postProcess($this->data);

		$this->validate();
	}

	/**
	 * Load data from storage.
	 *
	 * @param bool $force loading without memcache
	 */
	public function load($force = false) {
		$this->data = $this->memcache()->get($this->cacheKey);

		if ((false === $this->data) || $force) {
			if (null === $this->sql) {
				$this->sql = "
					SELECT *
					FROM
						`{$this->table}`
					WHERE
						`{$this->primary}` = {$this->id}
					LIMIT 1;";
			}

			$database = $this->database();
			$database->query($this->sql);

			$this->data = $database->fetch();
			$database->freeResult();

			$this->postProcess($this->data);

			if ($this->noCache === false) {
				$this->memcache()->set($this->cacheKey, $this->data);
			}
		}

		$this->validate();
	}

	/**
	 * Enable post data manipulation.
	 */
	public function postProcess(&$data) {
		return;
	}

	/**
	 * @return void
	 */
	private function validate() {
		$this->valid = is_array($this->data);
	}

	/**
	 * Updates all changed data.
	 *
	 * @unittested
	 * @return	bool	true if updated
	 */
	public function update() {
		if (empty($this->changedData)) {
			return false;
		}

		$update = array();
		foreach ($this->changedData as $index => $isNumeric) {
			$value = $this->data[$index];
			$value = $isNumeric ? (float)$value : "'".mysql_real_escape_string($value)."'";

			$update[] = "`{$index}` = {$value}";
		}

		$sql = "
			UPDATE `{$this->table}`
			SET
				".implode(',', $update)."
			WHERE
				`{$this->primary}` = {$this->id}
			LIMIT 1;";
		$database = $this->database();
		$database->query($sql);
		$database->freeResult();

		// Lisbeth_Entity must be in cache, since self::load() does a set if not available.
		$memcache = $this->memcache();
		if ($this->noCache) {
			$memcache->delete($this->cacheKey);
		}
		else {
			$memcache->replace($this->cacheKey, $this->data);
		}

		$this->changedData = array();

		return true;
	}

	public function clearCache() {
		$this->memcache()->delete($this->cacheKey);
	}

	/**
	 * Delete the entity.
	 */
	public function delete() {
		$sql = "
			DELETE FROM `{$this->table}`
			WHERE
				`{$this->primary}` = {$this->id}
			LIMIT 1;";
		$database = $this->database();
		$database->query($sql);
		$database->freeResult();

		// Lisbeth_Entity must be in cache, since self::load() does a set if not available.
		$memcache = $this->memcache();
		$memcache->delete($this->cacheKey);

		$this->data = array();
	}

	/**
	 * Return if the entity was loaded successfully.
	 *
	 * @return bool true on valid state
	 */
	public function valid() {
		return $this->valid;
	}

	/**
	 * @return int
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * @deprecated Use self::data() instead.
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Return plain data of the entity.
	 *
	 * @return array
	 */
	public function data() {
		return $this->data;
	}

	/**
	 * Return an entity value.
	 *
	 * @unittested
	 * @param	string	$index
	 * @return	string
	 * @throws	InvalidArgumentException
	 */
	public function value($index) {
		if (is_array($this->data) && array_key_exists($index, $this->data)) {
			return $this->data[$index];
		}

		throw new InvalidArgumentException("Get value ['{$index}'] for entity '".get_class($this)."({$this->id})' not found.");
	}

	/**
	 * Return an account value.
	 *
	 * @param string $index
	 * @param int|string $value
	 * @param bool $setOnly
	 * @throws InvalidArgumentException
	 * @return Lisbeth_Entity
	 */
	public function setValue($index, $value, $setOnly = false) {
		if (!array_key_exists($index, $this->data)) {
			throw new InvalidArgumentException("Set value ['{$index}'] for entity '".get_class($this)."({$this->id})' not found.");
		}

		if ($value != $this->data[$index]) {
			$this->data[$index] = $value;

			if (!$setOnly) {
				$this->changedData[$index] = is_numeric($value);
			}
		}

		return $this;
	}

	/**
	 * @param string $index
	 * @param int|float $value
	 * @return Lisbeth_Entity
	 */
	public function increment($index, $value) {
		$this->setValue(
			$index,
			$this->value($index) + $value
		);

		return $this;
	}

	/**
	 * @param string $index
	 * @param int|float $value
	 * @return Lisbeth_Entity
	 */
	public function decrement($index, $value) {
		return $this->increment($index, -$value);
	}

	/**
	 * Force writing of a none-existing value to use object without real data.
	 *
	 * @param	string		$index
	 * @param	int|string	$value
	 */
	public function inject($index, $value) {
		$this->data[$index] = $value;
	}
}