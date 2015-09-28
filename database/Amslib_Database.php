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
class Amslib_Database
{
    protected $debugState	=	false;
    protected $error		=	array();

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
    protected $selectResult			=	array();

    protected $lastQuery			=	false;
    protected $lastInsertId			=	0;

    protected $validEncoding		=	array("utf8","latin1");

    /**
     * 	array: $statementCache
     *
     * 	An array of PDOStatements which have been cached by previous queries
     */
    protected $statementCache;

    /**
     * 	method: debug
     *
     * 	todo: write documentation
     */
    protected function debug($type,$data,$params=array())
    {
        //	Type "ERROR" always record into the database, but DEBUG/QUERY, require debugState=true
        if(!$this->debugState && in_array($type,array("DEBUG","QUERY"))){
            return null;
        }

        if(!empty($params)){
            static $param_map = array(
                0 => "PARAM_NULL",
                1 => "PARAM_INT",
                2 => "PARAM_STR",
                3 => "PARAM_LOB",
                4 => "PARAM_STMT",
                5 => "PARAM_BOOL"
            );

            $params = Amslib_Array::valid($params);
            $params = Amslib_Array::reindexByKey($params,"0");

            foreach($params as &$p){
                $p = Amslib_Array::pluck($p,array("1","2"));
                $p[2] = $param_map[$p[2]];
            }
        }

        return empty($params)
            ? Amslib_Debug::log($type,$data)
            : Amslib_Debug::log($type,$data,$params);
    }

    /**
     * 	method:	getLastResult
     *
     * 	todo: write documentation
     */
    protected function getLastResult()
    {
        return $this->selectResult;
    }

    protected function setErrorStackDepth($depth=NULL)
    {
        static $defaultDepth = 5;

        $depth = intval($depth);

        $this->errorStackDepth = $depth ? $depth : $defaultDepth;
    }

    /**
     * 	method: getStatement
     *
     * 	Retrieve the PDOStatement based on the query, from the query cache or created
     * 	anew and inserted into the cache for next time
     */
    protected function getStatement($query,$params=array())
    {
        $this->isConnected();

        $this->setLastQuery($query);

        $this->debug("QUERY",$query,$params);

        $key = sha1($query);

        $statement = isset($this->statementCache[$key])
            ? $this->statementCache[$key]
            : $this->connection->prepare($query);

        if(!$statement instanceof PDOStatement){
            throw new PDOException("prepare() did not return a PDOStatement");
        }

        if(empty($params)) return $statement;

        $this->statementCache[$key] = $statement;

        foreach(Amslib_Array::valid($params) as $p){
            call_user_func_array(array($this->statementCache[$key],"bindValue"),$p);
        }

        return $this->statementCache[$key];
    }

    /**
     * 	method:	__construct
     *
     * 	This method is called when the database object is created, it connects to the database by default
     */
    public function __construct($connect=true)
    {
        $this->table	=	array();
        $this->error	=	false;

        //	Set the default error stack depth for code location reporting
        $this->setErrorStackDepth();

        //	This test is to detect whether you are accidentally going
        //	to try to auto-connect a database object which has no database
        //	settings, causing the database system to kill the code because
        //	it will not continue without database credentials
        $is_derived_class = strpos(get_class($this),__CLASS__) === false;

        //	TODO: we should implement a try/catch block to easily catch disconnected databases
        if($connect && $is_derived_class){
            $this->connect();
        }
    }

    /**
     *  method:	getInstance
     *
     */
    static public function getInstance()
    {
        static $instance = NULL;

        if ($instance === NULL) $instance = new self();

        return $instance;
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
        $args = array_map(array($this,"escape"),$args);

        $c = count($args);

        if($c == 1){
            $this->table = $args[0];
        }else if($c > 1){
            $this->table[$args[0]] = $args[1];
        }
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
        //	Overload some default values just in case something failed
        $args = func_get_args() + array(
                "__MISSING_QUERY__",
                $this->connection->errorInfo(),
                $this->connection->errorCode(),
                //	TODO: eventually I'll figure out what to put here as a default parameter
                -1
            );

        $this->error = array(
            "db_failure"		=>	true,
            "db_query"			=>	preg_replace('/\s+/',' ',$args[0]),
            "db_error"			=>	$args[1],
            "db_error_num"		=>	$args[2],
            "db_last_insert"	=>	$this->lastInsertId,
            "db_insert_id"		=>	$args[3],
            "db_location"		=>	Amslib_Debug::getCodeLocation($this->errorStackDepth)
        );

        $this->debug("ERROR",$this->error);

        $this->setErrorStackDepth();
    }

