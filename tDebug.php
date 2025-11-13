<?php namespace rogoss\core;

trait tDebug {

	private static $__debugToError = __CLASS__ . "::_debugNoop";
	private static $__debugToEcho = __CLASS__ . "::_debugNoop";

	public static function setDebug($mode) {

		if(empty($mode))  {
			self::$__debugToEcho = __CLASS__ . "::_debugNoop";
			self::$__debugToError = __CLASS__ . "::_debugNoop";
		}
		else {
			self::$__debugToEcho = __CLASS__ . "::_toPrint";
			self::$__debugToError = __CLASS__ . "::_toErrorLog";
		}

	}


	private static function toErrorLog(...$args) { (self::$__debugToError)($args); }
	private static function toPrint(...$args) { (self::$__debugToEcho)($args); }

	private static function _debugNoop() {}
	private static function _toErrorLog($args) {
		$sPrefix = "[" . __CLASS__ . "]";
		foreach($args as $arg) {
			error_log($sPrefix . " => " . print_r($arg, true));
		}
	}
	private static function _toPrint($args) {
		echo "[" . __CLASS__ . "]";
		foreach($args as $arg)
			echo "\t => " , var_dump($arg, true), "\n";
	}
}
