<?php 
class Amslib_Database_Layout
{
	static protected $table	=	array();
	static protected $field	=	array();

	static public function createTable($name,$table=NULL)
	{
		if($table === NULL) $table = $name;
		
		self::$table[$name] = $table;
		self::$field[$name] = array();
	}
	
	static public function addField($name,$key,$field=NULL)
	{
		if($field == NULL) $field = $key;
		
		self::$field[$name][$key] = $field;
	}
	
	static public function getTableName($name)
	{
		return (isset(self::$table[$name])) ? self::$table[$name] : NULL;
	}
	
	static public function getTableFields($name)
	{
		return (isset(self::$field[$name])) ? self::$field[$name] : NULL;
	}
	
	//	Should add option to load from XML database as well
}