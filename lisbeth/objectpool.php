<?php

/**
 * Class instance holder class.
 */
class Lisbeth_ObjectPool {
	/**
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Return a class from object pool, using lazy initialisation.
	 *
	 * @param string $className
	 * @param int $parameter
	 * @return mixed instance of given $className
	 */
	public static function get($className, $parameter = null) {
		$parameter = (int)$parameter;

		if (!self::has($className, $parameter)) {
			$instance = (null === $parameter)
				? new $className()
				: new $className($parameter);

			self::set($instance, $className, $parameter);
		}

		return self::$instances[$className][$parameter];
	}

	/**
	 * @param $className
	 * @return array
	 */
	public static function classes($className) {
		if (array_key_exists($className, self::$instances)) {
			return self::$instances[$className];
		}

		return array();
	}

	/**
	 * @static
	 * @param string $className
	 * @param int $parameter
	 * @return bool
	 */
	public static function has($className, $parameter = null) {
		return isset(self::$instances[$className][$parameter]);
	}

	/**
	 * @static
	 * @param $instance
	 * @param $className
	 * @param null $parameter
	 */
	public static function set($instance, $className, $parameter = null) {
		self::$instances[$className][$parameter] = $instance;
	}

	public static function clear() {
		self::$instances = array();
	}
}