<?php
$path	=	__DIR__;
$amslib	=	"$path/amslib";

require_once("$amslib/Amslib.php");

$application = new Amslib_Plugin_Application("application",$path);
$application->setShutdown(Amslib_File::relative("$amslib/error/500/"));
$application->setDebug(isset($_SERVER["ENABLE_DEBUG"]) && $_SERVER["ENABLE_DEBUG"]);
$application->initialise();
$application->execute();