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
 * version: 1.5
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
	private static $sharedConnection = false;
	
	protected $lastResult = array();

	protected $lastInsertId = 0;

	protected $lastQuery = array();

	protected $debug = false;

	protected $selectResult = false;
	
	protected $seq = 0;

/******************************************************************************
 *	PROTECTED MEMBERS
 *****************************************************************************/

	/**
	 * 	array: $loginDetails
	 *
	 * 	An array of login data which contains the values that you want to connect with
	 *
	 * 	values:
	 * 		server		-	string(url:port)
	 * 		username	-	string
	 * 		password	-	string
	 * 		database	-	string
	 */
	protected $loginDetails;

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

	protected function getDatabaseLogin()
	{
		$this->setDatabaseLogin();
	}
	
	protected function setDatabaseLogin()
	{
		die(get_class($this)."::setDatabaseLogin() -> Your database class is missing this method");
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
		$this->seq = 0;
	}

	public function getLastQuery()
	{
		return $this->lastQuery;
	}

	public function setFetchMethod($method)
	{
		if(function_exists($method)){
			$this->fetchMethod = $method;
		}
	}

	public function getSearchResultHandle()
	{
		return $this->selectResult;
	}

	public function connect()
	{
		$this->getDatabaseLogin();
		$this->makeConnection();
	}
	
	public function fatalError($msg)
	{
		//	Protect sensitive data
		$this->loginDetails		=	"*PROTECTED*";
		$this->__lastResult		=	"*PROTECTED*";
		$this->__lastQuery		=	"*PROTECTED*";
		
		die("	FATAL ERROR: $msg<br/>
				database error = '".$this->error()."'<br/>
				location of error = ".Amslib::var_dump(Amslib_Array::filterKey(array_slice(debug_backtrace(),1,2),array("file","line")),true)
		);
	}

	public function setDebug($state)
	{
		$this->debug = $state;
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
		return $this->connection ? true : false;
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

	public function copy($database)
	{
		if($database && method_exists($database,"getConnection")){
			$c = $database->getConnection();
			
			if($c){
				$this->connection = $c;

				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * method: setSharedConnection
	 * 
	 * Set a shared "application" database connection which can be 
	 * retrieved from other classes in order to share the "connection" 
	 * with the application's database
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
	 * Get the shared "application" database connection in order to access 
	 * the application database, without knowing where the connection comes 
	 * from (example: the application knows, but the plugin does not)
	 */
	public static function getSharedConnection()
	{
		return self::$sharedConnection;
	}
}