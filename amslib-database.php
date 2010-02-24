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
 * file: amslib-database.php
 * title: Antimatter Database
 * description: A database object to centralise all interaction with a mysql databse
 * 		into a single object which nicely hides some of the annoying repetitive
 * 		aspects of a database, whilst giving you a nice candy layer to deal with instead
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

/**
 * 	class:	AntimatterDatabase
 *
 * 	The database core class which controls access to the database, ALL SQL
 * 	is done inside here and no SQL should be done elsewhere
 *
 *	notes:
 *		-	Do not directly instantiate this class, instead, extend it into a child and then use a singleton pattern and instantiate that instead
 *
 *	TODO:
 *		-	Implement connection pooling
 */
class AntimatterDatabase
{
	var $__loginDetails;

	/**
	 * 	boolean: _connection
	 *
	 * 	Boolean true or false value, which gets updated when the server login is attempted
	 *
	 * 	values:
	 * 		true	-	The database connected successfully
	 * 		false	-	The database failed to connect
	 */
	var $__connection = false;

	var $__dbAction = array();

	var $__lastResult = array();

	var $__lastInsertId = 0;

	var $__lastQuery = array();
	var $__queryCacheLength = 10;

	var $__dbFile = "mysql-details.php";

	var $__debug = false;

	/**
	 * 	method:	AntimatterDatabase
	 *
	 * 	This method is called when the database object is created, it connects to the database by default
	 */
	function AntimatterDatabase($connect=true)
	{
		//	Setup default database actions (just in case someone forgets)
		$this->__setupDatabaseActions(array("select","insert","update","delete"));

		if($connect) $this->connect();
	}

	function __setupLoginDetails()
	{
		amslib::include_file($this->__dbFile);
		$this->__loginDetails = getDatabaseAccess();
	}

	function __setupDatabaseActions($actions)
	{
		$this->__dbAction = $actions;
	}

	function __setDBFile($file)
	{
		$this->__dbFile = $file;
	}

	function __setLastQuery($query)
	{
		$this->__lastQuery[] = $query;
		if(count($this->__lastQuery) > $this->__queryCacheLength) array_shift($this->__lastQuery);
	}

	function __defaultFetchResult($result)
	{
		return mysql_fetch_assoc($result);
	}

	function __getLastTransactionId()
	{
		return $this->__lastInsertId;
	}

	function __getLastResult()
	{
		return $this->__lastResult;
	}

	function getLastQuery($count=NULL)
	{
		if($count === NULL) return array_reverse($this->__lastQuery);
		else if(is_numeric($count)) return array_slice(array_reverse($this->__lastQuery),0,$count);
	}

	/**
	 * 	method:	__makeConnection
	 *
	 * 	Connect to the MYSQL database using various details
	 *
	 * 	todo:
	 * 		-	Need to move the database details to somewhere more secure (like inside the database!! ROFL!! joke, don't do that!!!!)
	 */
	function __makeConnection()
	{
		$this->__disconnect();

		if($this->__loginDetails){
			if($c = mysql_connect($this->__loginDetails["server"],$this->__loginDetails["username"],$this->__loginDetails["password"],true))
			{
				if(!mysql_select_db($this->__loginDetails["database"])) $this->__disconnect();
				else{
					$this->__connection = $c;
				}
			// Replace these errors with PHPTranslator codes instead (language translation)
			}else $this->fatalError("Failed to connect to database: {$this->__loginDetails["database"]}<br/>");
		// Replace these errors with PHPTranslator codes instead (language translation)
		}else $this->fatalError("Failed to find the database connection details, check this information<br/>");
	}

	function setEncoding($encoding)
	{
		mysql_query("SET NAMES $encoding");
		mysql_query("SET CHARACTER SET $encoding");
	}

