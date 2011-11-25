<?php
class Amslib_Array
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
	
	static public function pluck($array,$key)
	{
		if(!is_array($array) || !self::isMulti($array)) return array();
		
		$values = array();
		
		foreach(self::valid($array) as $item){
			if(isset($item[$key])) $values[] = $item[$key];
		}
		
		return $values;
	}
	
	static public function removeValue(array $array,$value,$strict=false)
	{
	    return array_diff_key($array, array_flip(array_keys($array, $value, $strict)));
	}
	
	public function filterType($array,$callback)
	{
		return function_exists($callback) ? array_filter(self::valid($array),$callback) : $array;
	}
	
	static public function filterKey($array,$filter,$similar=false)
	{
		$array = self::valid($array);
		
		if($similar === false){
			if(self::isMulti($array)){
				foreach($array as &$a) $a = array_intersect_key($a, array_flip($filter));
			}else{
				$array = array_intersect_key($array, array_flip($filter));	
			}
		}else{
			$array = self::filter($array,NULL,$filter,true,true);
		}
		
		return $array;
	}
	
	static public function filter($array,$key,$value,$returnFiltered=false,$similar=false)
	{
		$filter = array();

		foreach(self::valid($array) as $k=>$v)
		{
			$found = false;
			
			//	TODO: I'm sure that there are more situations I could take into account here
			//	TODO: I should document exactly what this method does, because right now I can't remember
			
			$search = $key ? $v[$key] : $v;

			if($similar == true && strpos($search,$value) !== false) $found = true;
			else if($search == $value) $found = true;
			else if(is_array($value)){
				if($similar == true){
					foreach($value as $subvalue){
						if(strpos($search,$subvalue) !== false){
							//	NOTE: here I am adjusting the key to reply the key=>value meaning the subvalue=>value
							//	NOTE: but I am not sure whether I want to do this 100% of the time, for now, it's a good choice
							$k = $subvalue;
							$found = true;
							break;
						}
					}
				}else if(in_array($search,$value)){
					$found = true;	
				}
			}
			
			if($found){
				$filter[$k] = $v;
				unset($array[$k]);
			}
		}

		return $returnFiltered ? $filter : $array;
	}
	
	//	TODO: this method is a little open to abuse and in some situations wouldn't do the right thing
	//	TODO: explain in words what this does and how it should work
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
		$result = NULL;
		
		foreach(self::valid($array) as $a){
			if($a[$key] == $value) return $a;
		}

		return false;
	}

	/*
	//	I don't know how to integrate this into the api yet, but it's a cool function!!!
	//	author: d3x from freenode's #php channel
	//	usage: find($array,$k1,$k2,$k3,$k4)
	//	description: you have a multi-dimensional array, the keys will represent a path to the final kv pair
	function find($array) {
		$args = func_get_args();
		array_shift($args);
		if ($args) {
			$key = array_shift($args);
			array_unshift($args, $array[$key]);
			return call_user_func_array(__FUNCTION__, $args); 
		}
		return $array;
	}
	*/
	
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

	static public function stripSlashesMulti($array,$key=NULL)
	{
		if(is_string($key)) $key = array($key);
		
		foreach($array as &$element){
			if(!$key) $key = array_keys($element);
			foreach($key as $index){
				$element[$index] = stripslashes($element[$index]);
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
	
	//	DEPRECATED: this is not supposed to be here
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