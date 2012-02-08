<?php
class Amslib_Array2
{
	static public function valid($array=NULL)
	{
		//	Invalid values return an empty array
		if(empty($array) || !$array || !is_array($array) || is_null($array)) return array();
		//	cast objects to arrays
		if(is_object($array)) $array = (array)$a;
		//	return the original value
		return $array;
	}

	static public function min($array,$key,$returnKey=NULL)
	{
		$min = NULL;

		foreach(self::valid($array) as $item){
			if($min === NULL) $min = $item;

			if($item[$key] < $min[$key]) $min = $item;
		}

		return $returnKey !== NULL && isset($min[$returnKey]) ? $min[$returnKey] : $min;
	}

	static public function max($array,$key,$returnKey=NULL)
	{
		$max = NULL;

		foreach(self::valid($array) as $item){
			if($max === NULL) $max = $item;

			if($item[$key] > $max[$key]) $max = $item;
		}

		return $returnKey !== NULL && isset($max[$returnKey]) ? $max[$returnKey] : $max;
	}

	static public function sort($array,$index)
	{
		if(count($array) < 2) return $array;

		$left = $right = array();

		reset($array);
		$pivot_key = key($array);
		$pivot = array_shift($array);

		foreach($array as $k => $v) {
			if($v[$index] < $pivot[$index])
				$left[$k] = $v;
			else
				$right[$k] = $v;
		}

		return array_merge(self::sort($left,$index), array($pivot_key => $pivot), self::sort($right,$index));
	}
}