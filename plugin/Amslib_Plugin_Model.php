<?php
//	NOTE:	this object is getting smaller and smaller whilst it's
//			functionality is being absorbed into the parent object

//	NOTE:	perhaps this means I should work to delete this object
//			and redistribute it's code elsewhere.
class Amslib_Plugin_Model extends Amslib_Database_MySQL
{
	protected $api;

	public function __construct()
	{
		parent::__construct(false);

		//	TODO: default to an empty object??
		$this->api = false;
	}
	
	public function isInitialised()
	{
		//	FIXME: this is overly simplistic, but quite realiable.
		
		return $this->api ? true : false;
	}

	public function initialiseObject($api)
	{
		$this->api = $api;
		$this->copyConnection($this->api->getModel());
	}
	
	public function selectValue($field,$query,$numResults=0,$optimise=false)
	{
		if(Amslib::getGET("debug_database",$this->api->getValue("debug_database"))){
			$log = Amslib::errorLog($query,$numResults,$optimise);
	
			if(Amslib::getGET("debug_database_log",$this->api->getValue("debug_database"))){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}
	
		return parent::selectValue($field,$query,$numResults,$optimise);
	}
	
	public function select($query,$numResults=0,$optimise=false)
	{
		error_log(get_class($this)."::".__FUNCTION__.", query = ".preg_replace("/\s+/"," ",$query));
		if(Amslib::getGET("debug_database",$this->api->getValue("debug_database"))){
			$log = Amslib::errorLog("func_offset,3",$query,$numResults,$optimise);
	
			if(Amslib::getGET("debug_database_log",$this->api->getValue("debug_database"))){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}
	
		return parent::select($query,$numResults,$optimise);
	}
}