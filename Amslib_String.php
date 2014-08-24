<?php

class Amslib_String
{
	/**
	 * 	method:	reduceSlashes
	 *
	 * 	Reduce the consecutive slashes in a string to a single item, /=>/, //=>/, ///=>/, etc
	 *
	 * 	params:
	 * 		$string	-	The string to process
	 *
	 * 	returns:
	 * 		A string with all the slashes reduced
	 *
	 * 	notes:
	 * 		If the string is a URL beginning with http://, use Amslib_Website::reduceSlashes instead
	 */
	static public function reduceSlashes($string)
	{
		return preg_replace('#//+#','/',$string);
	}

	/**
	 * 	method:	stripComments
	 *
	 * 	Remove all comments from a string, it might not be perfect
	 *
	 * 	params:
	 * 		$string	-	The string to process
	 *
	 * 	returns:
	 * 		A string without comments
	 *
	 * 	notes:
	 * 		-	I got some of this code originally from: http://stackoverflow.com/a/1581063/279147
	 */
	static public function stripComments($string)
	{
		if(!is_string($string)){
			Amslib::errorLog(__METHOD__,"Attempting to strip comments from something that is not a string");
			return false;
		}

		$string = preg_replace('#<!--[^\[<>].*?(?<!!)-->#s', '', $string);

		$regex = array(
				"`^([\t\s]+)`ism"=>'',
				"`\/\*(.+?)\*\/`ism"=>"",
				"`([\n\A;]+)\/\*(.+?)\*\/`ism"=>"$1",
				"`([\n\A;\s]+)//(.+?)[\n\r]`ism"=>"$1\n",
				"`(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+`ism"=>"\n"
		);
		$string = preg_replace(array_keys($regex),$regex,$string);

		return $string;
	}
}