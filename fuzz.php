<?php

$phpLocation = 'php';

function func() { }
function gen() { yield 1; }
date_default_timezone_set('UTC');

// Uncaught exception 'ReflectionException' with message 'Zend Extension PDO does not exist'
// Uncaught exception 'UnexpectedValueException' with message 'creating archive "/tmp/poi.phar" disabled by the php.ini setting phar.readonly'
// $p = new Phar('/tmp/poi.phar', 0, 'poi.phar');
// $p['testfile.txt'] = "hi\nthere\ndude";
$excluded = ["Generator", "PDORow", "Phar", "PharFileInfo", "ReflectionZendExtension", "mysqli", "mysqli_result", "mysqli_stmt", "Log", "Foo"];
// 'Serialization of '<class>' is not allowed'
$excluded = array_merge($excluded, ["SplFileInfo", "DirectoryIterator", "FilesystemIterator", "RecursiveDirectoryIterator", "GlobIterator", "SplFileObject", "SplTempFileObject", "PDO", "PDOStatement", "PharData", "SimpleXMLElement", "SimpleXMLIterator"]);

$startLogging = false;

require_once('common.php');

function fuzzClass($className) {
	$properties = [];

	global $excluded;
	global $startLogging;
	global $phpLocation;
	
	$gen = gen();

	$start = new DateTime('2012-07-01');
	$interval = new DateInterval('P7D');
	$end = new DateTime('2012-07-31');
	$dom = new DOMDocument('1.0', 'iso-8859-1');
	$rai = new RecursiveArrayIterator([]);

	$fh = fopen('/tmp/poi.empty', 'w');
	fwrite($fh, '');
	fclose($fh);

	@unlink('/tmp/poi.new');

	$serializedLogObj = serialize(new Log());

	$specialConstructElements = [
		"ReflectionClass" => ["DateTime"],
		"DateTimeZone" => ["UTC"],
		"DateInterval" => ["P7D"],
		"DatePeriod" => [$start, $interval, $end, DatePeriod::EXCLUDE_START_DATE],
		"SQLite3" => ["/tmp/poi.empty"],
		"DOMAttr" => ["HTML", "TEST"],
		"DOMElement" => ['pr:node1', 'thisvalue', 'http://xyz'],
		"DOMEntityReference" => ['nbsp'],
		"DOMProcessingInstruction" => ['php', 'echo "Hello World"; '],
		"DOMXPath" => [$dom],
		"RecursiveIteratorIterator" => [$rai],
		"IteratorIterator" => [$rai],
		"CallbackFilterIterator" => [$rai, 'func'],
		"RecursiveCallbackFilterIterator" => [$rai, 'func'],
		"ParentIterator" => [$rai],
		"LimitIterator" => [$rai],
		"CachingIterator" => [$rai],
		"RecursiveCachingIterator" => [$rai],
		"NoRewindIterator" => [$rai],
		"InfiniteIterator" => [$rai],
		"RegexIterator" => [$rai, '//', RegexIterator::REPLACE],
		"RecursiveRegexIterator" => [$rai, '//', RegexIterator::REPLACE],
		"RecursiveTreeIterator" => [$rai, RecursiveTreeIterator::BYPASS_KEY, CachingIterator::CATCH_GET_CHILD, RecursiveIteratorIterator::SELF_FIRST],
		"ArrayObject" => [[]],
		"ArrayIterator" => [[]],
		"RecursiveArrayIterator" => [[]],
		"DirectoryIterator" => ["/tmp/"],
		"FilesystemIterator" => ["/tmp/"],
		"RecursiveDirectoryIterator" => ["/tmp/"],
		"GlobIterator" => ["/tmp/"],
		"SplFileObject" => ["/tmp/poi.empty"],
		"PDO" => ["sqlite:/tmp/poi.empty"],
		"PharData" => ["/tmp/poi.new"],
		"ReflectionFunction" => ["preg_replace"],
		"ReflectionParameter" => ["preg_replace", "regex"],
		"ReflectionMethod" => ["DateTime", "__construct"],
		"ReflectionProperty" => ["Exception", "message"],
		"ReflectionExtension" => ["zlib"],
		"ReflectionZendExtension" => ["PDO"],
		"SimpleXMLElement" => ["<xml><custom_property /></xml>"],
		"SimpleXMLIterator" => ["<xml><custom_property /></xml>"],
		"SoapClient" => [null, array("encoding"=>"ISO-8859-1", "uri" => "http://localhost/", "location" => "foo")], // TODO: we want to enable WSDL here...
		"SoapServer" => [null, array("uri" => "http://localhost/")],
		"ReflectionObject" => [$start],
		"SoapParam" => [null, 'param'],
		"SoapHeader" => ['foo', 'bar'],
		"ReflectionGenerator" => [$gen],
		"Exception" => ["msg", 1, new Exception('foo', 2, new Exception('bar'))],
		"ReflectionClassConstant" => ['DateTime', 'ATOM'],
	];

	$reflectionClass = new ReflectionClass($className);
	// $refProperties = $reflectionClass->getProperties();

	// foreach ($refProperties as $refProperty) {
	// 	$properties[] = ["name" => $refProperty->name, "private" => $refProperty->isPrivate(), "protected" => $refProperty->isProtected(), "public" => $refProperty->isPublic()];
	// }

	if ($reflectionClass->isInstantiable()) {
		echo "--- $className\n--------------------------\n";

		$args = [];

		if (array_key_exists($className, $specialConstructElements)) {
			$args = $specialConstructElements[$className];
		}

		else {
			$constructor = $reflectionClass->getConstructor();
			if (! is_null($constructor)) {
				$numParams = $constructor->getNumberOfParameters();
				if ($numParams > 0) {
					$args = array_fill(0, $numParams, null);
				}
			}
		}
		$obj = $reflectionClass->newInstanceArgs($args);

		// $reflectionObject = new ReflectionObject($obj);
		// $refProperties = $reflectionObject->getProperties();
		// foreach ($refProperties as $refProperty) {
		// 	if (count(array_filter($properties, function($prop) { 
		// 		global $refProperty;
		// 		return !is_null($refProperty) && $refProperty->name == $prop['name'];
		// 	})) == 0) {
		// 		$properties[] = ["name" => $refProperty->name, "private" => $refProperty->isPrivate(), "protected" => $refProperty->isProtected(), "public" => $refProperty->isPublic()];
		// 	}
		// }

		$serialized = serialize($obj);
		$startLogging = true;

		try {
			foreach (replaceSerializedProperties($serialized, $serializedLogObj) as $unserializeAttempt) {
				// echo "$unserializeAttempt\n";
				@unlink('func_call.log');
				$fh = fopen('in.ser', 'w');
				fwrite($fh, $unserializeAttempt);
				fclose($fh);
				shell_exec(PHP_BINDIR . "/php try-unserialize.php");
				$result = file_get_contents('func_call.log');
				echo $result;
				if (strpos($result, '__toString') !== false) {
					echo "+++ Found __toString in $unserializeAttempt\n";
				}
				elseif (strpos($result, '__call') !== false) {
					echo "+++ Found __call in $unserializeAttempt\n";
				}
			}
		} catch (Exception $e) {
			echo "Caught exception while unserializing... " . $e->getMessage() . "\n";
		} catch (Error $e) {
			echo "Caught exception while unserializing... " . $e->getMessage() . "\n";
		}

		@unlink('func_call.log');
		@unlink('in.ser');

		$startLogging = false;
	}
	return null;
}

