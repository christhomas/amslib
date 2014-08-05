<?php
class Amslib_POST extends Amslib_GLOBAL
{
	static public function has($key)
	{
		return self::hasIndex($_POST,$key);
	}

	static public function set($key,$value)
	{
		return self::setIndex($_POST,$key,$value);
	}

	static public function get($key,$default=NULL,$erase=false)
	{
		return self::getIndex($_POST,$key,$default,$erase);
	}

	static public function delete($key,$path)
	{
		return self::deleteIndex($_POST,$key);
	}
}