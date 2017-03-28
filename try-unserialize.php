<?php

require_once('common.php');

register_shutdown_function( "fatal_handler" );

function fatal_handler() {
	$error = error_get_last();
	if (! is_null($error)) {
		$fh = fopen('func_call.log', 'a');
		fwrite($fh, '--- Caught error: ' . str_replace("\n", "\n--- ", $error["message"]) . " ---\n");
		fclose($fh);
	}
}

$serialized = file_get_contents('in.ser');
$startLogging = true;

$_1 = unserialize($serialized);
$_2 = (string)unserialize($serialized);

