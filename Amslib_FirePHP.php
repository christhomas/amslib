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
		//	NOTE: The array_slide gets rid of the first method (which is Amslib_FirePHP::backtrace)
		self::output("backtrace",array_slice(debug_backtrace($levels),1,$levels));
	}
	
	public static function output($name,$data){
		try{
			self::$instance->log($data,$name);
		}catch(Exception $e){
			print("Amslib_FirePHP::output(), exception occured, output has already started? backtrace = ".Amslib::var_dump(debug_backtrace(),true));
		}
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
