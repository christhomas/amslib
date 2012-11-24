<?php 
class Amslib_Translator_Database extends Amslib_Translator_Keystore
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
			$l	=	$this->database->escape($l);
			$k	=	$this->database->escape($k);
			$r	=	$this->database->select("v from {$this->table} where k='$k' and lang='$l'");
			$v	=	"";
			
			if(is_array($r)){
				if(count($r) > 1){
					Amslib_Keystore::add("AMSTUDIOS_TRANSLATOR_ERROR","Multiple conflicting translations for key($k) and language($l)");
				}else{
					if(isset($r[0]["v"])) $v = trim(stripslashes($r[0]["v"]));	
				}
				
				parent::learn($k,$v,$l);
			}else{
				$v = $k;
			}
		}
		
		return stripslashes($v);
	}
	
	public function learn($k,$v,$l=NULL)
	{			
		if(!$l) $l = $this->language;
		$l	=	$this->database->escape($l);
		$k	=	$this->database->escape($k);
		$v	=	$this->database->escape($v);
		
		if(strlen($k) == 0) return false;
		
		$found = $this->database->select("COUNT(id) as c from {$this->table} where k='$k' and lang='$l'",1,true);
		
		return $found && $found["c"]
			? $this->database->update("{$this->table} set v='$v' where k='$k' and lang='$l'")
			: $this->database->insert("{$this->table} set v='$v', k='$k',lang='$l'");
	}
	
	public function forget($k,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$l = $this->database->escape($l);
		
		$k	=	$this->database->escape($k);
		$f	=	parent::forget($k,$l);
		$d	=	$this->database->delete("{$this->table} where k='$k' and lang='$l'");
		
		return $f && $d;
	}
	
	public function searchKey($k,$s=false,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$l = $this->database->escape($l);
		$k = $this->database->escape($k);
		
		$filter = "where lang='$l' AND ".($s ? "k LIKE '%$k%'" : "k='$k'");

		$list = Amslib_Array::valid($this->database->select("k,v from {$this->table} $filter"));
		
		return Amslib_Array::stripSlashesMulti($list);
	}
	
	public function searchValue($v,$s=false,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$l = $this->database->escape($l);
		$v = $this->database->escape($v);
		
		$filter = "where lang='$l' AND ".($s ? "v LIKE '%$v%'" : "v='$v'");

		$list = Amslib_Array::valid($this->database->select("k,v from {$this->table} $filter"));
		
		return Amslib_Array::stripSlashesMulti($list);
	}
	
	public function getKeyList($l=NULL)
	{
		if(!$l) $l = $this->language;
		$l = $this->database->escape($l);
		
		return Amslib_Array::valid($this->database->select("k from {$this->table} where lang='$l'"));
	}
	
	public function getValueList($l=NULL)
	{				
		if(!$l) $l = $this->language;
		$l = $this->database->escape($l);
		
		return Amslib_Array::valid($this->database->select("v from {$this->table} where lang='$l'"));		
	}
	
	public function getList($l=NULL)
	{
		if(!$l) $l = $this->language;
		$l = $this->database->escape($l);
		
		return Amslib_Array::valid($this->database->select("k,v from {$this->table} where lang='$l'"));
	}
}