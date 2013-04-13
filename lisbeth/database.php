<?php

/**
 * Database query helper class.
 */
class Lisbeth_Database {
	/**
	 * @var string
	 */
	private $sql;

	/**
	 * @var resource|int|bool
	 */
	private $result;

	/**
	 * @var int
	 */
	private $errorNumber;

	/**
	 * @var string
	 */
	private $errorString;

	/**
	 * @var callback
	 */
	private $errorHandler;

	/**
	 * @var int
	 */
	private $queries = 0;

	/**
	 * @param callback $errorHandler
	 */
	public function setErrorHandler($errorHandler) {
		$this->errorHandler = $errorHandler;
	}

	/**
	 * Execute given query.
	 *
	 * @param string $sql
	 * @return Lisbeth_Database
	 */
	public function query($sql) {
		$this->sql = trim($sql);

		$this->result = mysql_query($this->sql);
		++$this->queries;

		$this->errorNumber = 0;
		$this->errorString = '';
		if ($this->result === false) {
			$this->errorNumber = mysql_errno();
			$this->errorString = mysql_error();

			// Ask for duplicates to handle errors instead.
			if (!$this->isDuplicate() && $this->errorHandler) {
				$this->errorHandler(
					$this->errorNumber,
					$this->getErrorMessage(),
					__CLASS__,
					__LINE__
				);
			}
		}

		return $this;
	}

	/**
	 * Return the current error state.
	 *
	 * @return bool true on error
	 */
	public function hasError() {
		return ($this->errorNumber ? true : false);
	}

	/**
	 * Return the sql error message.
	 *
	 * @return string
	 */
	public function errorMessage() {
		if ($this->hasError()) {
			return "Query:\n{$this->sql}\nAnswer:\n{$this->errorString}";
		}

		return 'No error occurred.';
	}

	/**
	 * Return the duplicate check state.
	 *
	 * @return bool true on duplicate entry
	 */
	public function isDuplicate() {
		return ($this->errorNumber === 1062);
	}

	/**
	 * Return the amount of submitted queries.
	 *
	 * @return int
	 */
	public function getQueries() {
		return $this->queries;
	}

	/**
	 * Return one fetched query result.
	 *
	 * @return array or null on error
	 */
	public function fetch() {
		if ($this->hasError()) {
			return null;
		}

		return @mysql_fetch_assoc($this->result);
	}

	/**
	 * Return all query results at once.
	 *
	 * @return array
	 */
	public function fetchAll() {
		$result = array();

		while ($data = $this->fetch()) {
			$result[] = $data;
		}

		return $result;
	}

	/**
	 * Return an array containing the content of a single column.
	 *
	 * @return array
	 */
	public function fetchColumn() {
		$result = array();

		while ($data = $this->fetch()) {
			$result[] = current($data);
		}

		return $result;
	}

	/**
	 * Return the content of a single selected field.
	 *
	 * @return mixed
	 */
	public function fetchOne() {
		$data = $this->fetch();

		return $data ? current($data) : null;
	}

	/**
	 * Return mysql num rows.
	 *
	 * @return int
	 */
	public function numRows() {
		if ($this->hasError()) {
			return -1;
		}

		return mysql_num_rows($this->result);
	}

	/**
	 * Frees the mysql result.
	 */
	public function freeResult() {
		if ((false === $this->hasError()) && ($this->result != -1)) {
			mysql_free_result($this->result);
			$this->result = -1;
		}
	}

	/**
	 * @param string $value
	 * @return string
	 */
	public function escape($value) {
		return mysql_real_escape_string($value);
	}
}