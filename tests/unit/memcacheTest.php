<?php

class memcacheTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $memcache;

	public function setUp() {
		$this->memcache = $this->getMock('Memcache');
	}

	public function testGetSingleton() {
		$this->assertInstanceOf(
			'Lisbeth_Memcache',
			Lisbeth_Memcache::getSingleton()
		);
	}

	public function testIsConnected() {
		$this->assertFalse(
			Lisbeth_Memcache::getSingleton()->isConnected()
		);
	}
}
