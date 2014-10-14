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
 *    {Alfonso Fernandez-Ocampo} - Author = afernandezocampo@gmail.com
 *
 *******************************************************************************/

/**
 * 	class:	Amslib_Database_PDO
 *
 *	group:	database
 *
 *	file:	Amslib_Database_PDO.php
 *
 *	title:	Antimatter Database: PDO library
 *
 *	description:	This is a small cover object which enhanced the original with some
 * 					new functionality aiming to solve the fatal_error problem but keep returning data
 *
 * 	todo: write documentation
 */

	/**		Jedi says:
	 * -	We need to look at the mysql code and figure out how to reimplement the same api here
	 * -	If we are creating a lot of shadow methods, we need to look at how to use the mixin object
	 * -	But in order to do this, perhaps the mixin object needs to change how it is implemented to be more generic
	 * -	Although I need to figure out whether I should inherit from PDO, or wrap it up, I'm favouring wrapping it
	 */


class Amslib_Database_PDO extends Amslib_Database
{
	var $db_type; // <-= BAD ALFONSO!!! class members ALWAYS have an access type, protected, public or private

	public function __construct($connect=true)
	{
		parent::__construct();

		$this->errors = array();

		//	TODO: we should implement a try/catch block to easily catch disconnected databases
		if($connect) $this->connect();
	}

	public function getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	public function connect($details=false)
	{
		$this->db_type = $DB_TYPE;
		$details = $this->getConnectionDetails();
		$server = $details['server'];
		$username = $details['username'];
		$password =  $details['password'];
		$database = $details['database'];
		$dns = "mysql:host=$server:$database";

		try {
			$this->connection = new PDO( $dns, $username, $password );
		} catch ( Exception $e ) {
			echo "Impossible to connect to the Mysql Database : ", $e->getMessage();
			die();
		}
	}

	function lastInsertRowId() {
		switch($this->db_type) {
			case 'SQLITE':
				return $this->connect()->lastInsertRowId();
			case 'MYSQL':
				return mysql_insert_id($this->db);
		}
	}

	public function query($query){
		$this->setLastQuery($query);

		$results = $this->connection->query($query);
		$this->setDebugOutput($query);
		return $results->fetchAll();
	}

	/* this select and returns the typical MySQL array in Amslib_Database_MySQL */
	public function select($query,$numResults=0,$optimise=false){
		$query ="select $query";
		$this->setLastQuery($query);

		if($numResults == 0) $numResults = PHP_INT_MAX;
		$results = $this->connection->query($query);
		$this->setDebugOutput($query);
		return $results->fetchAll();
	}

	/**
	 * 	method:	beginTransaction
	 *
	 * 	todo: write documentation
	 */
	public function beginTransaction()
	{
		return $this->connection->beginTransaction();
	}

	/**
	 * 	method:	commitTransaction
	 *
	 * 	todo: write documentation
	 */
	public function commitTransaction()
	{
		return $this->connection->commit();
	}

	/**
	 * 	method:	rollbackTransaction
	 *
	 * 	todo: write documentation
	 */
	public function rollbackTransaction()
	{
		return $this->connection->rollBack();
	}
}