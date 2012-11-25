<?php

class Lisbeth_KeyGeneratorTest extends PHPUnit_Framework_TestCase {
	public function testGetInstance() {
		Lisbeth_KeyGenerator::setCacheSpace('database1');
		$sut1 = Lisbeth_KeyGenerator::getInstance();
		$sut2 = Lisbeth_KeyGenerator::getInstance();

		Lisbeth_KeyGenerator::setCacheSpace('database2');
		$sut3 = Lisbeth_KeyGenerator::getInstance();

		$this->assertSame($sut1, $sut2);
		$this->assertNotSame($sut1, $sut3);
	}

	public function testCreateKey() {
		Lisbeth_KeyGenerator::setCacheSpace('database');
		$sut = Lisbeth_KeyGenerator::getInstance();

		$result = $sut->createKey('table');
		$this->assertEquals(
			'space_-917305810_table',
			$result
		);

		$result = $sut->createKey('table', 1);
		$this->assertEquals(
			'space_-917305810_table_1',
			$result
		);
	}
}
