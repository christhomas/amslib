<?php 
class Amslib_Translator2_Database extends Amslib_Translator2_Keystore
{
	protected $database;
	protected $table;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->database	=	NULL;
		$this->table	=	NULL;
	}
		
	public function load($location)
	{	
		if($this->language)
		{
			$this->database	=	NULL;
			$this->table	=	NULL;
		
			list($database,$table) = explode("/",$location);
			
			if($database && $table && class_exists($database) && method_exists($database,"getInstance")){
				$this->database	=	call_user_func(array($database,"getInstance"));
				$this->table	=	$this->database->escape($table);
				
				return true;
			}
			
			die(get_class($this)."::load(), DATABASE '$database' or method 'getInstance' DOES NOT EXIST</br>");
		}
		
		return false;
	}
	
	public function translate($k)
	{			
		$v = parent::translate($k);
		
		if($v == $k){
			$result = $this->database->select("v from {$this->table} where k='$k' and lang='{$this->language}'");
			
			$v = "";
			
			if(is_array($result)){
				if(count($result) > 1){
					Amslib_Keystore::add("AMSTUDIOS_TRANSLATOR_ERROR","Multiple conflicting translations for key($k) and language({$this->language})");
				}else{
					if(isset($result[0]["v"])) $v = trim(stripslashes($result[0]["v"]));	
				}
			}
			
			if(strlen($v)) parent::learn($k,$v);
			else $v = $k;
		}
		
		return $v;
	}
	
	public function learn($k,$v)
	{			
		$k	=	$this->database->escape($k);
		$v	=	$this->database->escape($v);
		
		$found = $this->database->select("COUNT(id) from {$this->table} where k='$k' and lang='{$this->language}'",1,true);
		if($found["COUNT(id)"] == 0)
		{
			return $this->database->insert("{$this->table} set k='$k',v='$v',lang='{$this->language}'");
		}
		
		return $this->database->update("{$this->table} set v='$v' where k='$k' and lang='{$this->language}'");
	}
	
	public function forget($k)
	{
		$cache	=	parent::forget($k);
		$db		=	$this->database->delete("{$this->table} where k='$k' and lang='{$this->language}'");
		
		return $cache && $db;
	}
	
	public function getKeyList()
	{
		return $this->database->select("k from {$this->table} where lang='{$this->language}'");
	}
	
	public function getValueList()
	{				
		return $this->database->select("v from {$this->table} where lang='{$this->language}'");		
	}
}