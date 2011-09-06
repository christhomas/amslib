<?php 
class Amslib_Plugin_Model extends Amslib_Database_MySQL
{
	protected $table;
	protected $prefix;
	
	public function __construct()
	{
		parent::__construct(false);
		
		$this->table = array();
	}
	
	public function setTable($name,$value)
	{
		$this->table[$name] = $this->escape($value);
	}
}