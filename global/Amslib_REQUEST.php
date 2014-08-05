<?php
class Amslib_REQUEST extends Amslib_GLOBAL
{
	static public function has($key)
	{
		return self::hasIndex($_REQUEST,$key);
	}

	static public function set($key,$value)
	{
		return self::setIndex($_REQUEST,$key,$value);
	}

	static public function get($key,$default=NULL,$erase=false)
	{
		return self::getIndex($_REQUEST,$key,$default,$erase);
	}

	static public function delete($key,$path)
	{
		return self::deleteIndex($_REQUEST,$key);
	}
}