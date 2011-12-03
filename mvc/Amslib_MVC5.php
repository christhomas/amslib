<?php 
class Amslib_MVC5 extends Amslib_MVC4
{
	protected $backend;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function &getInstance()
	{
		static $instance = NULL;
		
		if($instance === NULL) $instance = new self();
		
		return $instance;
	}
	
	public function __call($name,$args)
	{
		if($this->backend && method_exists($this->backend,$name)){
			return call_user_func_array(array($this->backend,$name),$args);
		}
			
		return false;
	}
}