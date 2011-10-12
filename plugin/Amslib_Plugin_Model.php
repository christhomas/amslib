<?php 
class Amslib_Plugin_Model extends Amslib_Database_MySQL2
{
	protected $table;
	
	public function __construct()
	{
		parent::__construct(false);
		
		$this->table = array();
	}
	
	public function setTable($name,$value,$singular=false)
	{
		if($singular){
			$this->table = $this->escape($value);
		}else{
			$this->table[$name] = $this->escape($value);	
		}
	}
}