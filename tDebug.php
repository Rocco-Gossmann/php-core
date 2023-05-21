<?php namespace de\roccogossmann\php\core;

trait tDebug {

	private static $__debugmode = false;

	public static function setDebug($mode) {
		self::$__debugmode = $mode != false;
	}

	public static function toErrorLog(...$args) {
		if(self::$__debugmode == false) return;

		$sPrefix = "[" . __CLASS__ . "]";
		foreach($args as $arg) {
			error_log($sPrefix . " => " . print_r($arg, true));
		}
	}

	public static function toPrint(...$args) {
		if(self::$__debugmode == false) return;

		echo "[" . __CLASS__ . "]";
		foreach($args as $arg) 
			echo "\t => " , var_dump($arg, true), "\n";
	}
}
