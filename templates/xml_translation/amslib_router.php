<?php
@session_start();

$path = dirname(__FILE__);

require_once("$path/amslib/Amslib.php");
Amslib::autoloader();
//	This shutdown call has to be more or less static and touching as little code as possible
//	NOTE: the reason for this is, touching more code means touching ore potential errors
//	NOTE: if you touch an error whilst trying to setup the shutdown function, maybe you'll never get to see the error page
#Amslib::shutdown(Amslib_File::relative(dirname(__FILE__)."/500/"));
Amslib::showErrors(true);
//	This is needed so non-routed-services will work without modification
Amslib_Router::initialise();
//	NOTE: I think that this method is redundant and the system should do it for me
Amslib_Website::set();
//	The minimal amount of information we need to manually provide to get the system working
Amslib::addIncludePath("$path/objects");
//	This will attempt to load any customisations we need to run PER PROJECT before we run this boilerplate code.
Amslib::includeFile("$path/amslib_customise.php");

$application = new Amslib_Plugin_Application("application",$path);
$application->execute();