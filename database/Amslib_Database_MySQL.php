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
	const FETCH_ASSOC		=	"mysql_fetch_assoc";
	const FETCH_ARRAY		=	"mysql_fetch_array";

	/**
	 * 	method:	__construct
	 *
	 * 	This method is called when the database object is created, it connects to the database by default
	 */
	public function __construct($connect=true)
	{
		parent::__construct($connect);

		$this->setFetchMethod(self::FETCH_ASSOC);
	}

	/**
	 *  method:	getInstance
	 *
	 *  note: When launching the translator object with the option database,
	 *  when launching the load function an instance of this object is needed.
	 *  I do not know if you are aware of the problem and you have a way to
	 *  cope with it.  I assume you are not aware of the problem since you
	 *  have to do several installation to see it(in my case) so I am adding
	 *  this function.  If you don't want getInstance in here I will create
	 *  a wrapper "capsule" object for my projects.
	 *
	 *  Chris: alfonso, I don't have any idea what you mean, sorry
	 */
	static public function getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	public function isHandle($handle)
	{
		return $handle && is_resource($handle) && stristr(get_resource_type($handle), "mysql");
	}

	/**
	 * 	method:	freeHandle
	 *
	 * 	todo: write documentation
	 *
	 * 	If we supply a result handle, free that, or obtain the handle created when you last selected something
	 */
	public function freeHandle($handle=NULL)
	{
		if(!$this->isHandle($handle)){
			$handle = $this->getHandle();
		}

		if(!$this->isHandle($handle)){
			$this->debug("DEBUG",__METHOD__,": trying to free an invalid handle");

			return false;
		}

		return mysql_free_result($handle);
	}

	/**
	 * 	method:	setFetchMethod
	 *
	 * 	todo: write documentation
	 */
	public function setFetchMethod($method)
	{
		if(function_exists($method)){
			$this->fetchMethod = $method;
		}else{
			$message = "Your fetch method is not valid, ALL database queries will fail, this is not acceptable";
			$this->debug(__METHOD__,$message);
			die($message);
		}
	}

	/**
	 * 	method:	setEncoding
	 *
	 * 	todo: write documentation
	 */
	public function setEncoding($encoding)
	{
		if($this->isConnected() == false) return;

		$allowedEncodings = array("utf8","latin1");

		if(!in_array($encoding,$allowedEncodings)){
			$message =
				"(".basename(__FILE__)." / FATAL ERROR): Your encoding ($encoding) is wrong, ".
				"this can cause database corruption.".
				"I'm sorry dave, but I can't allow you to do that<br/>".
				"allowed encodings = <pre>".implode(",",$allowedEncodings)."</pre>";
			$this->debug("ERROR",$message);
			die($message);
		}

		mysql_set_charset($encoding,$this->connection);
	}

	/**
	 * 	method:	escape
	 *
	 * 	todo: write documentation
	 */
	public function escape($value)
	{
		if($value === NULL)		return $value;
		//check if a string contains an integer representation and convert it into an integer
		if((string)(int)$value == $value) return (int)$value;
		//	Simple numeric checks to quickly escape them without using the mysql functionality
		if(is_int($value))		return intval($value);
		if(is_bool($value))		return intval($value);
		if(is_float($value))	return floatval($value);

		//	from this point on, the value must be a string
		if(!is_string($value)){
			$this->debug("stack_trace,2,*","value is not a string",$value);
		}

		if(!$this->isConnected()){
			print(__METHOD__.", unsafe string escape: database not connected<br/>\n");
			print("it is not safe to continue, corruption might occur<br/>\n");
			$this->debug("stack_trace,2","not connected to database");
			die("DYING");
		}

		ob_start();
		$value = mysql_real_escape_string($value);
		$error = ob_get_clean();

		if(strlen($error)){
			//	I'm not sure what I should do here, so I'll just log whatever
			//	it outputs and improve the error control when I find out what I am dealing with
			$this->debug(__METHOD__,$error);
		}

		return $value;
	}

	/**
	 * method:	getRealResultCount
	 *
	 * Obtain a real row count from the previous query, if you use limit and want to know how many
	 * a query WOULD return without the limit, you can use this method, but in the query, you need
	 * to put SQL_CALC_FOUND_ROWS at the beginning of the query
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
		$this->pushHandle();

		$count = $this->selectValue("c","FOUND_ROWS() as c",1,true);

		$this->popHandle();

		return $count;
	}

	/**
	 * 	method:	setError
	 *
	 * 	todo: write documentation
	 */
	public function setErrors($data)
	{
		$args = array($data);

		if($this->isConnected()){
			$args[] = mysql_error($this->connection);
			$args[] = mysql_errno($this->connection);
			$args[] = mysql_insert_id($this->connection);
		}

		call_user_func_array("parent::setError",$args);
	}

	/**
	 * 	method:	connect
	 *
	 * 	Connect to the MYSQL database using various details
	 *
	 * 	todo:
	 * 		-	Need to move the database details to somewhere more secure (like inside the database!! ROFL!! joke, don't do that!!!!)
	 */
	public function connect($details=NULL)
	{
		list($details,$password) = Amslib_Array::valid($this->setConnectionDetails($details)) + array(NULL,NULL);

		$this->disconnect();

		$valid = Amslib_Array::hasKeys($details,array("server","username","database","encoding")) && strlen($password);

		if($valid){
			ob_start();
			if($c = mysql_connect($details["server"],$details["username"],$password,true))
			{
				if(!mysql_select_db($details["database"],$c)){
					$this->disconnect();
					$this->debug("DATABASE","Connection to database worked however Credentials don't appear to give access to database '{$details["database"]}'",$details);
				}else{
					$this->connection = $c;

					$this->setEncoding($details["encoding"]);

					return true;
				}
			}else{
				$this->debug("DATABASE","Connection to database failed",$details);
			}
			$output = ob_get_clean();

			//	Cause obviously, I don't want to log the password
			if(strlen($output)){
				$this->debug("DATABASE","Connection to database failed and produced output = ",$output,$details);
			}
		}else{
			$this->debug("DATABASE","Connection to database failed with the provided credentials",$details);
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
		if($this->connection){
			mysql_close($this->connection);
		}

		$this->connection = false;
	}

	/**
	 * 	method:	query
	 *
	 * 	todo: write documentation
	 */
	public function query($query,$returnBoolean=false)
	{
		$result = false;

		if($this->isConnected()){
			$this->setLastQuery($query);
			$result = mysql_query($query,$this->connection);
			$this->debug("QUERY",$query);

			if(!$result){
				$this->setError($query);
			}
		}

		return $returnBoolean ? !!$result : $result;
	}

	/**
	 * 	method:	select
	 *
	 * 	todo: write documentation
	 */
	public function select($query,$numResults=0,$optimise=false)
	{
		$handle = $this->query("select $query");

		//	record error information?
		if(!$handle) return false;

		$handle = $this->setHandle($handle);

		//	If you don't request a number of results, use the maximum number we could possible accept
		//	NOTE: you'll run out of memory a long time before you reach this count
		if($numResults == 0) $numResults = PHP_INT_MAX;

		return $this->getResults($numResults,$handle,$optimise);
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
	 * 	method:	insert
	 *
	 * 	todo: write documentation
	 */
	public function insert($query)
	{
		$this->lastInsertId = false;

		return $this->query("insert into $query",true)
			? $this->getLastInsertId()
			: false;
	}

	/**
	 * 	method: insertFields
	 *
	 * 	A method to insert into a table an array of fields which is built and imploded beforehand
	 *
	 * 	params:
	 * 		$table - The database table to insert into
	 * 		$fields - An array of fields to insert
	 *
	 *  returns:
	 *  	-	Boolean false if the fields returned was not valid
	 *  	-	Boolean false if the insert failed
	 *  	-	An integer primary key of the row that was inserted into the table
	 */
	public function insertFields($table,$fields)
	{
		$this->setErrorStackDepth(6);
		
		$fields = $this->buildFields($fields);

		if(!$fields) return false;

		$table = $this->escape($table);

		return $this->insert("$table set $fields");
	}

	/**
	 * 	method:	update
	 *
	 * 	todo: write documentation
	 */
	public function update($query,$allow_zero=true)
	{
		return $this->query("update $query",true)
			? $this->getLastAffectedCount(true,$allow_zero)
			: false;
	}

	/**
	 * 	method: updateFields
	 *
	 * 	A method to update a table with an array of fields which is built and imploded beforehand
	 *
	 * 	params:
	 * 		$table - The database table to insert into
	 * 		$fields - An array of fields to insert
	 * 		$allow_zero - Whether or not zero updates is to be ignored or treated like a failure
	 *
	 *  returns:
	 *  	-	Boolean false if the fields returned was not valid
	 *  	-	Boolean false if the insert failed
	 *  	-	An integer primary key of the row that was inserted into the table
	 */
	public function updateFields($table,$fields,$allow_zero=true)
	{
		$this->setErrorStackDepth(6);
		
		$fields = $this->buildFields($fields);

		if(!$fields) return false;

		$table = $this->escape($table);

		return $this->update("$table set $fields",$allow_zero);
	}

	/**
	 * 	method:	delete
	 *
	 * 	todo: write documentation
	 */
	public function delete($query)
	{
		return $this->query("delete from $query")
			? $this->getLastAffectedCount()
			: false;
	}

	/**
	 * 	method:	getLastInsertId
	 *
	 * 	todo: write documentation
	 */
	public function getLastInsertId()
	{
		return $this->lastInsertId = mysql_insert_id($this->connection);
	}

	/**
	 * 	method:	getLastAffectedCount
	 *
	 * 	todo: write documentation
	 */
	public function getLastAffectedCount($boolean=true,$allow_zero=true)
	{
		$count = mysql_affected_rows($this->connection);

		return $boolean ? ($allow_zero ? $count >= 0 : $count > 0) : $count;
	}

	/**
	 * 	method:	getResults
	 *
	 * 	todo: write documentation
	 */
	public function getResults($count,$handle=NULL,$optimise=false)
	{
		$this->lastResult = array();

		//	Make sure the result handle is valid
		if(!$this->isHandle($handle)) $handle = $this->getHandle();
		if(!$this->isHandle($handle)) return false;

		for($a=0;$a<$count;$a++){
			$row = call_user_func($this->fetchMethod,$handle);
			//	We have no results left to obtain
			if(!$row) break;
			//	Otherwise record the result
			$this->lastResult[] = $row;
		}

		//	optimise means remove the silly outer layer, this method normally returns an array
		//	of results, but when there is one result, it's kind of silly, it's an array
		//	containing a single array.  So optimise returns the single result as the returned variable
		//	Example: array(array("id_row"=>1,"field_1"=>"hello")) will become array("id_row"=>1,"field_1"=>"hello")
		//	So you can see it's removed the outer array
		if($optimise && $count == 1){
			$this->lastResult = current($this->lastResult);
		}

		//	No results? return false! I'm not sure whether returning false is a good idea, since it has some "meaning"
		//	Perhaps return NULL = no results and return false = failure (failure has meaning, it means something went wrong)
		if(empty($this->lastResult)){
			$this->lastResult = false;
		}

		return $this->lastResult;
	}

	/**
	 * 	method:	begin
	 *
	 * 	todo: write documentation
	 */
	public function begin()
	{
		return $this->query("begin");
	}

	/**
	 * 	method:	commit
	 *
	 * 	todo: write documentation
	 */
	public function commit()
	{
		return $this->query("commit");
	}

	/**
	 * 	method:	rollback
	 *
	 * 	todo: write documentation
	 */
	public function rollback()
	{
		return $this->query("rollback");
	}

	/**
	 * 	method: countRows
	 *
	 * 	A quick method to count the number of rows by selecting a single field, this
	 * 	is quite a limited method, but in the future I can add more features to it
	 *
	 * 	parameters:
	 * 		$field	-	The field to select
	 * 		$table	-	The table to query
	 *
	 * 	returns:
	 * 		Returns errors [-1 (field),-2 (table)] when they are invalid,
	 * 		a positive integer when successful or boolean false for a general failure
	 */
	public function countRows($field,$table)
	{
		$field = $this->escape($field);
		$table = $this->escape($table);

		if(!$field || !strlen($field) || is_numeric($field)) return -1;
		if(!$table || !strlen($table) || is_numeric($table)) return -2;

		return $this->selectValue("c","count($field) as c from $table",1,true);
	}
}