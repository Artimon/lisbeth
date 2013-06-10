<?php

class Lisbeth_EntityExposed extends Lisbeth_Entity { }

class Lisbeth_CollectionExposed extends Lisbeth_Collection {
	protected $entityName = 'Lisbeth_EntityExposed';

	public function setEntity($index, $entity) {
		$this->entities[$index] = $entity;
	}

	public function setData(array $data) {
		$this->data = $data;
	}

	public function getData() {
		return $this->data;
	}

	public function passLoadFromDatabase($ids) {
		return $this->loadFromDatabase($ids);
	}
}

class Lisbeth_CollectionTest extends PHPUnit_Framework_TestCase {
	private $collection;
	private $collectionExposed;
	private $database;
	private $memcache;
	private $objectPool;

	protected function setUp() {
		$this->database = $this->getMock('Lisbeth_Database');
		$this->memcache = $this->getMock('Lisbeth_Memcache');
		$this->objectPool = $this->getMock('Lisbeth_ObjectPool');


		$this->collection = $this->getMock(
			'Lisbeth_Collection',
			array('memcache', 'database', 'objectPool'),
			array(),
			'',
			false
		);

		$this->collection
			->expects($this->any())
			->method('database')
			->will($this->returnValue($this->database));

		$this->collection
			->expects($this->any())
			->method('memcache')
			->will($this->returnValue($this->memcache));

		$this->collection
			->expects($this->any())
			->method('objectPool')
			->will($this->returnValue($this->objectPool));


		$this->collectionExposed = $this->getMock(
			'Lisbeth_CollectionExposed',
			array('memcache', 'database'),
			array(),
			'',
			false
		);

		$this->collectionExposed
			->expects($this->any())
			->method('database')
			->will($this->returnValue($this->database));

		$this->collectionExposed
			->expects($this->any())
			->method('memcache')
			->will($this->returnValue($this->memcache));
	}

	public function testLoadFromDatabase() {
		$idsFixture = array(
			3 => 3,
			5 => 5,
			7 => 7
		);

		$fetchFixture1 = array('id' => 3);
		$fetchFixture2 = array('id' => 5);

		$this->database
			->expects($this->at(1))
			->method('fetch')
			->will($this->returnValue($fetchFixture1));
		$this->database
			->expects($this->at(2))
			->method('fetch')
			->will($this->returnValue($fetchFixture2));

		$entity = $this->getMock('Lisbeth_Entity', array(), array(123));

		$this->collectionExposed->setEntity(3, $entity);
		$this->collectionExposed->setEntity(5, $entity);

		$result = $this->collectionExposed->passLoadFromDatabase($idsFixture);

		$this->assertEquals(
			array(7 => 7),
			$result
		);
	}

	public function testEntities() {
		$result = $this->collection->entities();

		$this->assertTrue(
			is_array($result)
		);

		$this->assertTrue(
			empty($result)
		);
	}

	public function testAddEntityNotSetYet() {
		$this->collectionExposed->setData(array(1 => 1, 2 => 2));

		$this->memcache
			->expects($this->once())
			->method('replace');

		$this->collectionExposed->addEntity(3);

		$result = $this->collectionExposed->getData();

		$this->assertEquals(
			3,
			count($result)
		);

		$this->assertTrue(
			isset($result[3])
		);
	}

	public function testAddEntityAlreadySet() {
		$this->collectionExposed->setData(array(1 => 1, 2 => 2));

		$this->memcache
			->expects($this->never())
			->method('replace');

		$this->collectionExposed->addEntity(2);

		$result = $this->collectionExposed->getData();

		$this->assertEquals(
			2,
			count($result)
		);

		$this->assertFalse(
			isset($result[3])
		);
	}

	public function testRemoveEntityNoneExisting() {
		$this->collectionExposed->setData(array(1 => 1, 2 => 2));

		$this->memcache
			->expects($this->never())
			->method('replace');

		$this->collectionExposed->removeEntity(3);

		$result = $this->collectionExposed->getData();

		$this->assertEquals(
			2,
			count($result)
		);

		$this->assertFalse(
			isset($result[3])
		);
	}

	public function testRemoveEntityExisting() {
		$this->collectionExposed->setData(array(1 => 1, 2 => 2));

		$this->memcache
			->expects($this->once())
			->method('replace');

		$this->collectionExposed->removeEntity(2);

		$result = $this->collectionExposed->getData();

		$this->assertEquals(
			1,
			count($result)
		);

		$this->assertFalse(
			isset($result[2])
		);
	}
}