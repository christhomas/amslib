<?php
class Amslib_SESSION extends Amslib_GLOBAL
{
	/**
	 * 	method:	has
	 *
	 * 	todo: write documentation
	 */
	static public function has($key)
	{
		return self::hasIndex($_SESSION,$key);
	}

	/**
	 * 	method:	set
	 *
	 * 	todo: write documentation
	 */
	static public function set($key,$value)
	{
		return self::setIndex($_SESSION,$key,$value);
	}

	/**
	 * 	function:	get
	 *
	 * 	Obtain a parameter from the SESSION global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the SESSION global array, if not exists, the value of the parameter return
	 */
	static public function get($key,$default=NULL,$erase=false)
	{
		return self::getIndex($_SESSION,$key,$default,$erase);
	}

	/**
	 * 	method:	delete
	 *
	 * 	todo: write documentation
	 */
	static public function delete($key)
	{
		return self::deleteIndex($_SESSION,$key);
	}

	static public function start()
	{
		@session_start();
	}

	static public function stop()
	{
		@session_write_close();
	}

	static public function destroy()
	{
		@session_destroy();
	}

	static public function reset()
	{
		self::start();
		self::destroy();
	}
	
	static public function dump()
	{
		return parent::dump($_SESSION);
	}
}