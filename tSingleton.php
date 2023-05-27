<?php namespace de\rogoss\php\core;

trait tSingleton {
	private static $__instance = null;

	/**
 	 * @return static	
	 */
	public static function get() {
		if(self::$__instance === null)
			self::$__instance = new static();

		return self::$__instance;
	}
}
