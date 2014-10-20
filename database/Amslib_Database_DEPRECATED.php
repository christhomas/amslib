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
		return self::sharedConnection($object);
	}

	public static function getSharedConnection()
	{
		return self::sharedConnection();
	}

	public function setDBErrors($data)
	{
		$args = func_get_args();

		call_user_func_array(array($this,"setError"),$args);
	}

	public function getDBErrors($clear=true)
	{
		return $this->getError($clear);
	}

	public function getConnectionStatus()
	{
		return $this->isConnected();
	}

	public function setDebug($state)
	{
		$this->setDebugState($state);
	}

	protected function getLastTransactionId()
	{
		return $this->getLastInsertId();
	}

	public function getDBList()
	{
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getDatabases();
	}

	public function getDBTables($database_name=NULL)
	{
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getTables($database_name);
	}

	public function getDBColumns($database_name=NULL,$table_name=NULL)
	{
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getTables($database_name,$table_name);
	}

	public function getDBTableFields($table)
	{
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getFields($table);
	}

	public function getDBTableRowCount($database,$table)
	{
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getRowCount($database,$table);
	}

	public function hasTable($database,$table)
	{
		$this->schema = new Amslib_Database_Schema($this);

		$this->schema->hasTable($database,$table);
	}

	public function getSQLLimit($length=NULL,$offset=NULL)
	{
		$this->debug(__METHOD__,"DEPRECATED METHOD",$length,$offset);
		return $this->buildLimit($length,$offset);
	}

	//	this method was a horribly, ugly mistake
	public function select2($query,$numResults=0,$optimise=false)
	{
		$this->debug(__METHOD__,"DEPRECATED METHOD",$query,$numResults,$optimise);
		return $this->select($query,$numResults,$optimise);
	}

	public function getSearchResultHandle()
	{
		return $this->getHandle();
	}

	public function storeSearchHandle()
	{
		return $this->pushHandle();
	}

	public function restoreSearchHandle($handle=NULL)
	{
		if(!$this->isHandle($handle)) $handle = $this->popHandle();

		return $this->setHandle($handle);
	}

	public function beginTransaction()
	{
		return $this->begin();
	}

	public function commitTransaction()
	{
		return $this->commit();
	}

	public function rollbackTransaction()
	{
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
		return $this->freeHandle($handle);
	}

	//	a quick fix for the PDO object which doesn't support this method yet
	public function freeHandle($handle)
	{
		return false;
	}

	function lastInsertRowId()
	{
		return $this->getLastInsertId();
	}

	public function setTable()
	{
		$args = func_get_args();

		return call_user_func_array(array($this,"setAlias"),$args);
	}
}