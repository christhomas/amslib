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
 * 		-	I DONT THINK THIS IS THE CORRECT LOCATION FOR THIS FILE
 * 			SINCE ITS ALMOST 100% NOT A PLUGIN SPECIFIC OBJECT, ITS
 * 			A DATABASE OBJECT, PERHAPS I CAN CALL IT SOMETHING LIKE
 * 			Amslib_Database_Model INSTEAD?
 * 		-	(23/10/2014): I still think this object is stupid and
 * 			the debugging code is probably not very nice, I should
 * 			do something nicer, build into the base layer
 *
 */
class Amslib_Plugin_Model extends Amslib_Database_MySQL
{
	protected $api;

	protected $enableDebug;
	protected $enableDebugLog;

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct($connect=false)
	{
		parent::__construct($connect);

		//	TODO: default to an empty object??
		$this->api = false;
	}

	public function isConnected()
	{
		$s1 = parent::isConnected();
		$s2 = $this->isInitialised();

		if(!$s1 && !$s2){
			Amslib_Debug::log(
				__METHOD__,
				"NOTE: Database was not connected, model was not initialised properly, ".
				"have you called the parent::initialiseObject(\$api) method?"
			);

			return false;
		}

		return true;
	}

	/**
	 * 	method:	isInitialised
	 *
	 * 	todo: write documentation
	 */
	public function isInitialised()
	{
		//	FIXME: this is overly simplistic, but quite realiable.

		return $this->api ? true : false;
	}

	/**
	 * 	method:	initialiseObject
	 *
	 * 	todo: write documentation
	 */
	public function initialiseObject($api)
	{
		if(!$api){
			Amslib_Debug::log("api variable passed was not valid");
		}

		$this->api = $api;
		$this->copyConnection($this->api->getModel());

		$dd		= $this->api ? $this->api->getValue("debug_database",false) : false;
		$ddl	= $this->api ? $this->api->getValue("debug_database_log",false) : false;

		$this->setDebugStatus(
			Amslib_GET::get("debug_database",$dd),
			Amslib_GET::get("debug_database_log",$ddl)
		);
	}

	/**
	 * 	method:	selectValue
	 *
	 * 	todo: write documentation
	 */
	public function selectValue($field,$query,$numResults=0,$optimise=false)
	{
		if($this->enableDebug){
			$log = Amslib_Debug::log("func_offset,3",$query,$numResults,$optimise);

			if($this->enableDebugLog){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}

		return parent::selectValue($field,$query,$numResults,$optimise);
	}

	/**
	 * 	method:	select
	 *
	 * 	todo: write documentation
	 */
	public function select($query,$numResults=0,$optimise=false)
	{
		if($this->enableDebug){
			$log = Amslib_Debug::log("func_offset,3",stripslashes($query),$numResults,$optimise);

			if($this->enableDebugLog){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}

		return parent::select($query,$numResults,$optimise);
	}

	/**
	 * 	method:	select2
	 *
	 * 	todo: write documentation
	 */
	public function select2($query,$numResults=0,$optimise=false)
	{
		if($this->enableDebug){
			$log = Amslib_Debug::log("func_offset,3",stripslashes($query),$numResults,$optimise);

			if($this->enableDebugLog){
				$this->api->logDebug("DEBUG_DATABASE: {$log["function"]}",$log["data"]);
			}
		}

		return parent::select2($query,$numResults,$optimise);
	}

	/**
	 * 	method:	insert
	 *
	 * 	todo: write documentation
	 */
	public function insert($query)
	{
		if($this->enableDebug){
			$log = Amslib_Debug::log("func_offset,3",stripslashes($query));
		}

		return parent::insert($query);
	}

	/**
	 * 	method:	update
	 *
	 * 	todo: write documentation
	 */
	public function update($query,$allow_zero=true)
	{
		if($this->enableDebug){
			$log = Amslib_Debug::log("func_offset,3",stripslashes($query),$allow_zero);
		}

		return parent::update($query,$allow_zero);
	}

	/**
	 * 	method:	delete
	 *
	 * 	todo: write documentation
	 */
	public function delete($query)
	{
		if($this->enableDebug){
			$log = Amslib_Debug::log("func_offset,3",stripslashes($query));
		}

		return parent::delete($query);
	}

	public function setDebugStatus($debug=false,$log=false)
	{
		$this->enableDebug		=	!!$debug;
		$this->enableDebugLog	=	!!$log;
	}
}