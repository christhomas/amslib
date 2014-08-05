<?php
class Amslib_SESSION extends Amslib_GLOBAL
{
	static public function has($key)
	{
		return self::hasIndex($_SESSION,$key);
	}

	static public function set($key,$value)
	{
		return self::setIndex($_SESSION,$key,$value);
	}

	static public function get($key,$default=NULL,$erase=false)
	{
		return self::getIndex($_SESSION,$key,$default,$erase);
	}

	static public function delete($key,$path)
	{
		return self::deleteIndex($_SESSION,$key);
	}
}