<?php
$path = dirname(__DIR__);

require_once("$path/Amslib.php");

$application = new Amslib_Plugin_Application("application",$path);
$application->setDebug(true);
$application->initialise();
$application->execute();