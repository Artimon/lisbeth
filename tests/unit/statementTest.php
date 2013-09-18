<?php

class Lisbeth_DatabaseExposed extends Lisbeth_Database {
	public function sanitize($value) {
		return $value;
	}
}

class Lisbeth_StatementTest extends PHPUnit_Framework_TestCase {
	public function testPrepare() {
		$fixtureParameters = array(
			'amount1' => 15,
			'amount2' => 'test'
		);
		$fixtureSql = "UPDATE table SET field1 = :amount1 WHERE field2 = ':amount2':";


		$database = new Lisbeth_DatabaseExposed();
		$sut = new Lisbeth_Statement($database);
		$sut->prepare($fixtureParameters, $fixtureSql);

		$this->assertEquals(
			"UPDATE table SET field1 = 15 WHERE field2 = 'test':",
			$sut->sql()
		);
	}
}