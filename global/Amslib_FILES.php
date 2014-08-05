<?php
class Amslib_FILES extends Amslib_GLOBAL
{
	static public function has($key)
	{
		return self::hasIndex($_FILES,$key);
	}

	static public function set($key,$value)
	{
		return self::setIndex($_FILES,$key,$value);
	}

	static public function get($key,$default=NULL,$erase=false)
	{
		return self::getIndex($_FILES,$key,$default,$erase);
	}

	static public function delete($key,$path)
	{
		return self::deleteIndex($_FILES,$key);
	}
}