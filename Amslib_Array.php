<?php
class Amslib_Array
{
	static public function valid($array=NULL)
	{
		//	Invalid values return an empty array
		if(empty($array) || !$array || !is_array($array) || is_null($array)) return array();
		//	cast objects to arrays
		if(is_object($array)) return (array)$a;
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
	
	static public function pluck($array,$key)
	{
		if(!is_array($array) || !self::isMulti($array)) return false;
		
		$values = array();
		
		foreach(self::valid($array) as $item){
			if(isset($item[$key])) $values[] = $item[$key];
		}
		
		return $values;
	}
	
	static public function removeValue(array $array,$value, $strict=false)
	{
	    return array_diff_key($array, array_flip(array_keys($array, $value, $strict)));
	}
	
	static public function filter($array,$key,$value,$returnFiltered=false,$similar=false)
	{
		$filter = array();

		foreach(self::valid($array) as $k=>$v)
		{
			$found = false;
			
			//	TODO: I'm sure that there are more situations I could take into account here
			
			if(is_array($value) && in_array($v[$key],$value)) $found = true;
			if($v[$key] == $value) $found = true;
			if($similar == true && strpos($v[$key],$value) !== false) $found = true;
			
			if($found){
				$filter[$k] = $v;
				unset($array[$k]);
			}
		}

		return $returnFiltered ? $filter : $array;
	}
	
	static public function countValues($array)
	{
		$counts = array();
		
		foreach(self::valid($array) as $v){
			if(!isset($counts[$v])) $counts[$v] = 0;
			
			$counts[$v]++;
		}
		
		return $counts;
	}

	static public function find($array,$key,$value)
	{
		foreach(self::valid($array) as $a){
			if($a[$key] == $value) return $a;
		}

		return false;
	}
	
	static public function findKey($array,$key,$value)
	{
		foreach(self::valid($array) as $k=>$a){
			if($a[$key] == $value) return $k;
		}

		return false;
	}

	static public function searchKeys($array,$filter)
	{
		$matches = array();

		foreach(self::valid($array) as $k=>$v){
			if(strpos($k,$filter) !== false) $matches[$k] = $v;
		}

		return $matches;
	}
	
	static public function hasKeys($array,$keys)
	{
		if(!is_array($array)) return false;
		
		if(!is_array($keys)) $keys = array($keys);
		
		foreach($keys as $k){
			if(!isset($array[$k])) return false;
		}
		
		return true;
	}

	//	NOTE: I am not sure of the consequences of defaulting key="" will break anything
	static public function stripSlashesMulti($array,$key="")
	{
		if($key == "") $key = array_keys($array);

		if(!is_array($key)){
			foreach($array as &$element){
				$element[$key] = stripslashes($element[$key]);
			}
		}else{
			foreach($array as &$element){
				foreach($key as $index){
					$element[$index] = stripslashes($element[$index]);
				}
			}
		}

		return $array;
	}

	static public function stripSlashesSingle($array,$key="")
	{
		if($key == "") $key = array_keys($array);

		if(!is_array($key)){
			$array[$key] = stripslashes($array[$key]);
		}else{
			foreach($key as $index){
				$array[$index] = stripslashes($array[$index]);
			}
		}
		return $array;
	}

	static public function stripSlashes($array,$key="")
	{
		if(!is_array($array) || empty($array)) return $array;

		if($key == "") $key = array_keys($array);

		return (self::isMulti($array)) ?
					self::stripSlashesMulti($array,$key) :
					self::stripSlashesSingle($array,$key);
	}

	static public function isMulti($array)
	{
		return count($array)!==count($array, COUNT_RECURSIVE);
	}
	
	//	FIXME: glob() on an array object? when it refers to the filesystem or array? I think it's a mistake to put this method here
	static public function glob($location,$relative=false)
	{
		$items = glob(Amslib_Website::abs($location));
		
		if($relative){
			foreach($items as &$i){
				$i = Amslib_Website::rel($i);
			}
		}
		
		return $items;
	}
}