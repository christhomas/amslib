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
 *	title:	Antimatter Database PDO Wrapper
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

	protected $db_type;

	/**
	 * 	variable:	$table
	 * 	type:		array
	 *
	 * 	An array of table names, each key returns the actual name in the database
	 */
	protected $table = array();

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
	protected $connection			=	false;
	protected $connectionDetails	=	false;

	protected $selectHandle			=	false;
	protected $selectStack			=	array();

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
		//	Probably you're not setting this, so this will always return an invalid result
		return $this->lastResult;
	}

	/**
	 * 	method:	getLastInsertId
	 *
	 * 	todo: write documentation
	 */
	protected function getLastInsertId()
	{
		switch($this->db_type) {
			case 'SQLITE': // <-- no { it's a bad form, you should always put one
				//	chris:	alfonso? is a method to obtain the last inserted row,
				//			the correct place to call a method that connects to a
				//			databases? surely at this point, it should already be connected?
				return $this->connect()->lastInsertRowId();
				//	chris:	<---- here is a bug, there is no break, therefore this code will
				//			execute the SQLITE code and immediately after the MYSQL code, the
				//			correct form for a switch case statement to avoid stupid bugs like
				//			this is as follows
			case "DEMO_ALFONSO_SWITCH_CASE":{
				//	chris: please do all future switch/case statements in this format
			}break; // <-- this was missing from your above code
			case 'MYSQL': // <-- no { to open the statement
				//	chris:	I didnt research the PDO object, but is this the correct way to
				//			ask the PDO object for it's last inserted row primary key ?
				return mysql_insert_id($this->db);
				//	<-- no break statement, also, no } to close this a few lines up
		}

		//	The original default code
		return $this->lastInsertId;
	}

	/**
	 * 	method:	__construct
	 *
	 * 	This method is called when the database object is created, it connects to the database by default
	 */
	public function __construct($connect=true)
	{
		$this->table	=	array();
		$this->errors	=	array();

		//	TODO: we should implement a try/catch block to easily catch disconnected databases
		if($connect){
			$this->connect();
		}
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
		/* The original MySQL Code, please convert to PDO
		return $handle && is_resource($handle) && stristr($handle, "mysql");
		*/
		return false;
	}

	public function setHandle($handle)
	{
		return $this->isHandle($handle)
			? ($this->selectHandle = $handle)
			: false;
	}

	/**
	 * 	method:	getHandle
	 *
	 * 	todo: write documentation
	 *
	 * 	note:
	 * 		-	Probably you're not setting this, so this will always return an invalid handle
	 */
	public function getHandle()
	{
		return $this->selectHandle;
	}

	public function pushHandle($handle=NULL,$returnHandle=true)
	{
		if($handle === NULL) $handle = $this->getHandle();

		if(!$this->isHandle($handle)) return false;

		$this->selectStack[] = $handle;

		//	Return the handle, or the index where it was added
		return $returnHandle ? $handle : count($this->selectStack)-1;
	}

	public function popHandle($index=NULL)
	{
		if($index !== NULL && !is_numeric($index) && !isset($this->selectStack[$index])){
			return false;
		}

		if($index === NULL){
			return array_pop($this->selectStack);
		}

		$handle = $this->selectStack[$index];
		unset($this->selectStack[$index]);
		return $handle;
	}

	public function restoreHandle($index=NULL)
	{
		return $this->setHandle($this->popHandle($index));
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

		/* The original MySQL Code, please convert to PDO
		return mysql_free_result($handle);
		*/
		return false;
	}

	/**
	 *	method:	setAlias
	 *
	 *	Set the table name for this database object, or if there are two parameters, a key=>value arrangement allowing
	 *	you to abstract table names from the names referenced in the code
	 *
	 *	parameters:
	 * 		$arg1	-	the name of the table, or the name of the key to use for this table
	 * 		$arg2	-	[optional] or the actual name of the table inside the database references by arg1 as the "key"
	 */
	public function setAlias()
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

		$this->errorState = !!$state;

		return $e;
	}

	/**
	 * 	method:	setDebug
	 *
	 * 	todo: write documentation
	 */
	public function setDebugState($state)
	{
		$d = $this->debugState;

		$this->debugState = !!$state;

		return $d;
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
				"__MISSING_QUERY",
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
	 * 	method:	getError
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
		//	chris: alfonso, probably you're not setting this, so calling this method will not return a valid query string
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

		$this->connectionDetails = $s ? $d : false;

		return $this->getConnectionDetails();
	}

	/**
	 * 	method:	getConnectionDetails
	 *
	 * 	todo: write documentation
	 */
	public function getConnectionDetails()
	{
		if($this->connectionDetails){
			$c = $this->connectionDetails;
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

	/**
	 * 	method:	setFetchMethod
	 *
	 * 	todo: write documentation
	 */
	public function setFetchMethod($method)
	{
		/* The original MySQL Code, please convert to PDO
		if(function_exists($method)){
			$this->fetchMethod = $method;
		}else{
			$message = "Your fetch method is not valid, ALL database queries will fail, this is not acceptable";
			$this->debug(__METHOD__,$message);
			die($message);
		}
		*/
		return false;
	}

	/**
	 * 	method:	setEncoding
	 *
	 * 	todo: write documentation
	 */
	public function setEncoding($encoding)
	{
		/* The original MySQL Code, please convert to PDO
		if($this->getConnectionStatus() == false) return;

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
		*/
		return false;
	}

	/**
	 * 	method:	escape
	 *
	 * 	todo: write documentation
	 */
	public function escape($value)
	{
		/* The original MySQL Code, please convert to PDO
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

		if(!$this->getConnectionStatus()){
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
		*/
		return false;
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
	 * 	method:	buildLimit
	 *
	 * 	todo: write documentation
	 */
	public function buildLimit($length=NULL,$offset=NULL)
	{
		/* The original MySQL Code, please convert to PDO
		$length = intval($length);
		$offset = intval($offset);

		$limit = "LIMIT $offset".($length ? ",$length" : "");

		return !$length && !$offset ? "" : $limit;
		*/
		return false;
	}

	/**
	 * 	method: buildSort
	 *
	 * 	A method to build the sorting part of an SQL query from parameters and hide all the complexity inside here
	 * @param unknown_type $va_args
	 */
	public function buildSort($va_args)
	{
		/* The original MySQL Code, please convert to PDO
		$args = func_get_args();
		$sort = array();

		foreach($args as $pair){
			if(!count($pair) == 2) continue;
			if(!strlen($pair[0]) || !is_string($pair[0]) || is_numeric($pair[0])) continue;
			if(!in_array($pair[1],array("asc","desc"))) continue;

			$sort[] = "{$pair[0]} {$pair[1]}";
		}

		return "order by ".implode(",",$sort);
		*/
		return false;
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
		/* The original MySQL Code, please convert to PDO
		$count = $this->selectValue("c","FOUND_ROWS() as c",1,true);
		*/
		$count = false; // obviously I'm doing this to make this code fail before you rewrite it, delete this after you've finished converting it

		$this->popHandle();

		return $count;
	}

	/**
	 * 	method:	connect
	 *
	 * 	Connect to the MYSQL database using various details
	 *
	 * 	todo:
	 * 		-	Need to move the database details to somewhere more
	 * 			secure (like inside the database!! ROFL!! joke, don't do that!!!!)
	 */
	public function connect($details=false)
	{
		Amslib_Debug::log("stack_trace");
		//	What is $DB_TYPE ? it's not specified ANYWHERE.....probably this code will fail really badly...
		//	chris: I just tested this code, omg, fails the first attempt, how did you ever use this code?
		$this->db_type	=	$DB_TYPE;
		//	chris: alfonso, so you ignore the $details parameter I pass in here completely and just use your own?
		$details		=	$this->getConnectionDetails();
		$server			=	$details['server'];
		$username		=	$details['username'];
		$password		=	$details['password'];
		$database		=	$details['database'];
		$dns			=	"mysql:host=$server:$database";
		//	chris: perhaps better to do this no? less useless variables that are only used once and then thrown away
		//$dns = "mysql:host={$details["server"]}:{$details["database"]}";

		try {
			//	chris: and also
			//	$this->connection = new PDO($dns,$details["username"],$details["password"]);
			$this->connection = new PDO( $dns, $username, $password );
		} catch ( Exception $e ) {
			echo "Impossible to connect to the Mysql Database : ", $e->getMessage();
			die();
		}
	}

	/**
	 * 	method:	disconnect
	 *
	 * 	Disconnect from the Database
	 */
	public function disconnect()
	{
		if($this->connection){
			/* The original MySQL Code, please convert to PDO
			 * chris:	alfonso, you need to actually ask the connection to disconnect I suppose
			 *
			 * note:	I'm not sure about manually disconnecting a database object, it might prevent you from
			 * 			connection sharing, or using persistent connections, so perhaps this method gets dropped
			 * 			and deprecated
			mysql_close($this->connection);
			*/
		}

		$this->connection = false;
	}

	/**
	 * 	method:	query
	 *
	 * 	todo: write documentation
	 * 	notes:
	 * 		-	I added the returnBoolean parameter to keep the code compatible with the mysql version
	 */
	public function query($query,$returnBoolean=false){ // <-- always put the { opening a function, on a new line
		//{ <-- here for example, it's better code readability, fuck java.....
		$this->setLastQuery($query);

		$results = $this->connection->query($query);
		$this->debug("QUERY",$query);
		return $results->fetchAll();
	}

	/**
	 * 	method:	select
	 *
	 * 	todo: write documentation
	 */
	public function select($query,$numResults=0,$optimise=false){ // <-- put this { on a new line please
		$query ="select $query";
		$this->setLastQuery($query);

		if($numResults == 0) $numResults = PHP_INT_MAX;
		$results = $this->connection->query($query);
		$this->debug("QUERY",$query);
		//	chris:	oh cool!! fetchAll ? ignoring numResults completely :D

		//	chris:	double points for coolness!!!! when it returns 70,000 results, leading
		//			your PHP script to fail as it runs out of memory :D
		return $results->fetchAll();

		//	chris:	alfonso, this is why in the mysql layer, you have getResults, meaning you
		//			can obtain 100 results in the main query and then subsequent calls to getResults
		//			can "page" through the other 1,000,000 rows instead of obtaining all 1m rows into memory
		//			you can then write an outside loop grabbing X rows then processing them and obtaining
		//			another X rows.  Combined with the pushHandle, popHandle, meaning you can do a query which
		//			returns 1,000,000 rows, return in 250 row chunks, pushHandle, do other SQL operations, such as
		//			processing the rows back into the database, doing other select calls etc, then at the end of the loop
		//			popHandle and getResults(250) to obtain another 250 rows from the original sql query, it's quite
		//			useful, but probably not compatible with transactions (begin, commit, rollback) due to memory
		//			conditions (you might run out of it)
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
		/* The original MySQL Code, please convert to PDO
		$table = $this->escape($table);
		$field = $this->escape($field);

		if(is_string($value))	$value = is_string($value);
		if(is_numeric($value))	$value = intval($value);

		return $this->selectValue($field,"$field from $table where $field='$value'");
		*/
		return false;
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
		/* The original MySQL Code, please convert to PDO
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
		*/
		return false;
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
		$field	=	trim($field);
		$values	=	$this->select($query,$numResults,$optimise);

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
		/* The original MySQL Code, please convert to PDO
		$this->lastInsertId = false;

		if($this->query("insert into $query")){
			return false;
		}

		return $this->lastInsertId = mysql_insert_id($this->connection);
		*/
		return false;
	}

	/**
	 * 	method:	update
	 *
	 * 	todo: write documentation
	 */
	public function update($query,$allow_zero=true)
	{
		/* The original MySQL Code, please convert to PDO
		if(!$this->query("update $query",true)){
			return false;
		}

		$affected = mysql_affected_rows($this->connection);

		return $allow_zero ? $affected >= 0 : $affected > 0;
		*/
		return false;
	}

	/**
	 * 	method:	delete
	 *
	 * 	todo: write documentation
	 */
	public function delete($query)
	{
		/* The original MySQL Code, please convert to PDO
		return $this->query("delete from $query")
			? mysql_affected_rows($this->connection) >= 0
			: false;
		*/
		return false;
	}

	/**
	 * 	method:	getResults
	 *
	 * 	todo: write documentation
	 *
	 * 	note:
	 * 		-	OPTIMISATION: optimise means remove the silly outer layer,
	 * 			this method normally returns an array of results, but when
	 * 			there is one result, it's kind of silly, it's an array
	 * 			containing a single array.  So optimise returns the single
	 * 			result as the returned variable
	 * 			Example: array(array("id_row"=>1,"field_1"=>"hello")) will become array("id_row"=>1,"field_1"=>"hello")
	 * 			So you can see it's removed the outer array
	 */
	public function getResults($count,$handle=NULL,$optimise=false)
	{
		/* The original MySQL Code, please convert to PDO
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

		//	see note on optimisation
		if($optimise && $numResults == 1){
			$this->lastResult = current($this->lastResult);
		}

		//	No results? return false! I'm not sure whether returning false is a good idea, since it has some "meaning"
		//	Perhaps return NULL = no results and return false = failure (failure has meaning, it means something went wrong)
		if(empty($this->lastResult)){
			$this->lastResult = false;
		}

		return $this->lastResult;
		*/
		return false;
	}

	/**
	 * 	method:	begin
	 *
	 * 	todo: write documentation
	 */
	public function begin()
	{
		//	chris:	alfonso, you are assuming the connection is an object, what if
		//			connection failed and somebody attempted to use the object
		//			regardless, ignoring the failure? then this would cause your
		//			code to error and die, right?
		return $this->connection->beginTransaction();
	}

	/**
	 * 	method:	commit
	 *
	 * 	todo: write documentation
	 */
	public function commit()
	{
		//	chris:	alfonso, you are assuming the connection is an object, what if
		//			connection failed and somebody attempted to use the object
		//			regardless, ignoring the failure? then this would cause your
		//			code to error and die, right?
		return $this->connection->commit();
	}

	/**
	 * 	method:	rollback
	 *
	 * 	todo: write documentation
	 */
	public function rollback()
	{
		//	chris:	alfonso, you are assuming the connection is an object, what if
		//			connection failed and somebody attempted to use the object
		//			regardless, ignoring the failure? then this would cause your
		//			code to error and die, right?
		return $this->connection->rollBack();
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
		/* The original MySQL Code, please convert to PDO
		$field = $this->escape($field);
		$table = $this->escape($table);

		if(!$field || !strlen($field) || is_numeric($field)) return -1;
		if(!$table || !strlen($table) || is_numeric($table)) return -2;

		return $this->selectValue("c","count($field) as c from $table",1,true);
		*/
		return false;
	}

	/**
	 * 	method:	fixColumnEncoding
	 *
	 * 	todo: write documentation
	 *
	 * 	note: this method isn't really mysql specific, apart from calling select, escape and update, these could be API specific but they are abstracted anyway
	 */
	public function TOOL_fixColumnEncoding($src,$dst,$table,$primaryKey,$column)
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
}