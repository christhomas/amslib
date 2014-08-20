<?php
class Amslib_Global
{
	static public function hasIndex($source,$key)
	{
		return (isset($source[$key])) ? true : false;
	}

	/**
	 *	function:	setIndex
	 *
	 *	Insert a parameter into the specified global array
	 *
	 *	parameters:
	 *		$key	-	The parameter to insert
	 *		$value	-	The value of the parameter being inserted
	 */
	static public function setIndex(&$source,$key,$value)
	{
		$source[$key] = $value;

		return $source[$key];
	}

	/**
	 * 	function:	getIndex
	 *
	 * 	Obtain a parameter from the specifed global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the specified global array, if not exists, the value of the parameter return
	 */
	static public function getIndex(&$source,$key,$default=NULL,$erase=false)
	{
		if(is_array($key)){
			$k = array_shift(array_intersect($key,array_keys($source)));
			return self::getIndex($source,$k,$default,$erase);
		}

		if(isset($source[$key])){
			$default = $source[$key];
			if($erase) self::deleteIndex($source,$key);
		}

		return $default;
	}

	static public function deleteIndex(&$source,$key)
	{
		$key_list = func_get_args();
		//	get rid of the first item because it's the source array
		array_shift($key_list);

		foreach($key_list as $item){
			if(!is_array($item)){
				$item = array($item);
			}

			foreach($item as $k){
				if(!isset($source[$k])) continue;

				$source[$k] = NULL;

				unset($source[$k]);
			}
		}

		return self::getIndex($source,$key);
	}
}