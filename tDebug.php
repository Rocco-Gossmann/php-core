<?php namespace rogoss\core;

trait tDebug {

	private static $__debugDisbaled = false;

	public static function setDebug($mode) {

		self::$__debugDisbaled = empty($mode);

	}

	protected static function toErrorLog(...$args) {
		if(self::$__debugDisbaled) return;
		$sPrefix = "[" . __CLASS__ . "]";
		foreach($args as $arg) {
			error_log($sPrefix . " => " . print_r($arg, true));
		}
	}

	protected static function toPrint(...$args) {
		if(self::$__debugDisbaled) return;
		echo "[" . __CLASS__ . "]";
		foreach($args as $arg)
			echo "\t => " , var_dump($arg, true), "\n";
	}
}
