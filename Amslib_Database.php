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
 * title: Antimatter Database
 * description: A database object to centralise all interaction with a mysql databse
 * 		into a single object which nicely hides some of the annoying repetitive
 * 		aspects of a database, whilst giving you a nice candy layer to deal with instead
 * version: 2.3
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

/**
 * 	class:	Amslib_Database
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
class Amslib_Database
{
	protected $__loginDetails;

	/**
	 * 	boolean: __connection
	 *
	 * 	Boolean true or false value, which gets updated when the server login is attempted
	 *
	 * 	values:
	 * 		true	-	The database connected successfully
	 * 		false	-	The database failed to connect
	 */
	protected $__connection = false;

	protected $__dbAction = array();

	protected $__lastResult = array();

	protected $__lastInsertId = 0;

	protected $__lastQuery = array();

	protected $__dbFile = "mysql-details.php";

	protected $__debug = false;
	
	protected $__selectResult = false;

	/**
	 * 	method:	Amslib_Database
	 *
	 * 	This method is called when the database object is created, it connects to the database by default
	 */
	public function __construct($connect=true)
	{
		//	Setup default database actions (just in case someone forgets)
		$this->__setupDatabaseActions(array("select","insert","update","delete"));
		$this->setFetchMethod("mysql_fetch_assoc");
		
		if($connect) $this->connect();
	}

	protected function __setupLoginDetails()
	{
		Amslib::include_file($this->__dbFile);
		$this->__loginDetails = getDatabaseAccess();
	}

	protected function __setupDatabaseActions($actions)
	{
		$this->__dbAction = $actions;
	}

	protected function __setDBFile($file)
	{
		$this->__dbFile = $file;
	}

	protected function __setLastQuery($query)
	{
		$this->__lastQuery[] = $query;
		if(count($this->__lastQuery) > 100) array_shift($this->__lastQuery);
	}

	protected function __getLastTransactionId()
	{
		return $this->__lastInsertId;
	}

	protected function __getLastResult()
	{
		return $this->__lastResult;
	}

	public function getLastQuery()
	{
		return $this->__lastQuery;
	}
	
	public function setFetchMethod($method)
	{
		if(function_exists($method)){
			$this->fetchMethod = $method;
		}
	}

	/**
	 * 	method:	__makeConnection
	 *
	 * 	Connect to the MYSQL database using various details
	 *
	 * 	todo:
	 * 		-	Need to move the database details to somewhere more secure (like inside the database!! ROFL!! joke, don't do that!!!!)
	 */
	protected function __makeConnection()
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
		
		$this->__loginDetails = NULL;
	}

	public function setEncoding($encoding)
	{
		mysql_query("SET NAMES $encoding");
		mysql_query("SET CHARACTER SET $encoding");
	}

	protected function __transaction($command,$query,$numResults=0)
	{
		switch($command){
			case "select":{	return $this->select($query,$numResults);	}break;
			case "insert":{	return $this->insert($query);				}break;
			case "update":{	return $this->update($query);				}break;
			case "delete":{	return $this->delete($query);				}break;
		}
		
		return false;
	}
	
	public function getResults($numResults)
	{
		$this->__lastResult = array();
		
		$c = 0;
		while($r = call_user_func($this->fetchMethod,$this->__selectResult)){
			$this->__lastResult[] = $r;
			
			if(++$c == $numResults) break;		
		}
		
		if($c == 1 && $numResults == 1) $this->__lastResult = current($this->__lastResult);
		if($c == 0) $this->__lastResult = false;
		
		return $this->__lastResult;
	}

	//	Something along these lines might work
	//	FIXME: numResult doesnt really get used or shouldnt be, it's a PHP implementation of SQL's limit command, it should be changed
	//	FIXME: what numResult is used for, is to "optimise" out an array of arrays with only a single row returned, so perhaps it should update to reflect that
	public function select($query,$numResults=0)
	{
		if($this->getConnectionStatus() == false) return false;

		$command = "select";

		$this->__setLastQuery("$command $query");
		$this->__selectResult = mysql_query("$command $query");
		if($this->__debug) print("<pre>QUERY = '$command $query'<br/></pre>");

		if($this->__selectResult && mysql_num_rows($this->__selectResult) >= 0){
			return $this->getResults($numResults);
		}

		$this->fatalError("Transaction failed<br/>command = '$command'<br/>query = '$query'");

		return false;
	}

	public function insert($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$command = "insert into";

		$this->__setLastQuery("$command $query");
		$result = mysql_query("$command $query");
		if($this->__debug) print("<pre>QUERY = '$command $query'<br/></pre>");

		$this->__lastInsertId = mysql_insert_id();
		if($result && ($this->__lastInsertId !== false)) return $this->__lastInsertId;
		
		$this->__lastInsertId = false;

		$this->fatalError("Transaction failed<br/>command = '$command'<br/>query = '$query'<br/><pre>result = '".print_r($result,true)."'</pre>lastInsertId = '$this->__lastInsertId'<br/>mysql_insert_id() = '".mysql_insert_id()."'");

		return false;
	}

	public function update($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$command = "update";

		$this->__setLastQuery("$command $query");
		$result = mysql_query("$command $query");
		if($this->__debug) print("<pre>QUERY = '$command $query'<br/></pre>");

		if($result) return mysql_affected_rows() >= 0;

		$this->fatalError("Transaction failed<br/>command = '$command'<br/>query = '$query'");

		return false;
	}

	public function delete($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$command = "delete from";

		$this->__setLastQuery("$command $query");
		$result = mysql_query("$command $query");
		if($this->__debug) print("<pre>QUERY = '$command $query'<br/></pre>");

		if($result) return mysql_affected_rows();

		$this->fatalError("Transaction failed<br/>command = '$command'<br/>query = '$query'");

		return false;
	}

	public function connect()
	{
		$this->__setupLoginDetails();
		$this->__makeConnection();
	}

	public function fatalError($msg)
	{
		$this->__loginDetails = NULL;
		
		die("FATAL ERROR: $msg<br/>mysql_error = '".$this->error()."'");
	}

	public function error()
	{
		return mysql_error();
	}

	public function setDebug($state)
	{
		$this->__debug = $state;
	}

	/**
	 * 	method:	__disconnect
	 *
	 * 	Disconnect from the Database
	 */
	protected function __disconnect()
	{
		$this->__connection = false;
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
		return $this->__connection ? true : false;
	}
	
	public function getConnection()
	{
		return $this->__connection;
	}
	
	public function copy($database)
	{
		$this->__connection = $database->getConnection();
	}

	public function &getInstance($connect=true)
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new Amslib_Database($connect);

		return $instance;
	}
}
?>
