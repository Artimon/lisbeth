<?php

class Lisbeth_DatabaseTest extends PHPUnit_Framework_TestCase {
	public function testHasError() {
		$sut = new Lisbeth_Database();

		$this->assertFalse(
			$sut->hasError()
		);
	}
}
