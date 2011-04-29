<?php

/** Wrapper around FirePHP
 */

class Amslib_FirePHP extends FirePHP
{
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