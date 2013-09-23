<?php

/**
 * Provides entity collection functionality.
 */
abstract class Lisbeth_Collection
	extends Lisbeth_Distributor
	implements Iterator {

	/**
	 * @var string database table name
	 */
	protected $table;

	/**
	 * @var string primary key name
	 */
	protected $primary = 'id';

	/**
	 * @var string group key name
	 */
	protected $group;

	/**
	 * @var string order clause
	 */
	protected $order;

	/**
	 * @var string dataEntity object name
	 */
	protected $entityName;

	/**
	 * Default for unit testing.
	 *
	 * @var string collection cache key index
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
	 * @var array of entityId => entityId
	 */
	protected $data = array();

	/**
	 * @var Lisbeth_Entity[]
	 */
	protected $entities;


	/**
	 * Constructor
	 *
	 * @param int $id
	 */
	public function __construct($id) {
		$this->init($id);
		$this->load();
	}

	/**
	 * Initialize entity collection.
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
	 * Load collection from storage and create entities.
	 *
	 * @param bool $force loading without memcache
	 * @return Lisbeth_Entity[]
	 */
	public function load($force = false) {
		$memcache = $this->memcache();

		$this->data = $memcache->get($this->cacheKey);
		if ((false === $this->data) || $force) {
			if (empty($this->order)) {
				$this->order = "`{$this->primary}` DESC";
			}

			$sql = "
				SELECT
					`{$this->primary}`
				FROM
					`{$this->table}`
				WHERE
					`{$this->group}` = {$this->id}
				ORDER BY
					{$this->order};";
			$database = $this->database();
			$database->query($sql);

			$this->data = array();
			while ($item = $database->fetch()) {
				$entityId = (int)$item[$this->primary];
				$this->data[$entityId] = $entityId;
			}
			$database->freeResult();

			$memcache->set($this->cacheKey, $this->data);
		}

		return $this->entities();
	}

	/**
	 * @param $entityId
	 * @return null|Lisbeth_Entity
	 */
	public function entity($entityId) {
		$entityId = (int)$entityId;
		$entities = $this->entities();

		if (isset($entities[$entityId])) {
			return $entities[$entityId];
		}

		return null;
	}

	/**
	 * Return entities of the collection.
	 *
	 * @return Lisbeth_Entity[]
	 */
	public function entities() {
		if ($this->entities !== null) {
			return $this->entities;
		}

		// Copy data to preserve order.
		$this->entities = $this->data;
		$className = $this->entityName;

		$missed = $this->loadFromObjectPool($className);
		if (empty($missed)) {
			return $this->entities;
		}

		$missed = $this->loadFromMemcache($missed);
		if (empty($missed)) {
			return $this->entities;
		}

		$this->loadFromDatabase($missed);

		return $this->entities;
	}

	/**
	 * @param array $ids
	 * @return array
	 */
	protected function loadFromDatabase(array $ids) {
		if (empty($ids)) {
			return array();
		}

		// Injection safe since ids come from database and are integers.
		$idList = implode(',', $ids);

		$sql = "
			SELECT *
			FROM
				`{$this->table}`
			WHERE
				`{$this->primary}` IN {$idList};";
		$database = $this->database();
		$database->query($sql);

		while ($entityData = $database->fetch()) {
			$id = (int)$entityData[$this->primary];
			$this->entities[$id]->injectData($entityData);

			unset($ids[$id]);
		}
		$database->freeResult();

		return $ids;
	}

	/**
	 * @param array $ids
	 * @return array
	 */
	protected function loadFromMemcache(array $ids) {
		$cacheKeys = array();
		foreach ($ids as $id) {
			$cacheKeys[] = $this->entities[$id]->cacheKey();
		}

		$allData = $this->memcache()->get($cacheKeys);
		if (is_array($allData)) {
			$primary = current($this->entities)->primary();

			foreach ($allData as $entityData) {
				if (!empty($entityData)) {
					$id = $entityData[$primary];
					$this->entities[$id]->injectData($entityData);

					unset($ids[$id]);
				}
			}
		}

		return $ids;
	}

	/**
	 * @param string $className
	 * @return array
	 */
	protected function loadFromObjectPool($className) {
		$missed = array();

		foreach ($this->data as $id) {
			/** @var $entity Lisbeth_Entity */
			if (Lisbeth_ObjectPool::has($className, $id)) {
				$entity = Lisbeth_ObjectPool::get($className, $id);
			}
			else {
				$entity = new $className($id);
				Lisbeth_ObjectPool::set($entity, $className, $id);

				$missed[$id] = $id;
			}

			$this->entities[$id] = $entity;
		}

		return $missed;
	}

	/**
	 * @return array
	 */
	public function entityIds() {
		return $this->data;
	}

	/**
	 * Add entity to list.
	 *
	 * @unittested
	 * @param int $entityId
	 */
	public function addEntity($entityId) {
		$entityId = (int)$entityId;

		if (!isset($this->data[$entityId])) {
			$this->data[$entityId] = $entityId;
			$this->memcache()->replace($this->cacheKey, $this->data);

			$this->reload();
		}
	}

	/**
	 * Remove entity from list.
	 *
	 * @unittested
	 * @param int $entityId
	 */
	public function removeEntity($entityId) {
		$entityId = (int)$entityId;

		if (isset($this->data[$entityId])) {
			unset($this->data[$entityId]);
			$this->memcache()->replace($this->cacheKey, $this->data);

			$this->reload();
		}
	}

	protected function reload() {
		$this->entities = null;
		$this->entities();
	}

	/**
	 * @param int $entityId
	 * @param Lisbeth_Collection $collection
	 * @return bool
	 */
	public function moveEntityTo($entityId, Lisbeth_Collection $collection) {
		$entity = $this->entity($entityId);
		if (empty($entity)) {
			return false;
		}

		$entity
			->setValue($this->group, $collection->id())
			->update();

		$this->removeEntity($entityId);
		$collection->addEntity($entityId);

		return true;
	}

	public function rewind() {
		reset($this->entities);
	}

	/**
	 * @return Lisbeth_Entity|bool false
	 */
	public function current() {
		return current($this->entities);
	}

	/**
	 * @return int|null
	 */
	public function key() {
		return key($this->entities);
	}

	/**
	 * @return Lisbeth_Entity|bool false
	 */
	public function next() {
		return next($this->entities);
	}

	/**
	 * @return bool
	 */
	public function valid() {
		return ($this->current() !== false);
	}
}