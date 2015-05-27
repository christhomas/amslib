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

class Amslib_Database_DEPRECATED
{
	protected $schema;

	public static function setSharedConnection($object)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return self::sharedConnection($object);
	}

	public static function getSharedConnection()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return self::sharedConnection();
	}

	public function setDBErrors($data)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		$args = func_get_args();

		call_user_func_array(array($this,"setError"),$args);
	}

	public function getDBErrors($clear=true)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->getError($clear);
	}

	public function getConnectionStatus()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->isConnected();
	}

	public function setDebug($state)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		$this->setDebugState($state);
	}

	protected function getLastTransactionId()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->getLastInsertId();
	}

	public function getDBList()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getDatabases();
	}

	public function getDBTables($database_name=NULL)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getTables($database_name);
	}

	public function getDBColumns($database_name=NULL,$table_name=NULL)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getTables($database_name,$table_name);
	}

	public function getDBTableFields($table)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getFields($table);
	}

	public function getDBTableRowCount($database,$table)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getRowCount($database,$table);
	}

	public function hasTable($database,$table)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		$this->schema = new Amslib_Database_Schema($this);

		$this->schema->hasTable($database,$table);
	}

	public function getSQLLimit($length=NULL,$offset=NULL)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__,$length,$offset);
		return $this->buildLimit($length,$offset);
	}

	//	this method was a horribly, ugly mistake
	public function select2($query,$numResults=0,$optimise=false)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__,$query,$numResults,$optimise);
		return $this->select($query,$numResults,$optimise);
	}

	public function getSearchResultHandle()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->getHandle();
	}

	public function storeSearchHandle()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->pushHandle();
	}

	public function restoreSearchHandle($handle=NULL)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		if(!$this->isHandle($handle)) $handle = $this->popHandle();

		return $this->setHandle($handle);
	}

	public function beginTransaction()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->begin();
	}

	public function commitTransaction()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->commit();
	}

	public function rollbackTransaction()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->rollback();
	}

	/**
	 * 	method:	releaseMemory
	 *
	 * 	todo: write documentation
	 *
	 * 	If we supply a result handle, free that, or obtain the handle created when you last selected something
	 */
	public function releaseMemory($handle=NULL)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->freeHandle($handle);
	}

	//	a quick fix for the PDO object which doesn't support this method yet
	public function freeHandle($handle)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return false;
	}

	function lastInsertRowId()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		return $this->getLastInsertId();
	}

	public function setTable()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		$args = func_get_args();

		return call_user_func_array(array($this,"setAlias"),$args);
	}
	

	public static function sharedConnection($object=NULL)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		
		static $shared = NULL;
	
		if($object && is_object($object) && method_exists($object,"getConnection")){
			$shared = $object;
		}
	
		return $shared;
	}
	
	/**
	 * 	method:	unescape
	 *
	 * 	todo: write documentation
	 *
	 * 	note: this is generic functionality, it's not mysql specific
	 */
	public function unescape($results,$keys="")
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		
		if(!$results || !is_array($results)) return $results;
	
		if(Amslib_Array::isMulti($results)){
			if($keys == "") $keys = array_keys(current($results));
	
			foreach($results as &$r){
				$r = Amslib_Array::stripSlashesSingle($r,$keys);
			}
		}else{
			if($keys == "") $keys = array_keys($results);
	
			$results = Amslib_Array::stripSlashesSingle($results,$keys);
		}
	
		return $results;
	}
	
	public function getLastAffectedCount($boolean=true,$allow_zero=true)
	{
		Amslib_Debug::log("DEPRECATED METHOD");
	
		return $this->getCount($boolean,$allow_zero);
	}
	
	public function getCount($boolean=true,$allow_zero=true)
	{
		Amslib_Debug::log("DEBUG METHOD OVERRIDE FOR DEPRECATED METHOD");
		
		return 0;
	}
	
	/**
	 * 	method:	getLastInsertId
	 *
	 * 	todo: write documentation
	 */
	public function getLastInsertId()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		
		return $this->getInsertId();
	}
	
	public function getInsertId()
	{
		Amslib_Debug::log("DEBUG METHOD OVERRIDE FOR DEPRECATED METHOD");
		
		return 0;
	}
	
	public function getRealResultCount()
	{
		Amslib_Debug::log("DEPRECATED METHOD");
		
		return $this->getRealCount();
	}
	
	public function getRealCount()
	{
		Amslib_Debug::log("DEBUG METHOD OVERRIDE FOR DEPRECATED METHOD");
		
		return 0;
	}
	
	/**
	 * 	method: countRows
	 *
	 * 	A quick method to count the number of rows by selecting a single field, this
	 * 	is quite a limited method, but in the future I can add more features to it
	 *
	 * 	parameters:
	 * 		$field	-	The field to select
	 * 		$table	-	The table to query
	 *
	 * 	returns:
	 * 		Returns errors [-1 (field),-2 (table)] when they are invalid,
	 * 		a positive integer when successful or boolean false for a general failure
	 */
	public function countRows($field,$table)
	{
		/* The original MySQL Code, please convert to PDO
			$field = $this->escape($field);
			$table = $this->escape($table);
	
			if(!$field || !strlen($field) || is_numeric($field)) return -1;
			if(!$table || !strlen($table) || is_numeric($table)) return -2;
	
			return $this->selectValue("c","count($field) as c from $table",1,true);
			*/
		return false;
	}
}