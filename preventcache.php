<?php
//	For this to work, this header must be included before any output is sent
//	to the browser, just like any other header that needs to be sent
//header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Cache-Control: no-cache");
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");		// Date in the past

//	Enable all error reporting
ini_set("display_errors", "On");
error_reporting(E_ALL);
?>
