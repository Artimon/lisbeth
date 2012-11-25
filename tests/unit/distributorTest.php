<?php

class Lisbeth_DistributorExposed extends Lisbeth_Distributor {}

class Lisbeth_DistributorTest extends PHPUnit_Framework_TestCase {
	public function testDatabase() {
		$dataObjectProvider = new Lisbeth_DistributorExposed(1);

		$this->assertInstanceOf(
			'Lisbeth_Database',
			$dataObjectProvider->database()
		);
	}

	public function testMemcache() {
		$dataObjectProvider = new Lisbeth_DistributorExposed(1);

		$this->assertInstanceOf(
			'Lisbeth_Memcache',
			$dataObjectProvider->memcache()
		);
	}
}