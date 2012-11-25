<?php

class Lisbeth_EntityTest extends PHPUnit_Framework_TestCase {
	private $sut;
	private $database;
	private $memcache;

	protected function setUp() {
		$this->database = $this->getMock('Lisbeth_Database');
		$this->memcache = $this->getMock('Lisbeth_Memcache');


		$this->sut = $this->getMock(
			'Lisbeth_Entity',
			array('memcache', 'database'),
			array(),
			'',
			false
		);

		$this->sut
			->expects($this->any())
			->method('database')
			->will($this->returnValue($this->database));

		$this->sut
			->expects($this->any())
			->method('memcache')
			->will($this->returnValue($this->memcache));
	}

	public function testLoadCached() {
		$this->memcache
			->expects($this->once())
			->method('get')
			->will($this->returnValue(null));

		$this->memcache
			->expects($this->never())
			->method('set');

		$this->assertEquals(
			null,
			$this->sut->load()
		);
	}

	public function testLoadNotCached() {
		$this->memcache
			->expects($this->once())
			->method('get')
			->will($this->returnValue(false));

		$this->memcache
			->expects($this->once())
			->method('set');

		$this->database
			->expects($this->once())
			->method('query');

		$this->database
			->expects($this->once())
			->method('fetch');

		$this->database
			->expects($this->once())
			->method('freeResult');

		$this->assertEquals(
			null,
			$this->sut->load()
		);
	}

	public function testSetValueValueUpdate() {
		$this->assertFalse(
			$this->sut->update()
		);


		$testData = array('index' => 'value');

		$this->memcache
			->expects($this->once())
			->method('get')
			->will($this->returnValue($testData));

		$this->sut->load();
		$this->sut->setValue('index', 'newValue');

		$this->assertEquals(
			'newValue',
			$this->sut->value('index')
		);

		$this->assertTrue(
			$this->sut->valid()
		);

		$this->assertTrue(
			is_array($this->sut->getData())
		);


		$this->database
			->expects($this->once())
			->method('query');

		$this->database
			->expects($this->once())
			->method('freeResult');

		$this->memcache
			->expects($this->once())
			->method('replace');

		// First time update.
		$this->assertTrue(
			$this->sut->update()
		);

		// Second time, no update since changes have been reset.
		$this->assertFalse(
			$this->sut->update()
		);

		// Third time set same value as it was, no changes to update.
		$this->sut->setValue('index', 'newValue');

		$this->assertFalse(
			$this->sut->update()
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testSetDataInvalid() {
		$this->sut->setValue('test', 1);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGetDataInvalid() {
		$this->assertFalse(
			$this->sut->valid()
		);

		$this->assertEmpty(
			$this->sut->getData()
		);

		$this->sut->value('test');
	}
}
