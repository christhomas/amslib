<?php

/** Wrapper around FirePHP
 */

class Amslib_FirePHP extends FirePHP
{
	public function __construct()
	{
		parent::__construct();
	}

	public static function backtrace($levels=false)
	{
		$e = new Exception();

		$trace = (is_numeric($levels) && $levels > 0)
			? array_slice($e->getTrace(),0,(int)$levels)
			: $e->getTrace();

		self::output("backtrace",$trace);
	}

	public static function output($name,$data)
	{
		$a = func_get_args();
		$name = array_shift($a);
		try{
			self::$instance->log($a,$name);
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
