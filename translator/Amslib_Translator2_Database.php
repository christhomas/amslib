<?php 
class Amslib_Translator2_Database extends Amslib_Translator2_Keystore
{
	protected $location;
	protected $database;
	protected $table;
	
	public function __construct()
	{
		parent::__construct();
	
		$this->database	=	NULL;
		$this->table	=	NULL;
	}
	
	public function setLocation($location)
	{
		$this->location = $location;
	}
		
	public function load()
	{	
		if($this->language)
		{
			$this->database	=	NULL;
			$this->table	=	NULL;
		
			list($database,$table) = explode("/",$this->location);
				
			if($database && $table && class_exists($database) && method_exists($database,"getInstance")){
				$this->database	=	call_user_func(array($database,"getInstance"));
				$this->table	=	$this->database->escape($table);
				
				return true;
			}
			
			die(get_class($this)."::load(), DATABASE '$database' or method 'getInstance' DOES NOT EXIST</br>");
		}

		return false;
	}
	
	public function translate($k,$l=NULL)
	{			
		$v = parent::translate($k,$l);
		
		if($v == $k){
			if(!$l) $l = $this->language;
			
			$k	=	$this->database->escape($k);
			$r	=	$this->database->select("v from {$this->table} where k='$k' and lang='$l'");
			$v	=	"";
			
			if(is_array($r)){
				if(count($r) > 1){
					Amslib_Keystore::add("AMSTUDIOS_TRANSLATOR_ERROR","Multiple conflicting translations for key($k) and language($l)");
				}else{
					if(isset($r[0]["v"])) $v = trim(stripslashes($r[0]["v"]));	
				}
			}
			
			if(strlen($v)) parent::learn($k,$v,$l);
			else $v = $k;
		}
		
		return $v;
	}
	
	public function learn($k,$v,$l=NULL)
	{			
		$k	=	$this->database->escape($k);
		$v	=	$this->database->escape($v);
		
		if(strlen($k) == 0) return false;
		$lc = $l;
		if(!$l) $l = $this->language;
		
		$found = $this->database->select("COUNT(id) from {$this->table} where k='$k' and lang='$l'",1,true);
		Amslib_FirePHP::output("learn","[$lc]: COUNT(id) from {$this->table} where k='$k' and lang='$l'");
		if($found["COUNT(id)"] == 0)
		{
			return $this->database->insert("{$this->table} set k='$k',v='$v',lang='$l'");
		}
		
		return $this->database->update("{$this->table} set v='$v' where k='$k' and lang='$l'");
	}
	
	public function forget($k,$l=NULL)
	{
		if(!$l) $l = $this->language;
		
		$k	=	$this->database->escape($k);
		$f	=	parent::forget($k,$l);
		$d	=	$this->database->delete("{$this->table} where k='$k' and lang='$l'");
		
		return $f && $d;
	}
	
	public function getKeyList($l=NULL)
	{
		if(!$l) $l = $this->language;
		
		return Amslib_Array::valid($this->database->select("k from {$this->table} where lang='$l'"));
	}
	
	public function getValueList($l=NULL)
	{				
		if(!$l) $l = $this->language;
		
		return Amslib_Array::valid($this->database->select("v from {$this->table} where lang='$l'"));		
	}
	
	public function getList($l=NULL)
	{
		if(!$l) $l = $this->language;
		
		return Amslib_Array::valid($this->database->select("k,v from {$this->table} where lang='$l'"));
	}
}