    /**
     * 	method:	getError
     *
     * 	Obtain the error set on the database object due to queries run against it
     *
     * 	parameters:
     * 		$clear	-	Whether or not to clear the errors after obtaining them
     *
     * 	returns
     * 		An array of error data, or boolean false if there are no errors set
     */
    public function getError($clear=true)
    {
        $error = $this->error;

        if($clear) $this->error = false;

        return $error;
    }

    /**
     * 	method:	setLastQuery
     *
     * 	todo: write documentation
     */
    protected function setLastQuery($query)
    {
        $this->lastQuery = $query;
    }

    /**
     * 	method:	getLastQuery
     *
     * 	todo: write documentation
     */
    public function getLastQuery()
    {
        return $this->lastQuery;
    }

    /**
     * 	method:	isConnected
     *
     * 	Return the status of the database connection
     *
     * 	returns:
     * 		-	Boolean true or throws an exception about the database not being connected
     */
    public function isConnected($return=false)
    {
        if(!$this->connection instanceof PDO){
            if(!$return) throw new PDOException(__CLASS__." Exception: Database not connected");

            return false;
        }

        return true;
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
        if($object instanceof PDO){
            $this->setConnection($object);
        }else if($object instanceof self){
            $this->setConnection($object->getConnection());
        }

        return $this->isConnected();
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
            $v->add("encoding","text",true,array("default"=>"utf8","limit-input"=>$this->validEncoding));

            $s = $v->execute();
            $d = $v->getValid();
            $e = $v->getErrors();

            if(!$s){
                if(isset($e["password"])){
                    $e["password"]["value"] = "*CENSORED*";
                }
                Amslib_Debug::log("database details were invalid",$e);
            }
        }

        $this->connectionDetails = $s ? $d : false;