	function __transaction($command,$query,$numResults=0,$fetchMethod="__defaultFetchResult")
	{
		if($this->getConnectionStatus() == false) return false;

		if(in_array($command,$this->__dbAction))
		{
			$this->__setLastQuery("$command $query");
			$result = mysql_query("$command $query",$this->__connection);
			if($this->__debug) print("<pre>QUERY = '$command $query'<br/></pre>");

			if($result){
				if($command === "insert" && ($this->__lastInsertId = mysql_insert_id())) return $this->__lastInsertId;
				if($command === "update" || $command === "delete") return $result;
				if($command === "select" && mysql_num_rows($result) >= 0){
					$this->__lastResult = array();

					while($r = $this->$fetchMethod($result)){
						$this->__lastResult[] = $r;
						if(count($this->__lastResult) == $numResults) break;
					}

					$c = count($this->__lastResult);
					if($c == 1 && $numResults == 1) $this->__lastResult = $this->__lastResult[0];
					if($c == 0) $this->__lastResult = false;

					return $this->__lastResult;
				}
			}

			$this->fatalError("Transaction failed<br/>command = $command<br/>query = $query");
		}

		$this->fatalError("A transaction was attempted, which is not permitted:<br/>transaction command = $command<br/>transaction query = $query<br/>");
	}

	//	Something along these lines might work
	function select($query,$numResults=0,$fetchMethod="__defaultFetchResult")
	{
		if($this->getConnectionStatus() == false) return false;

		$command = "select";

		$this->__setLastQuery("$command $query");
		$result = mysql_query("$command $query",$this->__connection);
		if($this->__debug) print("<pre>QUERY = '$command $query'<br/></pre>");

		if($result && mysql_num_rows($result) >= 0){
			$this->__lastResult = array();

			while($r = $this->$fetchMethod($result)){
				$this->__lastResult[] = $r;
				if(count($this->__lastResult) == $numResults) break;
			}

			$c = count($this->__lastResult);
			if($c == 1 && $numResults == 1) $this->__lastResult = $this->__lastResult[0];
			if($c == 0) $this->__lastResult = false;

			return $this->__lastResult;
		}

		$this->fatalError("Transaction failed<br/>command = $command<br/>query = $query");

		return false;
	}

	function insert($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$command = "insert";

		$this->__setLastQuery("$command $query");
		$result = mysql_query("$command $query",$this->__connection);
		if($this->__debug) print("<pre>QUERY = '$command $query'<br/></pre>");

		if($result && ($this->__lastInsertId = mysql_insert_id())) return $this->__lastInsertId;

		$this->fatalError("Transaction failed<br/>command = $command<br/>query = $query");

		return false;
	}

	function update($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$command = "update";

		$this->__setLastQuery("$command $query");
		$result = mysql_query("$command $query",$this->__connection);
		if($this->__debug) print("<pre>QUERY = '$command $query'<br/></pre>");

		if($result) return $result;

		$this->fatalError("Transaction failed<br/>command = $command<br/>query = $query");

		return false;
	}

	function delete($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$command = "delete";

		$this->__setLastQuery("$command $query");
		$result = mysql_query("$command $query",$this->__connection);
		if($this->__debug) print("<pre>QUERY = '$command $query'<br/></pre>");

		if($result) return $result;

		$this->fatalError("Transaction failed<br/>command = $command<br/>query = $query");

		return false;
	}

	function connect()
	{
		$this->__setupLoginDetails();
		$this->__makeConnection();
	}

	function fatalError($msg)
	{
		die("FATAL ERROR: $msg<br/>mysql_error = ".mysql_error());
	}

	function error()
	{
		return mysql_error();
	}

	function setDebug($state)
	{
		$this->__debug = $state;
	}

	/**
	 * 	method:	__disconnect
	 *
	 * 	Disconnect from the Database
	 */
	function __disconnect()
	{
		$this->__connection = false;
	}
	
	function setQueryCacheLength($length)
	{
		if(!length || !is_numeric($length)) return false;
		
		$this->__queryCacheLength = $length;
		return true;
	}

	/**
	 * 	method:	getConnectionStatus
	 *
	 * 	Return the status of the database connection
	 *
	 * 	returns:
	 * 		-	Boolean true or false depending on whether the database logged in correctly or not
	 */
	function getConnectionStatus()
	{
		return $this->__connection;
	}

	function &getInstance($connect=true)
	{
		static  $instance = NULL;

		if($instance === NULL) $instance = new AntimatterDatabase($connect);

		return $instance;
	}
}
?>
