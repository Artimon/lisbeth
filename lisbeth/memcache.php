<?php

/**
 * Lisbeth_Memcache
 */
class Lisbeth_Memcache {
	/**
	 * @var bool
	 */
	private static $connected = false;

	/**
	 * @var Memcache
	 */
	private static $memcache;

	/**
	 * @var array
	 */
	private static $stats;

	public function __construct() {
		self::$stats = array(
			'set' => 0,
			'get' => 0
		);
	}

	public function __destruct() {
		$this->disconnect();
	}

	/**
	 * @return Lisbeth_Memcache
	 */
	public static function getSingleton() {
		return Lisbeth_ObjectPool::get('Lisbeth_Memcache');
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @return bool
	 */
	public function connect($host, $port) {
		if (class_exists('Memcache', false)) {
			self::$memcache = new Memcache();
			self::$connected = self::$memcache->connect($host, $port);
		}

		return $this->isConnected();
	}

	public function disconnect() {
		if ($this->isConnected()) {
			self::$connected = false;

			$this->memcache()->close();
		}
	}

	/**
	 * @return int
	 */
	public function performedSets() {
		return self::$stats['set'];
	}

	/**
	 * @return int
	 */
	public function performedGets() {
		return self::$stats['get'];
	}

	/**
	 * @return bool
	 */
	public function isConnected() {
		return (false !== self::$connected);
	}

	/**
	 * @return Memcache
	 */
	public function memcache() {
		return self::$memcache;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire
	 * @return Lisbeth_Memcache
	 */
	public function set($key, $value, $expire = 3600) {
		self::$stats['set']++;

		if ($this->isConnected()) {
			$expire = $this->sanitizeExpire($expire);
			$this->memcache()->set($key, $value, false, $expire);
		}

		return $this;
	}

	/**
	 * @param string|array $key
	 * @return array|bool|string
	 */
	public function get($key) {
		self::$stats['get']++;

		if ($this->isConnected()) {
			return $this->memcache()->get($key);
		}

		return false;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire
	 * @return Lisbeth_Memcache
	 */
	public function replace($key, $value, $expire = 3600) {
		self::$stats['set']++;

		if ($this->isConnected()) {
			$this->memcache()->replace($key, $value, false, $expire);
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function delete($key) {
		if ($this->isConnected()) {
			$this->memcache()->delete($key);
		}

		return $this;
	}

	public function flush() {
		if ($this->isConnected()) {
			$this->memcache()->flush();
		}
	}

	/**
	 * @return bool|array
	 */
	public function stats() {
		if ($this->isConnected()) {
			return $this->memcache()->getstats();
		}

		return false;
	}

	/**
	 * @param int $expire
	 * @return int
	 */
	protected function sanitizeExpire($expire) {
		return max(1, (int)$expire);
	}
}