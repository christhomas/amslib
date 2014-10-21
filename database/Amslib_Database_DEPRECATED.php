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
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return self::sharedConnection($object);
	}

	public static function getSharedConnection()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return self::sharedConnection();
	}

	public function setDBErrors($data)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		$args = func_get_args();

		call_user_func_array(array($this,"setError"),$args);
	}

	public function getDBErrors($clear=true)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return $this->getError($clear);
	}

	public function getConnectionStatus()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return $this->isConnected();
	}

	public function setDebug($state)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		$this->setDebugState($state);
	}

	protected function getLastTransactionId()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return $this->getLastInsertId();
	}

	public function getDBList()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getDatabases();
	}

	public function getDBTables($database_name=NULL)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getTables($database_name);
	}

	public function getDBColumns($database_name=NULL,$table_name=NULL)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getTables($database_name,$table_name);
	}

	public function getDBTableFields($table)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getFields($table);
	}

	public function getDBTableRowCount($database,$table)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		$this->schema = new Amslib_Database_Schema($this);

		return $this->schema->getRowCount($database,$table);
	}

	public function hasTable($database,$table)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
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
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return $this->getHandle();
	}

	public function storeSearchHandle()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return $this->pushHandle();
	}

	public function restoreSearchHandle($handle=NULL)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		if(!$this->isHandle($handle)) $handle = $this->popHandle();

		return $this->setHandle($handle);
	}

	public function beginTransaction()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return $this->begin();
	}

	public function commitTransaction()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return $this->commit();
	}

	public function rollbackTransaction()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
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
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return $this->freeHandle($handle);
	}

	//	a quick fix for the PDO object which doesn't support this method yet
	public function freeHandle($handle)
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return false;
	}

	function lastInsertRowId()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		return $this->getLastInsertId();
	}

	public function setTable()
	{
		Amslib_Debug::log("DEPRECATED METHOD",__METHOD__);
		$args = func_get_args();

		return call_user_func_array(array($this,"setAlias"),$args);
	}
}