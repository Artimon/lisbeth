<?php

class Lisbeth_KeyGeneratorTest extends PHPUnit_Framework_TestCase {
	public function testGetSingleton() {
		Lisbeth_KeyGenerator::setCacheSpace('database1');
		$sut1 = Lisbeth_KeyGenerator::getSingleton();
		$sut2 = Lisbeth_KeyGenerator::getSingleton();

		Lisbeth_KeyGenerator::setCacheSpace('database2');
		$sut3 = Lisbeth_KeyGenerator::getSingleton();

		$this->assertSame($sut1, $sut2);
		$this->assertNotSame($sut1, $sut3);
	}

	public function testCreateKey() {
		Lisbeth_KeyGenerator::setCacheSpace('database');
		$sut = Lisbeth_KeyGenerator::getSingleton();

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
