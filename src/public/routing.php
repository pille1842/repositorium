<?php
if (file_exists(__DIR__ . '/' . $_SERVER['REQUEST_URI'])) {
	return false; // serve the requested resource as-is.
} else {
	$_SERVER['SCRIPT_NAME'] = 'index.php';
	include 'index.php';
}