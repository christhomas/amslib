<?php
class Amslib_Benchmark
{
	static protected $start = NULL;
	static protected $finish = NULL;
	static protected $total = NULL;
	static protected $entries = array();
	static protected $mode = "record";
	static protected $allowedModes = array("record","log");
	
	static public function mode($mode)
	{
		if(!in_array($mode,self::$allowedModes)){
			return false;
		}
		
		return self::$mode = $mode;
	}

	static public function set($title=NULL,$data=NULL)
	{
		static $last = NULL;

		//	If no title is set, use the code location which executed the set() method
		if($title === NULL) $title = Amslib_Debug::getCodeLocation(3);

		$time = microtime(true);

		//	If is first benchmark point, set the start time to this time
		if(self::$start === NULL) self::$start = $time;

		//	Always set the finish time to the last benchmark point
		self::$finish = $time;

		//	Set the total to the subtraction of the last and first benchmark points
		self::$total = self::$finish - self::$start;
		
		//	calculate the diff only when there was a previous benchmark point set
		$diff = $last === NULL ? 0 : ($time-$last["time"]);

		//	Set the basic data
		$e = array(
			"title"	=>	$title,
			"time"	=>	$time,
			"diff"	=>	$diff
		);

		//	Save the data, store the last entry, add it to the list and return it to the calling method
		if($data !== NULL) $e["data"] = $data;

		//	Retain a copy of the previous execution so you can do the differential easier
		$last = $e;
		
		switch(self::$mode){
			case "record":{
				self::$entries[] = $e; 
			}break;
			
			case "log":{
				//	FIXME: refactor against log() 
				Amslib_Debug::log("title[$title], time[$time], diff[$diff]");
			}break;
		}

		return $e;
	}

	static public function get($title=NULL,$totals=true)
	{
		$data = array();

		if($totals){
			$data["totals"] = self::totals();
		}

		if($title){
			foreach(self::$entries as $e){
				if($e["title"] == $title){
					$data["entry"] = $e;
					break;
				}
			}
		}else{
			$data["list"] = self::$entries;
		}

		if($totals) return $data;

		if(isset($data["entry"])) return $data["entry"];
		if(isset($data["list"])) return $data["list"];

		return false;
	}

	static public function totals()
	{
		if(!self::$start) return NULL;

		return array(
			"start"		=>	self::$start,
			"finish"	=>	self::$finish,
			"total"		=>	self::$total
		);
	}

	static public function log()
	{
		foreach(self::get(NULL,false) as $item)
		{
			Amslib_Debug::log("code_location,{$item["title"]}","time[{$item["time"]}], diff[{$item["diff"]}]");
		}

		if($totals = self::totals()){
			Amslib_Debug::log("start[{$totals["start"]}], finish[{$totals["finish"]}], total[{$totals["total"]}]");
		}
	}
}