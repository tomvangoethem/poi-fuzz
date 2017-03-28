<?php

class Log {
	public static function logFuncCall($funcName) {
		global $startLogging;
		if ($startLogging) {
			// echo $funcName . "\n";
			$fh = fopen('func_call.log', 'a');
			fwrite($fh, $funcName."\n");
			fclose($fh);
		}
	}

	public function __construct() {Log::logFuncCall('__construct('.json_encode(func_get_args()).')');}
	public function __destruct() { Log::logFuncCall('__destruct()');}
	public function __get($name) { Log::logFuncCall("__get($name)"); return "Log";}
	public function __set($name, $value) { Log::logFuncCall("__set($name, value)");} 
	public function __isset($name) { Log::logFuncCall("__isset($name)"); return true;} 
	public function __unset($name) { Log::logFuncCall("__unset($name)");} 
	public function __sleep() { Log::logFuncCall("__sleep()"); return array();} 
	public function __wakeup() { Log::logFuncCall("__wakeup()");} 
	public function __toString() { Log::logFuncCall("__toString()"); return "Log";} 
	public function __invoke($a) { Log::logFuncCall("__invoke(". json_encode(func_get_args()).")");}
	public function __call($a, $b) { Log::logFuncCall("__call(". json_encode(func_get_args()).")");}
	public static function __callStatic($a, $b) { Log::logFuncCall("__callStatic(". json_encode(func_get_args()).")");}
	public static function __set_state($a) { Log::logFuncCall("__set_state(". json_encode(func_get_args()).")"); return null;}
	public function __clone() { Log::logFuncCall("__clone()");} 
}

class Foo {
	private $foo;

	public function __construct($foo) {
		$this->foo = $foo;
	}

	public function __set($name, $value) {
		$this->$name = $value;
	}
}