<?php

require_once '../bootstrap.php';

class Generate {
	/**
	 * @var Lisbeth_Database
	 */
	private $database;

	/**
	 * @param string $message
	 */
	public function sendMessage($message) {
		header('Content-type: application/json');

		echo json_encode(array(
			'message' => $message
		));

		$this->database->close();

		die();
	}

	/**
	 * @param string $type
	 * @return string
	 */
	protected function type($type) {
		$integers = array('tinyint', 'smallint', 'mediumint', 'int', 'bigint');
		foreach ($integers as $integer) {
			if (strpos($type, $integer) !== false) {
				return 'int';
			}
		}

		$floats = array('decimal', 'float', 'double', 'real');
		foreach ($floats as $float) {
			if (strpos($type, $float) !== false) {
				return 'float';
			}
		}

		return 'string';
	}

	/**
	 * @param string $name
	 * @param bool $upperCamelCase
	 * @return string
	 */
	protected function camelCase($name, $upperCamelCase = false) {
		$name = preg_replace('/[^a-zA-Z0-9]/', '', $name);

		$parts = explode('_', $name);
		foreach ($parts as &$part) {
			$part = ucfirst($part);
		}

		$name = implode('_', $parts);
		if (!$upperCamelCase) {
			$name = lcfirst($name);
		}

		return $name;
	}

	/**
	 * @param string $entityName
	 * @param string $table
	 * @return string
	 */
	protected function createMethods($entityName, $table) {
		$config = array();
		$properties = array();
		$methods = array();

		$this->database->query('DESCRIBE ' . $table);
		$structure = $this->database->fetchAll();
		$this->database->freeResult();

		$config[] = "protected \$table = '{$table}';";

		foreach ($structure as $data) {
			$field = $this->camelCase(
				$data['Field']
			);

			$type = $this->type(
				$data['Type']
			);

			if ($data['Key'] === 'PRI') {
				$config[] = "
	protected \$primary = '{$data['Field']}';";
			}

			$properties[] = "
	/**
	 * @var {$type}
	 */
	protected \${$field};";

			$methods[] = "
	/**
	 * @param {$type}|null
	 * @return {$type}|{$entityName}
	 */
	public function {$field}(\$value = null) {
		if (\$value === null) {
			return ({$type})\$this->{$field};
		}

		\$this->{$field} = ({$type})\$value;

		return \$this;
	}";
		}

		return
			implode('', $config) . "\n" .
			implode("\n", $properties) . "\n" .
			implode("\n", $methods);
	}

	/**
	 * @param string[] $tables
	 * @return int
	 */
	protected function buildEntities($tables) {
		$count = 0;

		foreach ($tables as $table) {
			$entityName = 'Lisbeth_Entity_' . $this->camelCase($table, true);

			$class = "<?php
abstract class {$entityName} extends Lisbeth_Entity {
	{$this->createMethods($entityName, $table)}
}";

			file_put_contents("../lisbeth/entity/{$table}.php", $class);

			++$count;
		}

		return $count;
	}

	public function execute() {
		$this->database = new Lisbeth_Database();
		$result = $this->database->connect(
			$_POST['host'],
			$_POST['user'],
			$_POST['password']
		);

		if (!$result) {
			$this->sendMessage('Could not connect to database.');
		}

		$result = $this->database->selectDatabase(
			$_POST['database']
		);

		if (!$result) {
			$this->sendMessage('Could not select database.');
		}

		$this->database->query("SHOW TABLES");
		$tables = $this->database->fetchColumn();
		$this->database->freeResult();

		$count = $this->buildEntities($tables);
		$this->sendMessage("Successfully generated {$count} entities.");
	}
}

$generate = new Generate();
$generate->execute();