<?php
//	NOTE:	this object is getting smaller and smaller whilst it's
//			functionality is being absorbed into the parent object

//	NOTE:	perhaps this means I should work to delete this object
//			and redistribute it's code elsewhere.
class Amslib_Plugin_Model extends Amslib_Database_MySQL
{
	protected $api;
	
	protected $enableDebug;
	protected $enableDebugLog;

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
		
		$dd		= $this->api ? $this->api->getValue("debug_database",false) : false;
		$ddl	= $this->api ? $this->api->getValue("debug_database_log",false) : false;
		
		$this->enableDebug		=	Amslib::getGET("debug_database",$dd);
		$this->enableDebugLog	=	Amslib::getGET("debug_database_log",$ddl);
	}
	
	public function selectValue($field,$query,$numResults=0,$optimise=false)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,4",$query,$numResults,$optimise);
	
			if($this->enableDebugLog){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}
	
		return parent::selectValue($field,$query,$numResults,$optimise);
	}
	
	public function select($query,$numResults=0,$optimise=false)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",$query,$numResults,$optimise);
	
			if($this->enableDebugLog){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}
	
		return parent::select($query,$numResults,$optimise);
	}
	
	public function select2($query,$numResults=0,$optimise=false)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",$query,$numResults,$optimise);
	
			if($this->enableDebugLog){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}
	
		return parent::select2($query,$numResults,$optimise);
	}
	
	public function insert($query)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",$query);
		}
		
		return parent::insert($query);
	}
	
	public function update($query,$allow_zero=true)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",$query,$allow_zero);
		}
		
		return parent::update($query,$allow_zero);
	}
	
	public function delete($query)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",$query);
		}
		
		return parent::delete($query);
	}
}