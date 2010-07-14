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
 * file: Amslib_Database_MySQL.php
 * title: Antimatter Database: MySQL library
 * description: A database object to centralise all interaction with a mysql databse
 * 		into a single object which nicely hides some of the annoying repetitive
 * 		aspects of a database, whilst giving you a nice candy layer to deal with instead
 * version: 2.5
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

/**
 * 	class:	Amslib_Database_MySQL
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
class Amslib_Database_MySQL extends Amslib_Database
{
	protected function setEncoding($encoding)
	{
		$allowedEncodings = array("utf8");
		
		if(in_array($encoding,$allowedEncodings)){
			mysql_query("SET NAMES '$encoding'",$this->connection);
			mysql_query("SET CHARACTER SET $encoding",$this->connection);
		}else{
			die(	"FATAL ERROR: Your encoding is wrong, this can cause database corruption.".
					"I can't allow you to continue<br/>".
					"allowed encodings = <pre>".print_r($allowedEncodings,true)."</pre>");
		}
	}
	
	/**
	 * 	method:	makeConnection
	 *
	 * 	Connect to the MYSQL database using various details
	 *
	 * 	todo:
	 * 		-	Need to move the database details to somewhere more secure (like inside the database!! ROFL!! joke, don't do that!!!!)
	 */
	protected function makeConnection()
	{
		$this->disconnect();

		if($this->loginDetails){
			if($c = mysql_connect($this->loginDetails["server"],$this->loginDetails["username"],$this->loginDetails["password"],true))
			{
				if(!mysql_select_db($this->loginDetails["database"],$c)) $this->disconnect();
				else{
					$this->connection = $c;
				}
			// Replace these errors with PHPTranslator codes instead (language translation)
			}else $this->fatalError("Failed to connect to database: {$this->loginDetails["database"]}<br/>");
		// Replace these errors with PHPTranslator codes instead (language translation)
		}else $this->fatalError("Failed to find the database connection details, check this information<br/>");
		
		$this->loginDetails = NULL;
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
		parent::__construct();
		$this->setFetchMethod("mysql_fetch_assoc");
		$this->setEncoding("utf8");
		
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
	
	/**
	 * 	method:	disconnect
	 *
	 * 	Disconnect from the Database
	 */
	public function disconnect()
	{
		mysql_close($this->connection);
		$this->connection = false;
	}
	
	public function getResults($numResults,$resultHandle=NULL)
	{
		$this->lastResult = array();
		
		if($resultHandle == NULL) $resultHandle = $this->getSearchResultHandle();
		
		for($a=0;$a<$numResults;$a++){
			$this->lastResult[] = mysql_fetch_assoc($resultHandle);
		}
		
		if($this->optimiseSingleResult && $numResults == 1) $this->lastResult = current($this->lastResult);
		if(count($this->lastResult) == 0) $this->lastResult = false;

		return $this->lastResult;
	}

	public function select($query,$numResults=0)
	{
		if($this->getConnectionStatus() == false) return false;

		$this->setLastQuery("select $query");
		$this->selectResult = mysql_query("select $query",$this->connection);
		if($this->debug) print("<pre>QUERY = 'select $query'<br/></pre>");

		$rowCount = mysql_num_rows($this->selectResult);
		
		if($this->selectResult && $rowCount >= 0){
			if($numResults == 0) $numResults = $rowCount;
			return $this->getResults($numResults);
		}

		$this->fatalError("Transaction failed<br/>command = 'select'<br/>query = '$query'");

		return false;
	}

	public function insert($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$this->setLastQuery("insert into $query");
		$result = mysql_query("insert into $query",$this->connection);
		if($this->debug) print("<pre>QUERY = 'insert into $query'<br/></pre>");

		$this->lastInsertId = mysql_insert_id();
		if($result && ($this->lastInsertId !== false)) return $this->lastInsertId;
		
		$this->lastInsertId = false;

		$this->fatalError("Transaction failed<br/>command = 'insert into'<br/>query = '$query'<br/><pre>result = '".print_r($result,true)."'</pre>lastInsertId = '$this->lastInsertId'<br/>mysql_insert_id() = '".mysql_insert_id()."'");

		return false;
	}

	public function update($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$this->setLastQuery("update $query");
		$result = mysql_query("update $query",$this->connection);
		if($this->debug) print("<pre>QUERY = 'update $query'<br/></pre>");

		if($result) return mysql_affected_rows() >= 0;

		$this->fatalError("Transaction failed<br/>command = 'update'<br/>query = '$query'");

		return false;
	}

	public function delete($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$this->setLastQuery("delete from $query");
		$result = mysql_query("delete from $query",$this->connection);
		if($this->debug) print("<pre>QUERY = 'delete from $query'<br/></pre>");

		if($result) return mysql_affected_rows();

		$this->fatalError("Transaction failed<br/>command = 'delete from'<br/>query = '$query'");

		return false;
	}
	
	public function error()
	{
		return mysql_error();
	}
	
	public function &getInstance($connect=true)
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new Amslib_Database_MySQL($connect);

		return $instance;
	}
}
?>