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
	
	public function translateExtended($n,$i,$l=NULL)
	{
		$v = parent::translateExtended($n,$i,$l);
		
		if($v == $n){
			if(!$l) $l = $this->language;
			$i	=	intval($i);
			$l	=	$this->database->escape($l);
			$n	=	$this->database->escape($n);
			$r	=	$this->database->select("value from {$this->table} where name='$n' and object_id='$i' and lang='$l'");
			$v	=	"";
			
			if(is_array($r)){
				if(count($r) > 1){
					Amslib_Keystore::add(__METHOD__,"Multiple conflicting translations for key($n),object_id($i) and language($l)");
				}else{
					if(isset($r[0]["value"])) $v = trim(stripslashes($r[0]["value"]));	
				}
				
				parent::learnExtended($n,$i,$v,$l);
			}else{
				$v = $n;
			}
		}

		return stripslashes($v);
	}
	
	public function learnExtended($n,$i,$v,$l=NULL)
	{			
		if(!$l) $l = $this->language;
		$i	=	intval($i);
		$l	=	$this->database->escape($l);
		$n	=	$this->database->escape($n);
		$v	=	$this->database->escape($v);
		
		if(strlen($n) == 0) return false;
		
		$found = $this->database->select("COUNT(id) as c from {$this->table} where name='$n' and lang='$l'",1,true);
		
		return $found && $found["c"]
			? $this->database->update("{$this->table} set value='$v' where name='$n' and object_id='$i' and lang='$l'")
			: $this->database->insert("{$this->table} set value='$v',name='$n',object_id='$id',lang='$l'");
	}
	
	public function forgetExtended($n,$i,$l=NULL)
	{
		if(!$l) $l = $this->language;
		
		$f	=	parent::forgetExtended($n,$i,$l);
		$i	=	intval($i);
		$l	=	$this->database->escape($l);
		$n	=	$this->database->escape($n);
		$d	=	$this->database->delete("{$this->table} where name='$n' and object_id='$i' and lang='$l'");
		
		return $f && $d;
	}
	
	public function searchKeyExtended($k,$i,$s=false,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$i = intval($i);
		$l = $this->database->escape($l);
		$n = $this->database->escape($n);
		
		$filter = "where lang='$l' and object_id='$i' and ".($s ? "name like '%$n%'" : "name='$n'");
		
		$query = "name,object_id,value from {$this->table} $filter";

		return Amslib_Array::stripSlashesMulti($this->database->select($query));
	}
	
	public function searchValueExtended($v,$i,$s=false,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$i = intval($i);
		$l = $this->database->escape($l);
		$v = $this->database->escape($v);
		
		$filter = "where lang='$l' and object_id='$i' and ".($s ? "value like '%$v%'" : "value='$v'");
		
		$query = "name,object_id,value from {$this->table} $filter";

		return Amslib_Array::stripSlashesMulti($this->database->select($query));
	}
	
	public function getKeyListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$i = intval($i);
		$l = $this->database->escape($l);
		
		$query = "name from {$this->table} where lang='$l' and object_id='$i'";
		
		return Amslib_Array::valid($this->database->select($query));
	}
	
	public function getValueListExtended($i,$l=NULL)
	{				
		if(!$l) $l = $this->language;
		$i = intval($i);
		$l = $this->database->escape($l);
		
		$query = "value from {$this->table} where lang='$l' and object_id='$i'";
		
		return Amslib_Array::valid($this->database->select($query));		
	}
	
	public function getListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;
		
		$i = intval($i);
		$l = $this->database->escape($l);
		
		$query = "name,object_id,value from {$this->table} where lang='$l' and object_id='$i'";
		
		return Amslib_Array::valid($this->database->select($query));
	}
}