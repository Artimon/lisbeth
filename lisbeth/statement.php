<?php

class Lisbeth_Statement {
	/**
	 * @var Lisbeth_Database
	 */
	private $database;

	/**
	 * @var string
	 */
	private $sql;

	/**
	 * @param Lisbeth_Database $database
	 */
	public function __construct(Lisbeth_Database $database) {
		$this->database = $database;
	}

	/**
	 * @return string
	 */
	public function sql() {
		return $this->sql;
	}

	/**
	 * Example:
	 *
	 * $parameters = array('amount' => 15);
	 * $sql = "SELECT * FROM table WHERE `amount` = :amount;";
	 *
	 * @param array $parameters
	 * @param string $sql
	 * @return $this
	 */
	public function prepare(array $parameters, $sql) {
		$search = array();
		$replace = array();

		foreach ($parameters as $key => $value) {
			$search[] = ':' . $key;
			$replace[] = $this->database->sanitize($value);
		}

		$this->sql = str_replace($search, $replace, $sql);

		return $this;
	}

	/**
	 * @return Lisbeth_Database
	 */
	public function execute() {
		return $this->database->query($this->sql);
	}
}