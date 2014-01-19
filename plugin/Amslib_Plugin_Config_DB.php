<?php
class Amslib_Plugin_Config_DB
{
	protected $database;

	protected $prefix;

	/**
	 * 	method:	__construct
	 *
	 * 	parameters:
	 * 		$database	-	A variety of formats that are recognised and converted in particular ways, see notes
	 * 		$prefix		-	The prefix of all the database tables
	 *
	 * 	notes:
	 * 		-	If the $prefix value is not valid, it'll default to using "amslib_plugin" for the prefix
	 * 		-	The $database parameter can either be one of the following formats and it'll be intelligently be converted
	 * 			if NULL: the database class name "Database" will be used and then will combine with the immediate next rule
	 * 			if is_string && is_class: will call static method getInstance on that class name to obtain object
	 * 			if is_string && is_function: will execute call_user_func to obtain the database object
	 *			if is_array && count > 2 && is_class(0) and method_exists(1): will execute call_user_func to obtain object
	 *			if is_object: will just use object, I suppose you passed a correct database object?
	 */
	public function __construct($database=NULL,$prefix=NULL)
	{
		if(!$prefix) $prefix = "amslib_plugin";
		$this->setValue("prefix",$prefix);

		if(!$database) $database = "Database";

		$this->database = false;

		$this->setValue("database",$database);

		//$c = new Amslib_Plugin_Config_DB(Database::getInstance());
		//$c = new Amslib_Plugin_Config_DB(Database::getInstance(),"amslib_plugin");
		//$c = new Amslib_Plugin_Config_DB("Database");
		//$c = new Amslib_Plugin_Config_DB(array("Database","getInstance"));
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	public function getStatus()
	{
		//	TODO: In order to know it's truely valid, the prefix should be tested first?

		return is_object($this->database) && is_string($this->prefix) && strlen($this->prefix) ? true : false;
	}

	public function setValue($key,$value)
	{
		switch($key){
			case "prefix":{
				$this->prefix = trim($prefix," _")."_";
			}break;

			case "database":{
				if(is_string($value)){
					if(is_class($value)){
						$this->database = call_user_func(array($value,"getInstance"));
					}else if(function_exists($value)){
						$this->database = call_user_func($value);
					}
				}else if(is_array($value) && count($value) > 2 && is_class($value[0]) && method_exists($value[0],$value[1])){
					$this->database = call_user_func(array($value[0],$value[1]));
				}else if(is_object($value)){
					$this->database = $value;
				}
			}break;

			default:{
				$value = false;
			}break;
		}

		return $value;
	}
}