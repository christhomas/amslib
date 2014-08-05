<?php
class Amslib_GET extends Amslib_GLOBAL
{
	static public function has($key)
	{
		return self::hasIndex($_GET,$key);
	}

	static public function set($key,$value)
	{
		return self::setIndex($_GET,$key,$value);
	}

	static public function get($key,$default=NULL,$erase=false)
	{
		return self::getIndex($_GET,$key,$default,$erase);
	}

	static public function delete($key,$path)
	{
		return self::deleteIndex($_GET,$key);
	}
}