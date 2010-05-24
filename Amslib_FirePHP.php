<?php

/** Wrapper around FirePHP
 */

class Amslib_FirePHP extends FirePHP
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public static function output($name,$data){
		$fp = self::getInstance(true);
		$fp->log($data,$name);	
	}
	
	/**
	* 	Gets singleton instance of FirePHP
	*
	* 	@param boolean $AutoCreate
	*	@return FirePHP
	*/
	public static function getInstance($AutoCreate=false) {
		if($AutoCreate===true && !self::$instance) {
			self::init();
		}
		
		return self::$instance;
	}
	
	/**
	* Creates FirePHP object and stores it for singleton access
	*
	* @return FirePHP
	*/
	public static function init() {
		return self::$instance = new self();
	}
}
