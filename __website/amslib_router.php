<?php
$path = $search = dirname(__DIR__);

do{
	$file = "$search/vendor/autoload.php";

	if(file_exists($file)){
		require_once($file);
		break;
	}else if(!strlen($search)){
		break;
	}
}while($search = rtrim(dirname($search),"/"));

$application = new Amslib_Plugin_Application("application",$path);
$application->setDebug(true);
$application->initialise();
$application->execute();