function replaceSerializedProperties($serialized, $replacement) {
	$results = [];

	list($objectType, $classNameLength, $rest) = explode(':', $serialized, 3);
	$className = substr($rest, 0, intval($classNameLength) + strlen('""'));
	if ($objectType === 'C') {
		echo "--- Skipping class $className since it uses custom serialization. ($serialized)\n";
		return [];
	}

	$rest = substr($rest, intval($classNameLength) + strlen('""') + strlen(':'));
	list($numProperties, $rest) = explode(':', $rest, 2);

	$headerLength = strlen($objectType) + strlen(':') + strlen($classNameLength) + strlen(':') + strlen($className) + strlen(':') + strlen($numProperties) + strlen(':');

	$foundProperties = 0;
	$pointer = 1;

	while ($foundProperties < intval($numProperties)) {
		switch ($rest[$pointer]) {
			case 's':
				$propertyName = getSerializedStringValue(substr($rest, $pointer));
				// echo "Replacing object property \"$propertyName\"\n";
				$pointer += getSerializedStringLength(substr($rest, $pointer));
				break;
			default:
				var_dump($rest);
				throw new Exception("Unhandled property type... " . $rest[$pointer], 1);
				break;
		}
		$endOfProperty = $pointer + getSerializedElementLength(substr($rest, $pointer));
		$results[] = substr($serialized, 0, $headerLength + $pointer) . $replacement . substr($serialized, $headerLength + $endOfProperty);

		$pointer += getSerializedElementLength(substr($rest, $pointer));
		$foundProperties += 1;
	}
	return $results;
}

function getSerializedElementLength($serialized) {
	switch ($serialized[0]) {
		case 'i':
		case 'b':
		case 'd':
		case 'N':
		case 'r':
		case 'R':
			list($propertyValue, $_) = explode(';', $serialized, 2);
			return strlen($propertyValue) + 1;
			break;
		case 's':
			return getSerializedStringLength($serialized);
			break;
		case 'a':
			return getSerializedArrayLength($serialized);
			break;
		case 'O':
			return getSerializedObjectLength($serialized);
		case 'C':
			return getSerializedCustomLength($serialized);
		default:
			var_dump($serialized);
			throw new Exception("Unhandled property type... " . $serialized[0], 1);
			break;
	}
}

