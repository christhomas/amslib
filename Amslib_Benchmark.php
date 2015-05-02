<?php
class Amslib_Benchmark
{
	static protected $start = NULL;
	static protected $finish = NULL;
	static protected $total = NULL;
	static protected $entries = array();

	static public function set($title=NULL,$data=NULL)
	{
		static $last = NULL;

		//	If no title is set, use the code location which executed the set() method
		if($title === NULL) $title = Amslib_Debug::getCodeLocation();

		$time = microtime(true);

		//	If is first benchmark point, set the start time to this time
		if(self::$start === NULL) self::$start = $time;

		//	Always set the finish time to the last benchmark point
		self::$finish = $time;

		//	Set the total to the subtraction of the last and first benchmark points
		self::$total = self::$finish - self::$start;

		//	Set the basic data
		$e = array(
				"title"	=>	$title,
				"time"	=>	$time,
				//	calculate the diff only when there was a previous benchmark point set
				"diff"	=>	$last === NULL ? 0 : ($time-$last["time"])
		);

		//	Save the data, store the last entry, add it to the list and return it to the calling method
		if($data !== NULL) $e["data"] = $data;

		//	Retain a copy of the previous execution so you can do the differential easier
		$last = $e;

		self::$entries[] = $e;

		return $e;
	}

	static public function get($title=NULL,$totals=true)
	{
		if($title === NULL){
			return array(
				"list"		=> self::$entries,
				"totals"	=> self::totals()
			);
		}

		foreach(self::$entries as $e){
			if($e["title"] == $title) return $e;
		}

		return false;
	}

	static public function totals()
	{
		return array(
			"start"		=>	self::$start,
			"finish"	=>	self::$finish,
			"total"		=>	self::$total
		);
	}
}