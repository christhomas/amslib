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
 * 	class:	Amslib_Database_MySQL
 *
 *	group:	database
 *
 *	file:	Amslib_Database_MySQL.php
 *
 *	title:	Antimatter Database: MySQL library
 *
 *	description:	This is a small cover object which enhanced the original with some
 * 					new functionality aiming to solve the fatal_error problem but keep returning data
 *
 * 	todo: write documentation
 */
class Amslib_Database_MySQL extends Amslib_Database
{
	/**
	 * 	method:	setDebugOutput
	 *
	 * 	todo: write documentation
	 */
	protected function setDebugOutput($query)
	{
		if($this->debug && $this->errorState){
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
	public function connect()
	{
		$this->disconnect();
		$details = $this->getConnectionDetails();

		if($details){
			$this->databaseName = $details["database"];

			if($c = mysql_connect($details["server"],$details["username"],$details["password"],true))
			{
				if(!mysql_select_db($details["database"],$c)){
					$this->disconnect();
					$this->setDBErrors("Failed to open database requested '{$details["database"]}'");
				}else{
					$this->connection = $c;

					$this->setFetchMethod("mysql_fetch_assoc");
					$this->setEncoding(isset($details["encoding"]) ? $details["encoding"] : "utf8");
				}
			// Replace these errors with Amslib_Translator codes instead (language translation)
			}else $this->setDBErrors("Failed to connect to database: {$details["database"]}<br/>");
		// Replace these errors with Amslib_Translator codes instead (language translation)
		}else $this->setDBErrors("Failed to find the database connection details, check this information<br/>");
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

	/**
	 * 	method:	setEncoding
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	escape
	 *
	 * 	todo: write documentation
	 */
	public function escape($value)
	{
		if($value === NULL)		return $value;
		//	Simple numeric checks to quickly escape them without using the mysql functionality
		if(is_int($value))		return intval($value);
		if(is_bool($value))		return intval($value);
		if(is_float($value))	return floatval($value);

		//	from this point on, the value must be a string
		if(!is_string($value)){
			Amslib::errorLog("stack_trace,2,*","value is not a string",$value);
		}

		if(!$this->getConnectionStatus()){
			print("unsafe string escape: database not connected<br/>\n");
			print("it is not safe to continue, corruption might occur<br/>\n");
			Amslib::errorLog("stack_trace,2","not connected to database");
			die("DYING");
		}

		return @mysql_real_escape_string($value);
	}

	/**
	 * 	method:	unescape
	 *
	 * 	todo: write documentation
	 *
	 * 	note: this is generic functionality, it's not mysql specific
	 */
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

	/**
	 * 	method:	fixColumnEncoding
	 *
	 * 	todo: write documentation
	 *
	 * 	note: this method isn't really mysql specific, apart from calling select, escape and update, these could be API specific but they are abstracted anyway
	 */
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

					$this->update("$table set $column='$string' where $primaryKey='{$c[$primaryKey]}'");
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
		$this->storeSearchHandle();

		$count = $this->selectValue("c","FOUND_ROWS() as c",1,true);

		$this->restoreSearchHandle();

		return $count;
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
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
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

	/**
	 * 	method:	getDBList
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function getDBList()
	{
		return $this->select("distinct table_schema as database_name from information_schema.tables");
	}

	/**
	 * 	method:	getDBTables
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function getDBTables($database_name=NULL)
	{
		$filter = "";
		if($database_name && is_string($database_name)){
			$database_name = $this->escape($database_name);

			$filter = "where table_schema='$database_name'";
		}

		return $this->select("distinct table_name,table_schema as database_name from information_schema.tables $filter");
	}

	/**
	 * 	method:	getDBColumns
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
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

	/**
	 * 	method:	getDBTableFields
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function getDBTableFields($table)
	{
		$table = $this->escape($table);

		return $this->select("column_name from Information_Schema.Columns where table_name='$table'");
	}

	/**
	 * 	method:	getDBTableRowCount
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function getDBTableRowCount($database,$table)
	{
		$database	=	$this->escape($database);
		$table		=	$this->escape($table);

		$this->select("SQL_CALC_FOUND_ROWS * from $database.$table limit 1");

		return $this->getRealResultCount();
	}

	/**
	 * 	method:	setDBErrors
	 *
	 * 	todo: write documentation
	 */
	public function setDBErrors($data,$error=NULL,$errno=NULL,$insert_id=NULL)
	{
		parent::setDBErrors(
			$data,
			$error ? $error : mysql_error($this->connection),
			$errno ? $errno : mysql_errno($this->connection),
			$insert_id ? $insert_id : mysql_insert_id($this->connection)
		);
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

	/**
	 * 	method:	getResults
	 *
	 * 	todo: write documentation
	 */
	public function getResults($numResults,$resultHandle=NULL,$optimise=false)
	{
		$this->lastResult = array();

		if(!$resultHandle) $resultHandle = $this->getSearchResultHandle();
		if(!$resultHandle) return false;

		for($a=0;$a<$numResults;$a++){
			//	BUG: surely this should use $this->fetchMethod ?? wasn't that the whole point?
			$r = mysql_fetch_assoc($resultHandle);
			if(!$r) break;
			$this->lastResult[] = $r;

			//	Stop when you've got the number of results you need
			if(count($this->lastResult) >= $numResults) break;
		}

		if($optimise && $numResults == 1) $this->lastResult = current($this->lastResult);
		if(count($this->lastResult) == 0) $this->lastResult = false;

		return $this->lastResult;
	}

	/**
	 * 	method:	releaseMemory
	 *
	 * 	todo: write documentation
	 *
	 * 	If we supply a result handle, free that, or obtain the handle created when you last selected something
	 */
	public function releaseMemory($resultHandle=NULL)
	{
		if(!$resultHandle) $resultHandle = $this->getSearchResultHandle();

		if(!$resultHandle) error_log(__METHOD__.": trying to free an invalid handle");
		return $resultHandle ? mysql_free_result($resultHandle) : false;
	}

	/**
	 * 	method:	beginTransaction
	 *
	 * 	todo: write documentation
	 *
	 * 	note: should I use "start transaction" here instead of just "begin" cause some of the examples I saw it was not clear or obvious
	 */
	public function beginTransaction()
	{
		return mysql_query("begin");
	}

	/**
	 * 	method:	commitTransaction
	 *
	 * 	todo: write documentation
	 */
	public function commitTransaction()
	{
		return mysql_query("commit");
	}

	/**
	 * 	method:	rollbackTransaction
	 *
	 * 	todo: write documentation
	 */
	public function rollbackTransaction()
	{
		return mysql_query("rollback");
	}

	/**
	 * 	method:	query
	 *
	 * 	todo: write documentation
	 */
	public function query($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);

		$this->setDebugOutput($query);

		if(!$result){
			$this->setDBErrors($query);
			return false;
		}

		return true;
	}

	/**
	 * 	method:	select
	 *
	 * 	todo: write documentation
	 */
	public function select($query,$numResults=0,$optimise=false)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$query = "select $query";

		$this->setLastQuery($query);
		$this->selectResult = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		if($this->selectResult){
			//	If you don't request a number of results, use the maximum number we could possible accept
			//	NOTE: you'll run out of memory a long time before you reach this count
			if($numResults == 0) $numResults = PHP_INT_MAX;

			return $this->getResults($numResults,$this->selectResult,$optimise);
		}

		$this->setDBErrors($query);

		return false;
	}

	/**
	 * 	method:	select2
	 *
	 * 	todo: write documentation
	 */
	public function select2($query,$numResults=0,$optimise=false)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$query = str_replace("SQL_CALC_FOUND_ROWS","",$query);

		$numResults = intval($numResults);

		//	These two rows are "dangerous" cause i'm not sure if the final query will be broken or not :(
		//	NOTE: if you try hard to avoid problems, it should be ok and a lot faster
		$query = "select SQL_CALC_FOUND_ROWS $query";
		//	IMPORTANT NOTE:
		//	****	there is a side effect of using select2 with a $numResults, is that the total result set is now
		//	****	not "streamable" as in, you can't select 100,000 results, numResults=1000 and get more results afterwards
		//	****	cause the limit clause will effectively return only 1000 results and the other 99,000 results will
		//	****	not be accessible
		if($numResults > 0 && strpos(strtolower($query)," limit ") === false) $query = "$query limit $numResults";

		$this->setLastQuery($query);
		$this->selectResult = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		if($this->selectResult){
			//	If you don't request a number of results, use the maximum number we could possible accept
			//	NOTE: you'll run out of memory a long time before you reach this count
			if($numResults == 0) $numResults = PHP_INT_MAX;

			return $this->getResults($numResults,$this->selectResult,$optimise);
		}

		$this->setDBErrors($query);

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
	 * 	method:	selectField
	 *
	 * 	todo: write documentation
	 */
	public function selectField($table,$value,$field,$count=1,$optimise=true)
	{
		$table = $this->escape($table);
		$field = $this->escape($field);

		if(is_string($value))	$value = is_string($value);
		if(is_numeric($value))	$value = intval($value);

		return $this->selectValue($field,"$field from $table where $field='$value'");
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

	/**
	 * 	method:	selectValue
	 *
	 * 	todo: write documentation
	 *
	 * 	notes:
	 * 		Anxo has explained that perhaps it's unnecessary that in the query to put the field if you are going to put
	 * 		the field you want in the first parameter, so in the query you can just put a "$field $query" and it'll
	 * 		select only what you want, without duplication.  He's clever sometimes.
	 */
	public function selectValue($field,$query,$numResults=0,$optimise=false)
	{
		$values = $this->select($query,$numResults,$optimise);

		if($numResults == 1 && $optimise){
			return isset($values[$field]) ? $values[$field] : NULL;
		}

		//	TODO: This hasn't been tested yet, it might not return exactly what I want
		if($numResults != 1 && !$optimise){
			//	FIXME? Why am I optimising the array, when optimise is being tested for false?
			//	NOTE: I think the reason I never found this issue before was I always using 1,true for numResults/optimise
			return Amslib_Array::pluck($values,$field);
		}

		return $values;
	}

	/**
	 * 	method:	selectRow
	 *
	 * 	todo: write documentation
	 */
	public function selectRow($query)
	{
		return $this->select($query,1,true);
	}

	/**
	 * 	method:	insert
	 *
	 * 	todo: write documentation
	 */
	public function insert($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$query = "insert into $query";

		//	FIXME: there is a memory leak in either setLastQuery,setDebugOutput,setDBErrors
		//	NOTE: this leak leads to a LOT of memory being leaked on large scripts processing tens of thousands of rows
		//	NOTE: when the methods are commented out, the leak disappears and it uses 32MB (Approx)
		//	NOTE: so something bad is happening here
		//$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);

		//$this->setDebugOutput($query);

		if(!$result){
			$this->lastInsertId = false;
			$this->setDBErrors($query);
			return false;
		}

		$this->lastInsertId = mysql_insert_id($this->connection);

		return $this->lastInsertId;
	}

	/**
	 * 	method:	update
	 *
	 * 	todo: write documentation
	 */
	public function update($query,$allow_zero=true)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$query = "update $query";

		//$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);
		//$this->setDebugOutput($query);

		if(!$result){
			$this->setDBErrors($query);
			return false;
		}

		$affected = mysql_affected_rows($this->connection);

		return $allow_zero ? $affected >= 0 : $affected > 0;
	}

	/**
	 * 	method:	delete
	 *
	 * 	todo: write documentation
	 */
	public function delete($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;

		$query = "delete from $query";

		$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		if(!$result){
			$this->setDBErrors($query);
			return false;
		}

		return mysql_affected_rows($this->connection) >= 0;
	}
}