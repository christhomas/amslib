<?php
/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
* Contributors/Author:
*    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
*
*******************************************************************************/

/**
 * 	class:	Amslib_Translator_Database
 *
 *	group:	translator
 *
 *	file:	Amslib_Translator_Database.php
 *
 *	description:
 *		write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Translator_Database extends Amslib_Translator_Keystore
{
	protected $database;
	protected $table;
	protected $lang;

	protected function getIdLang($lang)
	{
		if(!$lang) $lang = $this->language;

		$lang = $this->database->escape($lang);

		return $this->database->selectValue("id_lang","id_lang from $this->lang where code='$lang'",1,true);
	}

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		parent::__construct();

		$this->database	=	NULL;
		$this->table	=	NULL;
		$this->lang		=	NULL;
	}

	/**
	 * 	method:	load
	 *
	 * 	todo: write documentation
	 */
	public function load()
	{
		if(!$this->language) return false;

		$this->database	=	NULL;
		$this->table	=	NULL;
		$this->lang		=	NULL;

		$database	=	$this->getConfig("database","missing");
		$table		=	$this->getConfig("translation");
		$lang		=	$this->getConfig("lang");

		if($database && $table && class_exists($database) && method_exists($database,"getInstance")){
			$this->database	=	call_user_func(array($database,"getInstance"));
			$this->table	=	$this->database->escape($table);
			$this->lang		=	$this->database->escape($lang);

			return true;
		}

		die(get_class($this)."::load(), DATABASE '$database' or method 'getInstance' DOES NOT EXIST</br>");
	}

	/**
	 * 	method:	translateExtended
	 *
	 * 	todo: write documentation
	 */
	public function translateExtended($n,$i,$l=NULL)
	{
		$v = parent::translateExtended($n,$i,$l);

		if($v == $n && is_numeric($i)){
			$i	=	intval($i);
			$l	=	$this->getIdLang($l);
			$n	=	$this->database->escape($n);
			$r	=	$this->database->select("value from {$this->table} where name='$n' and id_object=$i and id_lang='$l'");
			$v	=	"";

			if(is_array($r)){
				if(count($r) > 1){
					Amslib_Keystore::add(__METHOD__,"Multiple conflicting translations for key($n),id_object($i) and language($l)");
				}else{
					if(isset($r[0]["value"])) $v = trim($r[0]["value"]);
				}

				parent::learnExtended($n,$i,$v,$l);
			}else{
				$v = $n;
			}
		}else{
			die("FAIL 1: ".Amslib_Debug::var_dump(array($n,$i,$l,$v),true));
		}

		return $v;
	}

	/**
	 * 	method:	learnExtended
	 *
	 * 	todo: write documentation
	 */
	public function learnExtended($n,$i,$v,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$i	=	intval($i);
		$l	=	$this->getIdLang($l);
		$n	=	$this->database->escape($n);
		$v	=	$this->database->escape($v);

		if(strlen($n) == 0) return false;

		$found = $this->database->select("COUNT(id) as c from {$this->table} where name='$n' and id_lang='$l'",1,true);

		return $found && $found["c"]
			? $this->database->update("{$this->table} set value='$v' where name='$n' and id_object='$i' and id_lang='$l'")
			: $this->database->insert("{$this->table} set value='$v',name='$n',id_object='$id',id_lang='$l'");
	}

	/**
	 * 	method:	forgetExtended
	 *
	 * 	todo: write documentation
	 */
	public function forgetExtended($n,$i,$l=NULL)
	{
		if(!$l) $l = $this->language;

		$f	=	parent::forgetExtended($n,$i,$l);
		$i	=	intval($i);
		$l	=	$this->getIdLang($l);
		$n	=	$this->database->escape($n);
		$d	=	$this->database->delete("{$this->table} where name='$n' and id_object=$i and id_lang='$l'");

		return $f && $d;
	}

	/**
	 * 	method:	searchKeyExtended
	 *
	 * 	todo: write documentation
	 */
	public function searchKeyExtended($k,$i,$s=false,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$i = intval($i);
		$l = $this->getIdLang($l);
		$n = $this->database->escape($n);

		$filter = "where id_lang='$l' and id_object=$i and ".($s ? "name like '%$n%'" : "name='$n'");

		$query = "name,id_object,value from {$this->table} $filter";

		return Amslib_Array::stripSlashesMulti($this->database->select($query));
	}

	/**
	 * 	method:	searchValueExtended
	 *
	 * 	todo: write documentation
	 */
	public function searchValueExtended($v,$i,$s=false,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$i = intval($i);
		$l = $this->getIdLang($l);
		$v = $this->database->escape($v);

		$filter = "where id_lang='$l' and id_object=$i and ".($s ? "value like '%$v%'" : "value='$v'");

		$query = "name,id_object,value from {$this->table} $filter";

		return Amslib_Array::stripSlashesMulti($this->database->select($query));
	}

	/**
	 * 	method:	getKeyListExtended
	 *
	 * 	todo: write documentation
	 */
	public function getKeyListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$i = intval($i);
		$l = $this->getIdLang($l);

		$query = "name from {$this->table} where id_lang='$l' and id_object=$i";

		return Amslib_Array::valid($this->database->select($query));
	}

	/**
	 * 	method:	getValueListExtended
	 *
	 * 	todo: write documentation
	 */
	public function getValueListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;
		$i = intval($i);
		$l = $this->getIdLang($l);

		$query = "value from {$this->table} where id_lang='$l' and id_object=$i";

		return Amslib_Array::valid($this->database->select($query));
	}

	/**
	 * 	method:	getListExtended
	 *
	 * 	todo: write documentation
	 */
	public function getListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;

		$i = intval($i);
		$l = $this->getIdLang($l);

		$query = "name,id_object,value from {$this->table} where id_lang='$l' and id_object=$i";

		return Amslib_Array::valid($this->database->select($query));
	}
}