<?php
/**
 * This still hasn't been thought of properly yet and still needs a lot of work
 */ 
class Admin_MVC_Model extends Amslib_Database_MySQL2
{
	static protected $connection	=	NULL;
	static protected $model			=	NULL;
	
	public function __construct($autoconnect)
	{
		parent::__construct($autoconnect);
		
		if(self::$connection) $this->copy(self::$connection);
	}
	
	static public function setConnection($database)
	{
		self::$connection = $database;
	}
	
	static public function getConnection()
	{
		return self::$connection;
	}
	
	static public function setModel($name,$model)
	{
		if(is_string($name) && $model){
			self::$model[$name] = $model;
		}
	}
	
	static public function getModel($name,$location=NULL)
	{
		return (is_string($name) && isset(self::$model[$name])) ? self::$model : false;
	}
}