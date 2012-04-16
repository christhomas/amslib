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
 * title: Antimatter Database: MySQL library version 3
 * description: This is a small cover object which enhanced the original with some
 * new functionality aiming to solve the fatal_error problem but keep returning data
 * version: 3.0
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Database_MySQL extends Amslib_Database
{
	protected $errors;

	protected function setDebugOutput($query)
	{
		if($this->debug){
			Amslib_Keystore::set("db_query[{$this->seq}][".microtime(true)."]",Amslib::var_dump($query,true));
		}
	}

	/**
	 * 	method:	connect
	 *
	 * 	Connect to the MYSQL database using various details
	 *
	 * 	todo:
	 * 		-	Need to move the database details to somewhere more secure (like inside the database!! ROFL!! joke, don't do that!!!!)
	 */
	protected function connect()
	{
		$this->disconnect();
		$details = $this->getConnectionDetails();

		if($details){
			$this->databaseName = $details["database"];

			if($c = mysql_connect($details["server"],$details["username"],$details["password"],true))
			{
				if(!mysql_select_db($details["database"],$c)){
					$this->disconnect();
					$this->setError("Failed to open database requested '{$details["database"]}'");
				}else{
					$this->connection = $c;

					$this->setFetchMethod("mysql_fetch_assoc");
					$this->setEncoding(isset($details["encoding"]) ? $details["encoding"] : "utf8");
				}
			// Replace these errors with Amslib_Translator codes instead (language translation)
			}else $this->setError("Failed to connect to database: {$details["database"]}<br/>");
		// Replace these errors with Amslib_Translator codes instead (language translation)
		}else $this->setError("Failed to find the database connection details, check this information<br/>");
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

		$this->errors = array();

		//	TODO: we should implement a try/catch block to easily catch disconnected databases
		if($connect) $this->connect();
	}

	public function setEncoding($encoding)
	{
		if($this->getConnectionStatus() == false) return;

		$allowedEncodings = array("utf8","latin1");

		if(in_array($encoding,$allowedEncodings)){
			mysql_set_charset($encoding,$this->connection);
		}else{
			die(	"(".basename(__FILE__)." / FATAL ERROR): Your encoding ($encoding) is wrong, this can cause database corruption. ".
					"I'm sorry dave, but I can't allow you to do that<br/>".
					"allowed encodings = <pre>".implode(",",$allowedEncodings)."</pre>");
		}
	}

	public function escape($value)
	{
		return $this->getConnectionStatus()
			? mysql_real_escape_string($value)
			: die("unsafe string escape: database not connected, backtrace: ".Amslib::var_dump(Amslib::backtrace(1,3,"file","line"),true));
	}

	public function unescape($results,$keys="")
	{
		if(!$results || !is_array($results)) return $results;

		if(Amslib_Array::isMulti($results)){
			if($keys == "") $keys = array_keys(current($results));

			foreach($results as &$r){
				$r = Amslib_Array::stripSlashesSingle($r,$keys);
			}
		}else{
			if($keys == "") $keys = array_keys($results);

			$results = Amslib_Array::stripSlashesSingle($results,$keys);
		}

		return $results;
	}

	public function fixColumnEncoding($src,$dst,$table,$primaryKey,$column)
	{
		$encoding_map = array(
			"latin1"	=>	"latin1",
			"utf8"		=>	"utf-8"
		);

		if(!isset($encoding_map[$src])) return false;
		if(!isset($encoding_map[$dst])) return false;

		$this->setEncoding($src);
		$data = $this->select("$primaryKey,$column from $table");

		if(!empty($data)){
			$this->setEncoding($dst);

			$ic_src = $encoding_map[$src];
			$ic_dst = $encoding_map[$dst];

			foreach($data as $c){
				$string = iconv($ic_src,$ic_dst,$c[$column]);

				if($string && is_string($string) && strlen($string)){
					$string = $this->escape($string);

					$this->update("$table set $column=\"$string\" where $primaryKey=\"{$c[$primaryKey]}\"");
				}
			}

			return true;
		}
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
		$result = $this->select("FOUND_ROWS() as num_results",1,true);

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
		$database	=	$this->escape($database);
		$table		=	$this->escape($table);

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

	public function getDBList()
	{
		return $this->select("distinct table_schema as database_name from information_schema.tables");
	}

	public function getDBTables($database_name=NULL)
	{
		$filter = "";
		if($database_name && is_string($database_name)){
			$database_name = $this->escape($database_name);

			$filter = "where table_schema='$database_name'";
		}

		return $this->select("distinct table_name,table_schema as database_name from information_schema.tables $filter");
	}

	public function getDBColumns($database_name=NULL,$table_name=NULL)
	{
		$filter = array();

		if($database_name && is_string($database_name)){
			$database_name = $this->escape($database_name);

			$filter[] = "table_schema='$database_name'";
		}

		if($table_name && is_string($table_name)){
			$table_name = $this->escape($table_name);

			$filter[] = "table_name='$table_name'";
		}

		$filter = count($filter) ? "where ".implode(" AND ",$filter) : "";

$query=<<<QUERY
		distinct column_name, table_name, table_schema as database_name
		from information_schema.columns
		$filter
		order by database_name,table_name,ordinal_position
QUERY;

		return $this->select($query);
	}

	public function getDBTableFields($table)
	{
		$table = $this->escape($table);

		return $this->select("column_name from Information_Schema.Columns where table_name='$table'");
	}

	public function getDBTableRowCount($database,$table)
	{
		$database	=	$this->escape($database);
		$table		=	$this->escape($table);

		$this->select("SQL_CALC_FOUND_ROWS * from $database.$table limit 1");

		return $this->getRealResultCount();
	}

	/**
	 * 	method:	disconnect
	 *
	 * 	Disconnect from the Database
	 */
	public function disconnect()
	{
		if($this->connection) mysql_close($this->connection);
		$this->connection = false;
	}

	public function getResults($numResults,$resultHandle=NULL,$optimise=false)
	{
		$this->lastResult = array();

		if(!$resultHandle) $resultHandle = $this->getSearchResultHandle();
		if(!$resultHandle) return false;

		for($a=0;$a<$numResults;$a++){
			$r = mysql_fetch_assoc($resultHandle);
			if(!$r) break;
			$this->lastResult[] = $r;
		}

		if($optimise && $numResults == 1) $this->lastResult = current($this->lastResult);
		if(count($this->lastResult) == 0) $this->lastResult = false;

		return $this->lastResult;
	}

	public function query($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);

		$this->setDebugOutput($query);

		if(!$result){
			$this->setErrors($query);
			return false;
		}

		return true;
	}

	public function select($query,$numResults=0,$optimise=false)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$query = "select $query";

		$this->setLastQuery($query);
		$this->selectResult = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		if($this->selectResult){
			$rowCount = mysql_num_rows($this->selectResult);

			if($numResults == 0) $numResults = $rowCount;

			return $this->getResults($numResults,$this->selectResult,$optimise);
		}

		$this->setErrors($query);

		return false;
	}

	/**
	 * method: selectColumn
	 *
	 * Obtain a single column from the result of an SQL query, optionally optimised
	 * to a single value if there is only one result.
	 *
	 * operations:
	 * 	-	do the sql query
	 * 	-	if the column parameter is a string, pluck that column from each result
	 * 	-	if there is only one result and optimisation was requested, make the first array value as the return value
	 * 	-	return the final value, either an array of values for that column, or a single variable containing the first value
	 */
	public function selectColumn($query,$column=false,$numResults=0,$optimise=false)
	{
		$result = $this->select($query,$numResults,$optimise);

		if(is_string($column)) $result = Amslib_Array::pluck($result,$column);

		if(count($result) == 1 && $optimise) $result = current($result);

		return $result;
	}

	/**
	 * method: selectRandom
	 *
	 * Select a number of random rows from a table
	 *
	 * I'd like to thank Jay Paroline for this little snippet of SQL
	 * link: http://forums.mysql.com/read.php?132,185266,194715
	 *
	 * NOTES: this apparently works nicely with large tables
	 */
	public function selectRandom($table,$pkName,$count)
	{
		$table	=	(string)$this->escape($table);
		$pkName	=	(string)$this->escape($pkName);
		$count	=	(int)$count;

$query=<<<QUERY
			*
		FROM
			$table
		JOIN
			(SELECT FLOOR(MAX($table.$pkName)*RAND()) AS RID FROM $table) AS x
			ON $table.$pkName >= x.RID
		LIMIT
			$count;
QUERY;

		return $this->select($query);
	}

	public function selectValue($field,$query,$numResults=0,$optimise=false)
	{
		$values = $this->select($query,$numResults,$optimise);

		if($numResults == 1 && $optimise){
			$values = array_shift($values);
			return isset($values[$field]) ? $values[$field] : NULL;
		}

		//	TODO: This hasn't been tested yet, it might not return exactly what I want
		if($numResults != 1 && !$optimise){
			return Amslib_Array::pluck($values,$field);
		}

		return $values;
	}

	public function insert($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$query = "insert into $query";

		$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);

		$this->setDebugOutput($query);

		if(!$result){
			$this->lastInsertId = false;
			$this->setErrors($query);
			return false;
		}

		$this->lastInsertId = mysql_insert_id($this->connection);

		return $this->lastInsertId;
	}

	public function update($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$query = "update $query";

		$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		if(!$result){
			$this->setErrors($query);
			return false;
		}

		return mysql_affected_rows($this->connection) >= 0;
	}

	public function delete($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$query = "delete from $query";

		$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		if(!$result){
			$this->setErrors($query);
			return false;
		}

		return mysql_affected_rows($this->connection) >= 0;
	}

	public function setErrors($query)
	{
		$this->errors[] = array(
			"db_failure"		=>	true,
			"db_query"			=>	$query,
			"db_error"			=>	mysql_error($this->connection),
			"db_error_num"		=>	mysql_errno($this->connection),
			"db_last_insert"	=>	$this->lastInsertId,
			"db_insert_id"		=>	mysql_insert_id($this->connection),
			"db_location"		=>	Amslib_Array::filterKey(array_slice(debug_backtrace(),0,5),array("file","line")),
		);
	}

	public function setError($error)
	{
		$this->errors[] = $error;
	}

	public function getErrors()
	{
		return $this->errors;
	}
}