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
 * 	class:	Amslib_Database
 *
 *	group:	database
 *
 *	file:	Amslib_Database.php
 *
 *	title:	Antimatter Database: Base layer
 *
 *	description:
 *		A low level object to collect shared data and methods
 *		that are common to all database layers
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Database extends Amslib_Database_DEPRECATED
{
	protected $lastQuery		=	array();
	protected $lastResult		=	array();
	protected $lastInsertId		=	0;

	protected $debugState		=	false;
	protected $errors			=	array();
	protected $errorState		=	true;
	protected $errorCount		=	100;

	/**
	 * 	variable:	$table
	 * 	type:		array
	 *
	 * 	An array of table names, each key returns the actual name in the database
	 */
	protected $table					=	array();

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
	protected $connection				=	false;
	protected $connection_details		=	false;

	/**
	 * 	method: debug
	 *
	 * 	todo: write documentation
	 */
	protected function debug($va_args)
	{
		$va_args = func_get_args();

		if(empty($va_args)) return;

		//	Logging debug or query information is only allowed when debug is enabled
		if(in_array($va_args[0],array("DEBUG","QUERY")) && !$this->debugState) return;

		return call_user_func_array("Amslib_Debug::log",$va_args);
	}

	/**
	 * 	method:	setLastQuery
	 *
	 * 	todo: write documentation
	 */
	protected function setLastQuery($query)
	{
		$this->lastQuery[] = $query;
		if(count($this->lastQuery) > 100) array_shift($this->lastQuery);
	}

	/**
	 * 	method:	getLastResult
	 *
	 * 	todo: write documentation
	 */
	protected function getLastResult()
	{
		return $this->lastResult;
	}

	/**
	 * 	method:	getLastInsertId
	 *
	 * 	todo: write documentation
	 */
	protected function getLastInsertId()
	{
		return $this->lastInsertId;
	}

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct($connect=true)
	{
		$this->table	=	array();
		$this->errors	=	array();

		//	TODO: we should implement a try/catch block to easily catch disconnected databases
		if($connect) $this->connect();
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
		$args = call_user_func_array(array($this,"escape"),$args);

		$c = count($args);

		if($c == 1){
			$this->table = $args[0];
		}else if($c > 1){
			$this->table[$args[0]] = $args[1];
		}
	}

	/**
	 * 	method:	setErrorState
	 *
	 * 	todo: write documentation
	 * 	NOTE: does anything use this now??
	 */
	public function setErrorState($state)
	{
		$e = $this->errorState;

		$this->errorState = $state ? true : false;

		return $e;
	}

	/**
	 * 	method:	setDebug
	 *
	 * 	todo: write documentation
	 */
	public function setDebugState($state)
	{
		$this->debugState = !!$state;
	}

	/**
	 * 	method:	setError
	 *
	 * 	todo: write documentation
	 */
	public function setError($data)
	{
		//	Overload some default values just inc ase something failed
		$args = func_get_args() + array(
			"database not connected",
			"database not connected",
			-1
		);

		$this->errors[] = array(
			"db_failure"		=>	true,
			"db_query"			=>	preg_replace('/\s+/',' ',$args[0]),
			"db_error"			=>	$args[1],
			"db_error_num"		=>	$args[2],
			"db_last_insert"	=>	$this->lastInsertId,
			"db_insert_id"		=>	$args[3],
			//	HMM, not sure about this, i think better to just record a single line of
			//	the trace, which will be in the method which generated the failure
			"db_location"		=>	Amslib_Debug::getStackTrace(0,true)
		);

		if(count($this->errors) > $this->errorCount){
			array_shift($this->errors);
		}
	}

	/**
	 * 	method:	getDBErrors
	 *
	 * 	Obtain the errors set on the database object due to queries run against it
	 *
	 * 	parameters:
	 * 		$clear	-	Whether or not to clear the errors after obtaining them
	 *
	 * 	returns
	 * 		An array, empty or otherwise, of all the errors that have occured in the system
	 */
	public function getError($clear=true)
	{
		$errors = $this->errors;

		if($clear) $this->errors = array();

		return $errors;
	}

	/**
	 * 	method:	getLastQuery
	 *
	 * 	todo: write documentation
	 */
	public function getLastQuery()
	{
		return $this->lastQuery;
	}

	/**
	 * 	method:	getConnectionStatus
	 *
	 * 	Return the status of the database connection
	 *
	 * 	returns:
	 * 		-	Boolean true or false depending on whether the database logged in correctly or not
	 */
	public function isConnected()
	{
		if($this->connection) return true;

		$this->debug(__METHOD__.": DATABASE IS NOT CONNECTED");

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

	/**
	 * 	method:	setConnection
	 *
	 * 	todo: write documentation
	 */
	public function setConnection($connection)
	{
		return $this->connection = $connection;
	}

	/**
	 * 	method:	copyConnection
	 *
	 * 	todo: write documentation
	 */
	public function copyConnection($object)
	{
		if($object && is_object($object) && method_exists($object,"getConnection")){
			$this->setConnection($object->getConnection());
		}

		return $this->connection ? true : false;
	}

	public function setConnectionDetails($details=NULL)
	{
		$s = $d = false;

		if($details){
			$v = new Amslib_Validator($details);
			$v->add("username","text",true);
			$v->add("password","text",true);
			$v->add("database","text",true);
			$v->add("server","text",true);
			$v->add("encoding","text",true,array("default"=>"utf8"));

			$s = $v->execute();
			$d = $v->getValidData();
		}

		$this->connection_details = $s ? $d : false;

		return $this->getConnectionDetails();
	}

	/**
	 * 	method:	getConnectionDetails
	 *
	 * 	todo: write documentation
	 */
	public function getConnectionDetails()
	{
		if($this->connection_details){
			$c = $this->connection_details;
			$p = $c["password"];
			unset($c["password"]);

			return array($c,$p);
		}

		die("(".basename(__FILE__)." / FATAL ERROR): ".
			"getConnectionDetails was not defined in your database object, ".
			"so connection attempt will fail");
	}

	public static function sharedConnection($object=NULL)
	{
		static $shared = NULL;

		if($object && is_object($object) && method_exists($object,"getConnection")){
			$shared = $object;
		}

		return $shared;
	}
}