        return $this->getConnectionDetails();
    }

    /**
     * 	method:	getConnectionDetails
     *
     * 	todo: write documentation
     *
     * 	note:
     * 		-	we store the password like this because it hides it from var_dump and friends but
     * 			still allows the password to be returned within the duration of the script
     * 		-	I'm not 100% sure it stops people from grabbing the password, but 90%+ :D
     */
    public function getConnectionDetails()
    {
        static $password = NULL;

        if($this->connectionDetails){
            if(isset($this->connectionDetails["password"])){
                $password = $this->connectionDetails["password"];
                unset($this->connectionDetails["password"]);
            }

            return array($this->connectionDetails,$password);
        }

        die(__METHOD__.", FATAL ERROR: there were no connection details".Amslib_Debug::vdump($this->connectionDetails));
    }

    /**
     * 	method:	setEncoding
     *
     * 	todo: write documentation
     */
    public function setEncoding($encoding)
    {
        if($this->isConnected() == false) return false;

        if(!in_array($encoding,$this->validEncoding)){
            $message =
                "(".basename(__FILE__)." / FATAL ERROR): Your encoding ($encoding) is wrong, ".
                "this can cause database corruption.".
                "I'm sorry dave, but I can't allow you to do that<br/>".
                "allowed encodings = <pre>".implode(",",$this->validEncoding)."</pre>";

            $this->debug("ERROR",$message);
            die($message);
        }

        $this->connection->exec("set names $encoding");
        $this->connection->exec("set character set $encoding");

        return true;
    }

    public function isHandle($handle)
    {
        return $handle instanceof PDOStatement;
    }

    public function setHandle($handle)
    {
        return $this->isHandle($handle)
            ? ($this->selectHandle = $handle)
            : false;
    }

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
            throw new Exception("Freeing an invalid select handle");
        }

        return $handle->closeCursor();
    }

    /**
     * 	method:	escape
     *
     * 	todo: write documentation
     */
    public function escape($value)
    {
        return $value;
    }

    /**
     * 	method:	buildLimit
     *
     * 	todo: write documentation
     */
    public function buildLimit($length=NULL,$offset=NULL)
    {
        $length = intval($length);
        $offset = intval($offset);

        $limit = "LIMIT $offset".($length ? ",$length" : "");

        return !$length && !$offset ? "" : $limit;
    }

    /**
     * 	method: buildSort
     *
     * 	A method to build the sorting part of an SQL query from parameters and hide all the complexity inside here
     * @param unknown_type $va_args
     */
    public function buildSort($va_args)
    {
        $args = func_get_args();
        $sort = array();

        foreach($args as $pair){
            if(!count($pair) == 2) continue;
            if(!strlen($pair[0]) || !is_string($pair[0]) || is_numeric($pair[0])) continue;
            if(!in_array($pair[1],array("asc","desc"))) continue;

            $sort[] = "{$pair[0]} {$pair[1]}";
        }

        return "order by ".implode(",",$sort);
    }

    /**
     * 	method: buildFields
     *
     * 	A method to take an array of key/values and construct a comma separated list of
     * 	paramaters ready to send to insert/update sql queries
     *
     * 	params:
     * 		$array - The array of key/value pairs
     * 		$separator - The separator to use between each pair
     *
     * 	returns:
     * 		an implode()'d string of key=values, separate by comma
     *
     * 	notes:
     * 		-	non-numeric keys are skipped
     * 		-	non-string keys are skipped
     * 		-	empty keys are skipped
     * 		-	non-numeric values are skipped
     * 		-	non-string values are skipped
     */
    public function buildFields($array,$separator=",")
    {
        if(!is_array($array)) $array = Amslib_Array::valid($array);

        $fields = array();

        foreach($array as $key=>$value){
            //	skip keys if numeric, non-string or empty string
            if(is_numeric($key) || !is_string($key) || !strlen($key)) continue;
            //	skip values if non-numeric or string
            if(!is_numeric($value) && !is_string($value)) continue;
            //	if string, quote it
            if(is_string($value)) $value=$this->connection->quote($value);

            $fields[] = "$key=$value";
        }

        return count($fields) ? implode($separator,$fields) : false;
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
        list($details,$password) = Amslib_Array::valid($this->setConnectionDetails($details)) + array(NULL,NULL);

        $this->disconnect();

        $valid = Amslib_Array::hasKeys($details,array("server","username","database","encoding")) && strlen($password);

        if($valid){
            try {
                $dsn = "mysql:dbname={$details["database"]};host={$details["server"]};charset={$details["encoding"]}";

                $options = array(
                    PDO::ATTR_ERRMODE				=> PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC,
                    PDO::ATTR_STATEMENT_CLASS		=> array('Amslib_Database_Statement', array($this))
                );

                $this->connection = new PDO($dsn,$details["username"],$password,$options);

                $this->setEncoding($details["encoding"]);

                return $this->isConnected(true);
            } catch (PDOException $e){
                $this->debug("DATABASE",array(
                    "exception"	=> $e->getMessage(),
                    "data"		=> $details
                ));

                throw $e;
            }
        }else{
            $this->debug("DATABASE","connection details were not valid",$details);
        }

        $this->disconnect();

        return false;
    }

    /**
     * 	method:	disconnect
     *
     * 	Disconnect from the Database
     */
    public function disconnect()
    {
        $this->connection = NULL;
    }

    /**
     * 	method:	begin
     *
     * 	todo: write documentation
     */
    public function begin()
    {
        $this->isConnected();

        return $this->connection->beginTransaction();
    }

    /**
     * 	method:	commit
     *
     * 	todo: write documentation
     */
    public function commit()
    {
        $this->isConnected();

        return $this->connection->commit();
    }

    /**
     * 	method:	rollback
     *
     * 	todo: write documentation
     */
    public function rollback()
    {
        $this->isConnected();

        return $this->connection->rollBack();
    }

    /**
     * 	method:	query
     *
     * 	todo: write documentation
     * 	notes:
     * 		-	I added the returnBoolean parameter to keep the code compatible with the mysql version
     */
    public function query($query,$params=array(),$returnBoolean=false)
    {
        $statement = $this->getStatement($query,$params);

        return $statement->execute();
    }

    /**
     * 	method: createTempTable
     *
     * 	Create a temporary table from a select query
     *
     * 	params:
     * 		$table	- The table name to use
     * 		$query	- The SQL select the create the table from
     * 		$params	- The SQL parameters to apply to the query
     *
     * 	returns:
     * 		-	Boolean false if drop table was not allowed
     * 		-	Boolean false if create table was not allowed
     * 		-	Boolean true if everything executed ok
     *
     * 	notes:
     * 		-	We should throw exceptions perhaps to allow better error detection cause false can mean many things
     */
    public function createTempTable($table,$query,$params=array())
    {
        if(!$this->query("drop temporary table if exists $table")){
            //	Could not drop temporary table (throw exception?)
            return false;
        }

        if(!$this->query("create temporary table $table as select $query",$params)){
            //	Could not create temporary table (throw exception?)
            return false;
        }

        return true;
    }

    /**
     * 	method:	select
     *
     * 	todo: write documentation
     */
    public function select($query,$params=array(),$numResults=0,$optimise=false)
    {
        $statement = $this->getStatement("select $query",$params);

        //	record error information?
        if(!$statement->execute()) return false;

        $statement = $this->setHandle($statement);

        //	If you don't request a number of results, use the maximum number we could possible accept
        //	NOTE: you'll run out of memory a long time before you reach this count
        if($numResults == 0) $numResults = PHP_INT_MAX;

        return $this->getResults($numResults,$statement,$optimise);
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
        $result = $this->select($query,array(),$numResults,$optimise);

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
        return $this->selectValue($field,":field from :table where :field=:value",array(
            array(":field",$field,PDO_TYPE_STR),
            array(":table",$table,PDO_TYPE_STR),
            array(":value",$value)
        ));
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
    public function selectValue($field,$query,$params=array(),$numResults=0,$optimise=false)
    {
        $field	=	trim($field);
        $values	=	$this->select($query,$params,$numResults,$optimise);

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
    public function selectRow($query,$params=array())
    {
        return $this->select($query,$params,1,true);
    }

    /**
     * 	method:	insert
     *
     * 	todo: write documentation
     */
    public function insert($query,$params=array())
    {
        $statement = $this->getStatement("insert into $query",$params);

        if(!$statement->execute()){
            return false;
        }

        $this->setHandle($statement);

        return $this->lastInsertId = $this->getInsertId();
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
    public function update($query,$params=array(),$allow_zero=true)
    {
        $statement = $this->getStatement("update $query",$params);

        if(!$statement->execute()){
            return false;
        }

        $statement = $this->setHandle($statement);

        return $this->getCount(true,$allow_zero);
    }

    /**
     * 	method:	delete
     *
     * 	todo: write documentation
     */
    public function delete($query,$params=array())
    {
        $statement = $this->getStatement("delete from $query",$params);

        if(!$statement->execute()){
            return false;
        }

        $statement = $this->setHandle($statement);

        return $this->getCount() >= 0;
    }

    /**
     * 	method:	getLastInsertId
     *
     * 	todo: write documentation
     */
    public function getInsertId()
    {
        $this->isConnected();

        return $this->connection->lastInsertId();
    }

    /**
     * 	method:	getCount
     *
     * 	todo: write documentation
     */
    public function getCount($boolean=true,$allow_zero=true)
    {
        $handle = $this->getHandle();

        $count = $handle->rowCount();

        return $boolean ? ($allow_zero ? $count >= 0 : $count > 0) : $count;
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
    public function getRealCount()
    {
        $this->pushHandle();

        $count = $this->connection->query("SELECT FOUND_ROWS()")->fetchColumn();

        $this->popHandle();

        return $count;
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
        $this->selectResult = array();

        //	Make sure the result handle is valid
        if(!$this->isHandle($handle)) $handle = $this->getHandle();
        if(!$this->isHandle($handle)) return false;

        for($a=0;$a<$count;$a++){
            $row = $handle->fetch();
            //	We have no results left to obtain
            if(!$row) break;
            //	Otherwise record the result
            $this->selectResult[] = $row;
        }

        //	see note on optimisation
        if($optimise && $count == 1){
            $this->selectResult = current($this->selectResult);
        }

        return $this->selectResult;
    }
}