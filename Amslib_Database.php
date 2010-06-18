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
 * version: 2.4
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
/******************************************************************************
 *	PRIVATE MEMBERS
 *
 *	These members are for private use only, if you have to use
 *	them yourself in the higher layers, maybe it's because you're DOING_IT_WRONG
 *
 *	NOTE: they are not converted to private yet because they are being
 *	explored for possible problems
 *****************************************************************************/
	protected $__dbAction = array();

	protected $__lastResult = array();

	protected $__lastInsertId = 0;

	protected $__lastQuery = array();

	protected $__dbFile = "mysql-details.php";

	protected $__debug = false;
	
	protected $__selectResult = false;
	
/******************************************************************************
 *	PROTECTED MEMBERS
 *****************************************************************************/
	
	/**
	 * 	array: $__loginDetails
	 * 
	 * 	An array of login data which contains the values that you want to connect with
	 * 
	 * 	values:
	 * 		server		-	string(url:port)
	 * 		username	-	string
	 * 		password	-	string
	 * 		database	-	string
	 */
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


	//	DEPRECATED: use getDatabaseLogin
	protected function __setupLoginDetails(){ return $this->getDatabaseLogin(); }
	protected function getDatabaseLogin()
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
				if(!mysql_select_db($this->__loginDetails["database"],$c)) $this->__disconnect();
				else{
					$this->__connection = $c;
				}
			// Replace these errors with PHPTranslator codes instead (language translation)
			}else $this->fatalError("Failed to connect to database: {$this->__loginDetails["database"]}<br/>");
		// Replace these errors with PHPTranslator codes instead (language translation)
		}else $this->fatalError("Failed to find the database connection details, check this information<br/>");
		
		$this->__loginDetails = NULL;
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

/******************************************************************************
 *	PUBLIC MEMBERS
 *****************************************************************************/

	/**
	 * 	method:	__construct
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
	
	/**
	 * method:	getRealResultCount
	 * 
	 * Obtain a real row count from the previous query, if you use limit and want to know how many
	 * a query WOULD return without the limit, you can use this method, but in the query, you need
	 * to put SQL_CALC_FOUND_ROWS as one of the selected fields
	 * 
	 * returns:
	 * 	The number of results the previous query would have returned without the limit statement, 
	 * 	if using SQL_CALC_FOUND_ROWS in the query
	 * 
	 * notes:
	 * 	-	this method uses the select result stack to store the previous query, which is assumed
	 * 		to be the query that generated the results, but you need the real result count before you 
	 * 		process all the results, normally, calling this method would destroy the previous query 
	 * 		and all your results with it.
	 */
	public function getRealResultCount()
	{
		$result = $this->select("FOUND_ROWS() as num_results",1);
		
		return (isset($result["num_results"])) ? $result["num_results"] : false;
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
	
	public function getSearchResultHandle()
	{
		return $this->__selectResult;
	}

	/**
	 * method: hasTable
	 * 
	 * Find out whether the connected mysql database has a table on the database given
	 * this is useful for determining whether a database can support certain functionality
	 * or not
	 * 
	 * parameters:	
	 * 	$database	-	The database to check for the table
	 * 	$table		-	The table to check for
	 * 
	 * returns:
	 * 	A boolean true or false result, depending on the existence of the table
	 */
	public function hasTable($database,$table)
	{
		$database	=	mysql_real_escape_string($database);
		$table		=	mysql_real_escape_string($table);
		
$query=<<<HAS_TABLE
			COUNT(*) 
		from 
			information_schema.tables 
		where 
			table_schema = '$database' and table_name='$table'
HAS_TABLE;

		$result = $this->select($query,1);
		
		if(isset($result["COUNT(*)"])){
			return $result["COUNT(*)"] ? true : false;
		}
		
		return false;
	}

	public function setEncoding($encoding)
	{
		$allowedEncodings = array("utf8");
		
		if(in_array($encoding,$allowedEncodings)){
			mysql_query("SET NAMES '$encoding'",$this->__connection);
			mysql_query("SET CHARACTER SET $encoding",$this->__connection);
		}else{
			die(	"FATAL ERROR: Your encoding is wrong, this can cause database corruption.".
					"I can't allow you to continue<br/>".
					"allowed encodings = <pre>".print_r($allowedEncodings,true)."</pre>");
		}
	}
	
	public function getResults($numResults,$resultHandle=NULL)
	{
		$this->__lastResult = array();
		
		if($resultHandle == NULL) $resultHandle = $this->__selectResult;
		
		$c = 0;
		while($r = call_user_func($this->fetchMethod,$resultHandle)){
			$this->__lastResult[] = $r;
			
			if(++$c == $numResults) break;		
		}
		
		if($c == 1 && $numResults == 1) $this->__lastResult = current($this->__lastResult);
		if($c == 0) $this->__lastResult = false;
		
		return $this->__lastResult;
	}

	public function select($query,$numResults=0)
	{
		if($this->getConnectionStatus() == false) return false;

		$command = "select";

		$this->__setLastQuery("$command $query");
		$this->__selectResult = mysql_query("$command $query",$this->__connection);
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
		$result = mysql_query("$command $query",$this->__connection);
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
		$result = mysql_query("$command $query",$this->__connection);
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
		$result = mysql_query("$command $query",$this->__connection);
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