function getSerializedCustomLength($serialized) {
	// C:11:"ArrayObject":21:{x:i:0;a:0:{};m:a:0:{}}
	list($customType, $classNameLength, $rest) = explode(':', $serialized, 3);
	$className = substr($rest, 0, intval($classNameLength) + 2);
	$rest = substr($rest, intval($classNameLength) + 2 + 1);
	list($serializedLength, $rest) = explode(':', 2);
	return strlen($customType) + 1 + strlen($classNameLength) + 1 + strlen($className) + 1 + strlen($serializedLength) + 1 + intval($serializedLength) + strlen('{}');
}

function getSerializedObjectLength($serialized) {
	list($objectType, $classNameLength, $rest) = explode(':', $serialized, 3);
	$className = substr($rest, 0, intval($classNameLength) + 2); // quoted
	$rest = substr($rest, intval($classNameLength) + 2 + 1);
	list($numProperties, $rest) = explode(':', $rest, 2);
	$foundProperties = 0;
	$pointer = 1;
	while ($foundProperties < intval($numProperties)) {
		switch ($rest[$pointer]) {
			case 's':
				$pointer += getSerializedStringLength(substr($rest, $pointer));
				break;
			default:
				var_dump($rest);
				throw new Exception("Unhandled property type... " . $rest[$pointer], 1);
				break;
		}
		$pointer += getSerializedElementLength(substr($rest, $pointer));
		$foundProperties += 1;
	}
	if ($rest[$pointer] !== '}') {
		throw new Exception("Failed to get object length", 1);
	}
	return strlen($objectType) + 1 + strlen($classNameLength) + 1 + strlen($className) + 1 + strlen($numProperties) + 1 + $pointer + strlen("}");
}

function getSerializedArrayLength($serialized) {
	list($arrayType, $numElements, $rest) = explode(':', $serialized, 3);
	if (intval($numElements) === 0) {
		return strlen($arrayType) + 1 + strlen($numElements) + 1 + strlen("{}"); // {} → 2
	}
	$foundElements = 0;
	$pointer = 1;
	while ($foundElements < intval($numElements)) {
		switch ($rest[$pointer]) {
			case 'i':
				list($arrayIndexSerialized, $_) = explode(';', substr($rest, $pointer), 2);
				$pointer += strlen($arrayIndexSerialized) + 1;
				break;
			case 's':
				$pointer += getSerializedStringLength(substr($rest, $pointer));
				break;
			default:
				var_dump($rest);
				throw new Exception("Unhandled property type... " . $rest[$pointer], 1);
				break;
		}
		$pointer += getSerializedElementLength(substr($rest, $pointer));

		$foundElements += 1;
	}
	if ($rest[$pointer] !== '}') {
		throw new Exception("Failed to get array length", 1);
	}
	return strlen($arrayType) + 1 + strlen($numElements) + 1 + $pointer + strlen("}");
}

function getSerializedStringLength($serialized) {
	list($stringType, $stringLength, $_) = explode(':', $serialized, 3);
	return strlen($stringType) + 1 + strlen($stringLength) + 1 + intval($stringLength) + strlen('"";');
}

function getSerializedStringValue($serialized) {
	list($stringType, $stringLength, $_) = explode(':', $serialized, 3);
	return substr($serialized, strlen($stringType) + strlen(':') + strlen($stringLength) + strlen(':"'), intval($stringLength));
}

function assertEqual($value, $expected) {
	if ($value !== $expected) {
		throw new Exception("Value ($value) is not what was expected ($expected)", 1);
		
	}
}

$s = serialize("f;oo:");
assertEqual(getSerializedStringLength($s), strlen($s));

$s = serialize([]);
assertEqual(getSerializedArrayLength($s), strlen($s));

$s = serialize([1, 2, 3]);
assertEqual(getSerializedArrayLength($s), strlen($s));

$s = serialize(["1", "2", "3"]);
assertEqual(getSerializedArrayLength($s), strlen($s));

$s = serialize(["o;1:", "o:2;", "3:;"]);
assertEqual(getSerializedArrayLength($s), strlen($s));

$s = serialize(["o;1:", 300 => "o:2;", "3:;"]);
assertEqual(getSerializedArrayLength($s), strlen($s));

$s = serialize(["o;1:", "f;a:;s" => "o:2;", "3:;"]);
assertEqual(getSerializedArrayLength($s), strlen($s));

$s = serialize(["o;1:", "f;a:;s" => ["o:2;", "3:;"]]);
assertEqual(getSerializedArrayLength($s), strlen($s));

$s = serialize(new Foo(123));
assertEqual(getSerializedObjectLength($s), strlen($s));

$s = serialize(new Foo([1, 2, 3]));
assertEqual(getSerializedObjectLength($s), strlen($s));

$s = serialize(new Foo([new Foo("foo")]));
assertEqual(getSerializedObjectLength($s), strlen($s));

// $f = new Foo("a");
// $f->bar = "b";
// $s = serialize($f);
// var_dump($s);
// var_dump(replaceSerializedProperties($s, serialize("TEST")));


$allClasses = get_declared_classes();

foreach ($allClasses as $className) {
	if (!in_array($className, $excluded)) {
		fuzzClass($className);
	}
}