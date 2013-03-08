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
 * file: Amslib_Database.php
 * title: Antimatter Database: Base layer
 * description: A low level object to collect shared data and methods that are common
 * 				to all database layers
 * version: 3.0
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Database
{
/******************************************************************************
 *	PRIVATE MEMBERS
 *
 *	These members are for private use only, if you have to use
 *	them yourself in the higher layers, maybe it's because you're DOING_IT_WRONG
 *
 *	NOTE: they are not converted to private yet because they are being
 *	explored for possible problems
 *****************************************************************************/
	private static $sharedConnection	=	false;

	protected $lastResult				=	array();

	protected $lastInsertId			=	0;

	protected $lastQuery				=	array();

	protected $debug					=	false;
	protected $errorState				=	true;

	protected $selectResult			=	false;
	protected $storeSearchResult		=	false;

	protected $seq						=	0;
	
	protected $errors					=	array();
	protected $maxErrorCount			=	100;

	//	NOTE: do I use this for anything?
	protected $databaseName			=	false;
	protected $table					=	array();

/******************************************************************************
 *	PROTECTED MEMBERS
 *****************************************************************************/

	/**
	 * 	boolean: connection
	 *
	 * 	Boolean true or false value, which gets updated when the server login is attempted
	 *
	 * 	values:
	 * 		true	-	The database connected successfully
	 * 		false	-	The database failed to connect
	 *
	 * 	FIXME: The description of this member is incorrect
	 */
	protected $connection = false;

	protected function setLastQuery($query)
	{
		$this->lastQuery[] = $query;
		if(count($this->lastQuery) > 100) array_shift($this->lastQuery);
	}

	protected function getLastTransactionId()
	{
		return $this->lastInsertId;
	}

	protected function getLastResult()
	{
		return $this->lastResult;
	}

	public function __construct()
	{
		$this->seq		=	0;
		$this->table	=	array();
	}

	public function setFetchMethod($method)
	{
		if(function_exists($method)){
			$this->fetchMethod = $method;
		}
	}

	public function getConnectionDetails()
	{
		die("(".basename(__FILE__)." / FATAL ERROR): getConnectionDetails was not defined in your database object, so connection attempt will fail");
	}

	public function setDebug($state)
	{
		$this->debug = $state;
	}

	public function setErrorState($state)
	{
		$e = $this->errorState;

		$this->errorState = $state ? true : false;

		return $e;
	}
	
	public function setDBErrors($data,$error=NULL,$errno=NULL,$insert_id=NULL)
	{
		$this->errors[] = array(
				"db_failure"		=>	true,
				"db_query"			=>	preg_replace('/\s+/',' ',$data),
				"db_error"			=>	$error,
				"db_error_num"		=>	$errno,
				"db_last_insert"	=>	$this->lastInsertId,
				"db_insert_id"		=>	$insert_id,
				"db_location"		=>	Amslib_Array::filterKey(array_slice(debug_backtrace(),0,5),array("file","line")),
		);
	
		if(count($this->errors) > $this->maxErrorCount){
			$this->errors = array_slice($this->errors,-($this->maxErrorCount));
		}
	}
	
	public function getDBErrors()
	{
		return $this->errors;
	}

	public function getLastQuery()
	{
		return $this->lastQuery;
	}

	public function getSearchResultHandle()
	{
		return $this->selectResult;
	}

	public function storeSearchHandle()
	{
		$this->storeSearchResult = $this->selectResult;
	}

	public function restoreSearchHandle()
	{
		if($this->storeSearchResult) $this->selectResult = $this->storeSearchResult;

		$this->storeSearchResult = false;
	}

	/**	
	 * method:	setTable
	 * 
	 * Set the table name for this database object, or if there are two parameters, a key=>value arrangement allowing
	 * you to abstract table names from the names referenced in the code
	 * 
	 * parameters:
	 * 	arg1	-	the name of the table, or the name of the key to use for this table
	 * 	arg2	-	[optional] or the actual name of the table inside the database references by arg1 as the "key"
	 */
	public function setTable()
	{
		$args = func_get_args();

		$c = count($args);

		if($c == 1){
			$this->table = $this->escape($args[0]);
		}else if($c > 1){
			$this->table[$this->escape($args[0])] = $this->escape($args[1]);
		}
	}

	/**
	 * 	method:	getConnectionStatus
	 *
	 * 	Return the status of the database connection
	 *
	 * 	returns:
	 * 		-	Boolean true or false depending on whether the database logged in correctly or not
	 */
	public function getConnectionStatus()
	{
		if($this->connection) return true;

		//	This is almost always a good idea!!
		ini_set("display_errors",false);
		trigger_error(__METHOD__.": DATABASE IS NOT CONNECTED");

		return false;
	}

	/**
	 * method: getConnection
	 *
	 * Return the current mysql connection created when the database was connected
	 *
	 * returns:
	 * 	A mysql database connection resource, or false, if it's not yet connected or failed to connect
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	public function setConnection($connection)
	{
		$this->connection = $connection;
	}

	public function copyConnection($database)
	{
		if($database && method_exists($database,"getConnection")){
			$this->setConnection($database->getConnection());
		}

		return $this->connection ? true : false;
	}

	/**
	 * method: setSharedConnection
	 *
	 * A simple way to share a database connection without
	 * having to pass the object around
	 */
	public static function setSharedConnection($databaseObject)
	{
		if(method_exists($databaseObject,"getConnection")){
			self::$sharedConnection = $databaseObject;
		}
	}

	/**
	 * method: getSharedConnection
	 *
	 * Retrieve the shared database connection, this is useful
	 * in scenarios where you need to simply share the object
	 * but don't want to pass the object around
	 */
	public static function getSharedConnection()
	{
		return self::$sharedConnection;
	}
}