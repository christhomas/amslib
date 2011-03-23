<?php

/** Wrapper around FirePHP
 */

class Amslib_FirePHP extends FirePHP
{
	public static function output($name,$data){
		self::$instance->log($data,$name);
	}
}

Amslib_FirePHP::init();