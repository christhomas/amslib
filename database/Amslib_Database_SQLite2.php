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
 * file: Amslib_Database_SQLite.php
 * title: Antimatter Database: SQLite2.x Implementation
 * description: A database object to centralise all interaction with a sqlite databse
 * 		into a single object which nicely hides some of the annoying repetitive
 * 		aspects of a database, whilst giving you a nice candy layer to deal with instead
 * version: 0.5
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Database_SQLite2 extends Amslib_Database
{	
	/**
	 * 	method:	makeConnection
	 *
	 * 	Connect to the SQLite database using various details
	 *
	 * 	todo:
	 * 		-	Need to move the database details to somewhere more secure (like inside the database!! ROFL!! joke, don't do that!!!!)
	 */
	protected function makeConnection()
	{
		$this->disconnect();
		
		try{ 
			$database = sqlite_open($this->loginDetails["database"], 0666, $error);
			
			if($database){
				$this->connection = $database;

				return true;
			}
		}catch(Exception $e){
			$this->fatalError("Connecting to database created an exception: error='$error'");
		}
		
		$this->fatalError("Failed to connect to database: error = '$error'");
		
		return false;
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
		$this->setFetchMethod("sqlite_fetch_array");
		
		if($connect) $this->connect();
	}
	
	public function escape($value)
	{
		return sqlite_escape_string($value);
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
	 * 
	 * FIXME: This description is from the mysql layer
	 */
	public function getRealResultCount()
	{
		return false;
	}
	
	/**
	 * 	method:	disconnect
	 *
	 * 	Disconnect from the Database
	 */
	public function disconnect()
	{
		if($this->connection) sqlite_close($this->connection);
		$this->connection = false;
	}

	public function setEncoding($encoding)
	{
		//	You can't do this on sqlite
	}
	
	public function getResults($numResults,$resultHandle=NULL)
	{
		$this->lastResult = array();
		
		if($resultHandle == NULL) $resultHandle = $this->getSearchResultHandle();
		
		for($a=0;$a<$numResults;$a++){
			$this->lastResult[] = sqlite_fetch_array($resultHandle);
		}
		
		if($this->optimiseSingleResult && $numResults == 1) $this->lastResult = current($this->lastResult);
		if(count($this->lastResult) == 0) $this->lastResult = false;

		return $this->lastResult;
	}

	public function select($query,$numResults=0)
	{
		if($this->getConnectionStatus() == false) return false;

		$this->setLastQuery("select $query");
		$this->selectResult = sqlite_query("select $query",$this->connection,SQLITE_ASSOC);
		if($this->debug) print("<pre>QUERY = 'select $query'<br/></pre>");

		$rowCount = sqlite_num_rows($this->selectResult);
		
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
		$result = sqlite_query("insert into $query",$this->connection);
		if($this->debug) print("<pre>QUERY = 'insert into $query'<br/></pre>");

		$this->lastInsertId = sqlite_last_insert_id();
		if($result && ($this->lastInsertId !== false)) return $this->lastInsertId;
		
		$this->lastInsertId = false;

		$this->fatalError("Transaction failed<br/>command = 'insert into'<br/>query = '$query'<br/><pre>result = '".print_r($result,true)."'</pre>lastInsertId = '$this->lastInsertId'<br/>mysql_insert_id() = '".mysql_insert_id()."'");
		
		return false;
	}

	public function update($query)
	{
		if($this->getConnectionStatus() == false) return false;

		$this->setLastQuery("update $query");
		$result = sqlite_query("update $query",$this->connection);
		if($this->debug) print("<pre>QUERY = 'update $query'<br/></pre>");

		if($result) return sqlite_changes() >= 0;

		$this->fatalError("Transaction failed<br/>command = 'update'<br/>query = '$query'");
		
		return false;
	}

	public function delete($query)
	{
		if($this->getConnectionStatus() == false) return false;
		
		$this->setLastQuery("delete from $query");
		$result = sqlite_query("delete from $query",$this->connection);
		if($this->debug) print("<pre>QUERY = 'delete from $query'<br/></pre>");

		if($result) return sqlite_changes();

		$this->fatalError("Transaction failed<br/>command = 'delete from'<br/>query = '$query'");

		return false;
	}
	
	public function error()
	{
		return sqlite_error_string(sqlite_last_error());
	}
	
	static public function &getInstance()
	{
		static $instance = NULL;
		
		if($instance === NULL) $instance = new Amslib_Database_SQLite();
		
		return $instance;
	}
}