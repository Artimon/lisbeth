<?php

/**
 * Provides data entity functionality.
 */
abstract class Lisbeth_Entity
	extends Lisbeth_Distributor
	implements Lisbeth_IEntity {

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
	 * Creates a blank entity, to load by other criteria for example.
	 *
	 * Example:
	 * Entity::blank()->by('name', 'username');
	 *
	 * @return Lisbeth_Entity
	 */
	public static function blank() {
		return new static(0, false);
	}

	/**
	 * Note:
	 * Does not fulfill the conditions to be a static function.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return null|Lisbeth_Entity
	 */
	public function by($key, $value) {
		$key = mysql_real_escape_string($key);

		$sql = "
			SELECT
				`{$this->primary}` AS `primary`
			FROM
				`{$this->table}`
			WHERE
				`{$key}` = {$this->quote($value)};";

		$database = new Lisbeth_Database();
		$database->query($sql);

		$result = $database->fetch();
		$database->freeResult();

		if ($result) {
			return new static($result['primary']);
		}

		return $this;
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
	 * Enable post data manipulation by overriding this method.
	 */
	protected function postProcess(&$data) {
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
			$update[] = "`{$index}` = {$this->quote($this->data[$index])}";
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

		$this->data = null;
		$this->validate();
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
	public function get($index) {
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
	 * @param bool $noUpdate
	 * @throws InvalidArgumentException
	 * @return Lisbeth_Entity
	 */
	public function set($index, $value, $noUpdate = false) {
		if (!array_key_exists($index, $this->data)) {
			throw new InvalidArgumentException("Set value ['{$index}'] for entity '".get_class($this)."({$this->id})' not found.");
		}

		if ($value != $this->data[$index]) {
			$this->data[$index] = $value;

			if (!$noUpdate) {
				$this->changedData[$index] = true;
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
		$this->set(
			$index,
			$this->get($index) + $value
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

	/**
	 * @param mixed $value
	 * @return float|string
	 */
	public function quote($value) {
		$value = $this->database()->sanitize($value);

		return is_numeric($value) ? $value : "'{$value}'";
	}
}