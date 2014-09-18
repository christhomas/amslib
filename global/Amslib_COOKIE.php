<?php
class Amslib_COOKIE extends Amslib_GLOBAL
{
	/**
	 * 	method:	has
	 *
	 * 	todo: write documentation
	 */
	static public function has($key)
	{
		return self::hasIndex($_COOKIE,$key);
	}

	/**
	 * 	method:	set
	 *
	 * 	todo: write documentation
	 */
	static public function set($key,$value,$expire_days,$path,$hash=true)
	{
		$value = $hash ? Amslib::getRandomCode($value) : $value;

		$expire_days = intval($expire_days);

		if(!$expire_days) $expire_days = 30;

		setcookie($key,$value,time()+(60*60*24*$expire_days),$path);

		return $value;
	}

	/**
	 * 	method:	get
	 *
	 * 	todo: write documentation
	 */
	static public function get($key,$default=NULL,$erase=false)
	{
		return self::getIndex($_COOKIE,$key,$default,$erase);
	}

	static public function delete($key,$path)
	{
		setcookie($key,"",1,$path);
	}
}