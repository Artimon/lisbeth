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
		$properties = array();
		$methods = array();
		$fields = array();

		$this->database->query('DESCRIBE ' . $table);
		$structure = $this->database->fetchAll();
		$this->database->freeResult();

		$properties[] = "protected \$table = '{$table}';";

		foreach ($structure as $data) {
			$field = $this->camelCase(
				$data['Field']
			);

			$type = $this->type(
				$data['Type']
			);

			if ($data['Key'] === 'PRI') {
				$properties[] = "
	protected \$primary = '{$data['Field']}';";
			}

			$fields[] = "'{$data['Field']}'";

			$methods[] = "
	/**
	 * @param {$type}|null \$value
	 * @return {$type}|{$entityName}
	 */
	public function {$field}(\$value = null) {
		if (\$value === null) {
			return ({$type})\$this->get('{$data['Field']}');
		}

		\$this->set('{$data['Field']}', ({$type})\$value);

		return \$this;
	}";
		}

		$fields = implode(',', $fields);
		$properties[] = "
	protected static \$fields = array({$fields});";

		$methods[] = "
	/**
	 * @param array \$parameters
	 * @return {$entityName}
	 * @throws InvalidArgumentException
	 */
	public static function create(array \$parameters) {
		\$fields = array_keys(\$parameters);
		foreach (\$fields as \$field) {
			if (!in_array(\$field, self::\$fields)) {
				\$message = \"Unknown field '{\$field}' for entity {$entityName}.\";
				throw new InvalidArgumentException(\$message);
			}
		}

		return parent::create(\$parameters);
	}";

		return
			implode('', $properties) . "\n" .
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