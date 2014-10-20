<?php
class Amslib_Database_Schema extends Amslib_Database_MySQL
{
	public function __construct($object)
	{
		parent::__construct(false);

		$this->copyConnection($object);
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) self::$instance = new self();

		return self::$instance;
	}

	/**
	 * 	method:	getDatabases
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function getDatabases()
	{
		return $this->select("distinct table_schema as database_name from information_schema.tables");
	}

	/**
	 * 	method:	getTables
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function getTables($database_name=NULL)
	{
		$filter = "";
		if($database_name && is_string($database_name)){
			$database_name = $this->escape($database_name);

			$filter = "where table_schema='$database_name'";
		}

		return $this->select("distinct table_name,table_schema as database_name from information_schema.tables $filter");
	}

	/**
	 * 	method:	getColumns
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function getColumns($database_name=NULL,$table_name=NULL)
	{
		$filter = array();

		if($database_name && is_string($database_name)){
			$database_name = $this->escape($database_name);

			$filter[] = "table_schema='$database_name'";
		}

		if($table_name && is_string($table_name)){
			$table_name = $this->escape($table_name);

			$filter[] = "table_name='$table_name'";
		}

		$filter = count($filter) ? "where ".implode(" AND ",$filter) : "";

		$query=<<<QUERY
		distinct column_name, table_name, table_schema as database_name
		from information_schema.columns
		$filter
		order by database_name,table_name,ordinal_position
QUERY;

		return $this->select($query);
	}

	/**
	 * 	method:	getFields
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function getFields($table)
	{
		$table = $this->escape($table);

		return $this->select("column_name from Information_Schema.Columns where table_name='$table'");
	}

	/**
	 * 	method:	getRowCount
	 *
	 * 	todo: write documentation
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function getRowCount($database,$table)
	{
		$database	=	$this->escape($database);
		$table		=	$this->escape($table);

		$this->select("SQL_CALC_FOUND_ROWS * from $database.$table limit 1");

		//	TODO: missing
		return $this->getRealResultCount();
	}
}