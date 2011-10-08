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
 * file: Amslib_Database_MySQL2.php
 * title: Antimatter Database: MySQL library version 2
 * description: This is a small cover object which enhanced the original with some
 * new functionality aiming to solve the fatal_error problem but keep returning data
 * version: 2.7
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

/**
 * 	class:	Amslib_Database_MySQL2
 *
 */
class Amslib_Database_MySQL2 extends Amslib_Database_MySQL
{
	protected $errors;
	
	/**
	 * 	method:	__construct
	 *
	 * 	This method is called when the database object is created, it connects to the database by default
	 */
	public function __construct($connect=true)
	{
		parent::__construct($connect);
	}
	
	protected function setDebugOutput($query)
	{
		if($this->debug){
			Amslib_Keystore::set("db_query[{$this->seq}][".microtime(true)."]",Amslib::var_dump($query,true));
		}
	}

	public function select($query,$numResults=0,$optimise=false)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;
		
		$query = "select $query";

		$this->setLastQuery($query);
		$this->selectResult = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		if($this->selectResult){
			$rowCount = mysql_num_rows($this->selectResult);

			if($numResults == 0) $numResults = $rowCount;

			return $this->getResults($numResults,$this->selectResult,$optimise);
		}

		$this->setErrors($query);

		return false;
	}

	public function insert($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;
		
		$query = "insert into $query";

		$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		$this->lastInsertId = mysql_insert_id($this->connection);
		if($result && ($this->lastInsertId !== false)) return $this->lastInsertId;

		$this->lastInsertId = false;
		
		$this->setErrors($query);
		
		return false;
	}

	public function update($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;
		
		$query = "update $query";

		$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		if($result) return mysql_affected_rows() >= 0;
		
		$this->setErrors($query);

		return false;
	}

	public function delete($query)
	{
		$this->seq++;

		if($this->getConnectionStatus() == false) return false;
		
		$query = "delete from $query";

		$this->setLastQuery($query);
		$result = mysql_query($query,$this->connection);
		$this->setDebugOutput($query);

		if($result) return mysql_affected_rows() >= 0;
		
		$this->setErrors($query);
		
		return false;
	}
	
	public function setErrors($query)
	{
		$this->errors = array(
			"db_failure"		=>	true,
			"db_query"			=>	Amslib::var_dump($query,true),
			"db_error"			=>	mysql_error(),
			"db_last_insert"	=>	$this->lastInsertId,
			"db_insert_id"		=>	mysql_insert_id(),
			"db_location"		=>	Amslib::var_dump(Amslib_Array::filterKey(array_slice(debug_backtrace(),1,5),array("file","line")),true),
		);
	}
	
	public function getErrors()
	{
		return $this->errors;
	}
}
?>
