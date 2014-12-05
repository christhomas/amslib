<?php
//	NOTE:	this version of this script is customised to allow the website to run
//			out of a subdirectory and not the root folder where the framework is

@session_start();

$path	=	dirname(__DIR__);
$amslib	=	$path;

require_once("$amslib/Amslib.php");

//	Unfortunately, the amslib internal website cannot execute itself for the error handling, circular loop
//Amslib::shutdown(Amslib_File::relative("$amslib/error/500/"));
Amslib_Debug::enable(true);
//	This is needed so non-routed-services will work without modification
Amslib_Router::initialise();
//	NOTE: I think that this method is redundant and the system should do it for me
Amslib_Website::set();
//	The minimal amount of information we need to manually provide to get the system working
Amslib::addIncludePath("$path/__website/objects");
//	This will attempt to load any customisations we need to run PER PROJECT before we run this boilerplate code.
Amslib::includeFile("$path/__website/amslib_customise.php");

$application = new Amslib_Plugin_Application("application",$path);
$application->execute();