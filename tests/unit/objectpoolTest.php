<?php

class Lisbeth_ObjectPoolTestDummy {
	public function __construct($id = 0) {
		return;
	}
}

class Lisbeth_ObjectPoolTest extends PHPUnit_Framework_TestCase {
	public function testGet() {
		$fixtureClass = 'Lisbeth_ObjectPoolTestDummy';

		$this->assertSame(
			Lisbeth_ObjectPool::get($fixtureClass),
			Lisbeth_ObjectPool::get($fixtureClass)
		);

		$this->assertSame(
			Lisbeth_ObjectPool::get($fixtureClass, 1),
			Lisbeth_ObjectPool::get($fixtureClass, 1)
		);

		$this->assertNotSame(
			Lisbeth_ObjectPool::get($fixtureClass, 1),
			Lisbeth_ObjectPool::get($fixtureClass, 2)
		);
	}
}
