<?php
class Amslib_SERVER extends Amslib_GLOBAL
{
	/**
	 * 	method:	has
	 *
	 * 	todo: write documentation
	 */
	static public function has($key)
	{
		return self::hasIndex($_SERVER,$key);
	}

	/**
	 *	function:	set
	 *
	 *	Insert a parameter into the global POST array
	 *
	 *	parameters:
	 *		$key	-	The parameter to insert
	 *		$value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	static public function set($key,$value)
	{
		return self::setIndex($_SERVER,$key,$value);
	}

	/**
	 * 	function:	get
	 *
	 * 	Obtain a parameter from the POST global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the POST global array, if not exists, the value of the parameter return
	 */
	static public function get($key,$default=NULL,$erase=false)
	{
		return self::getIndex($_SERVER,$key,$default,$erase);
	}

	static public function delete($key,$path)
	{
		return self::deleteIndex($_SERVER,$key);
	}
}