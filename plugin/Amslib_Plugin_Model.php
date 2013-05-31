<?php
/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
* Contributors/Author:
*    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
*
*******************************************************************************/

/**
 * 	class:	Amslib_Plugin_Model
 *
 *	group:	plugin
 *
 *	file:	Amslib_Plugin_Model.php
 *
 *	description:
 *		todo, write description
 *
 * 	todo:
 * 		write documentation
 * 
 * 	notes:
 * 		-	this object is getting smaller and smaller whilst it's
 * 			functionality is being absorbed into the parent object
 * 		-	perhaps this means I should work to delete this object
 * 			and redistribute it's code elsewhere.
 *
 */
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
			$log = Amslib::errorLog("func_offset,3",$query,$numResults,$optimise);
	
			if($this->enableDebugLog){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}
	
		return parent::selectValue($field,$query,$numResults,$optimise);
	}
	
	public function select($query,$numResults=0,$optimise=false)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",stripslashes($query),$numResults,$optimise);
	
			if($this->enableDebugLog){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}
	
		return parent::select($query,$numResults,$optimise);
	}
	
	public function select2($query,$numResults=0,$optimise=false)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",stripslashes($query),$numResults,$optimise);
	
			if($this->enableDebugLog){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}
	
		return parent::select2($query,$numResults,$optimise);
	}
	
	public function insert($query)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",stripslashes($query));
		}
		
		return parent::insert($query);
	}
	
	public function update($query,$allow_zero=true)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",stripslashes($query),$allow_zero);
		}
		
		return parent::update($query,$allow_zero);
	}
	
	public function delete($query)
	{
		if($this->enableDebug){
			$log = Amslib::errorLog("func_offset,3",stripslashes($query));
		}
		
		return parent::delete($query);
	}
}