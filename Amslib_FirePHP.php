<?php

/** Wrapper around FirePHP
 */

class Amslib_FirePHP extends FirePHP
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public static function backtrace($levels)
	{
		//	NOTE: The array_shift gets rid of the first method (which is Amslib_FirePHP::backtrace)
		$backtrace = array_shift(debug_backtrace($levels));
		self::output("backtrace",$backtrace);
	}
	
	public static function output($name,$data){
		self::$instance->log($data,$name);
	}
	
	public static function init()
	{
		parent::init();
		
		$options = array(	'maxObjectDepth'		=>	5,
							'maxArrayDepth'			=>	10,
							'maxDepth'				=>	10,
							'useNativeJsonEncode'	=>	true,
							'includeLineNumbers'	=>	true);
		
		self::$instance->setOptions($options);
	}
}

Amslib_FirePHP::init();
