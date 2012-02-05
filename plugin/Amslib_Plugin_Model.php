<?php 
class Amslib_Plugin_Model extends Amslib_Database_MySQL
{
	protected $table;
	
	public function __construct()
	{
		parent::__construct(false);
		
		$this->table = array();
	}
	
	public function setTable()
	{
		$args = func_get_args();
		
		$c = count($args);
		
		if($c == 1){
			$this->table = $this->escape($args[0]);
		}else if($c > 1){
			$this->table[$this->escape($args[0])] = $this->escape($args[1]);
		}
	}
}