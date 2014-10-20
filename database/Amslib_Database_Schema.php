<?php
class Amslib_Database_Schema
{
	protected $database;

	public function __construct($object)
	{
		$this->database = $object;
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
	 */
	public function getDatabases()
	{
		return $this->database->select("distinct table_schema as database_name from information_schema.tables");
	}

	/**
	 * 	method:	getTables
	 *
	 * 	todo: write documentation
	 */
	public function getTables($database=NULL)
	{
		$filter = "";
		if($database && is_string($database)){
			$database = $this->database->escape($database);

			$filter = "where table_schema='$database'";
		}

		return $this->database->select("distinct table_name,table_schema as database_name from information_schema.tables $filter");
	}

	/**
	 * 	method:	getColumns
	 *
	 * 	todo: write documentation
	 */
	public function getColumns($database=NULL,$table=NULL)
	{
		$filter = array();

		if($database && is_string($database)){
			$database = $this->database->escape($database);

			$filter[] = "table_schema='$database'";
		}

		if($table && is_string($table)){
			$table = $this->database->escape($table);

			$filter[] = "table_name='$table'";
		}

		$filter = count($filter) ? "where ".implode(" AND ",$filter) : "";

$query=<<<QUERY
		distinct column_name, table_name, table_schema as database_name
		from information_schema.columns
		$filter
		order by database_name,table_name,ordinal_position
QUERY;

		return $this->database->select($query);
	}

	/**
	 * 	method:	getFields
	 *
	 * 	todo: write documentation
	 */
	public function getFields($table)
	{
		$table = $this->database->escape($table);

		return $this->database->select("column_name from Information_Schema.Columns where table_name='$table'");
	}

	/**
	 * 	method:	getRowCount
	 *
	 * 	todo: write documentation
	 */
	public function getRowCount($database,$table)
	{
		$database	=	$this->database->escape($database);
		$table		=	$this->database->escape($table);

		$this->database->select("SQL_CALC_FOUND_ROWS * from $database.$table limit 1");

		//	TODO: missing
		return $this->database->getRealResultCount();
	}

	/**
	 * method: hasTable
	 *
	 * Find out whether the connected mysql database has a table on the database given
	 * this is useful for determining whether a database can support certain functionality
	 * or not
	 *
	 * parameters:
	 * 	$database	-	The database to check for the table
	 * 	$table		-	The table to check for
	 *
	 * returns:
	 * 	A boolean true or false result, depending on the existence of the table
	 *
	 * note:	perhaps I should split all of these "db structure" methods into a separate object
	 * 			but to do that, then you'd need to use this object to acquire the structure object
	 * 			since it would require a database connection, so it'd have to clone the connection
	 */
	public function hasTable($database,$table)
	{
		$database	=	$this->database->escape($database);
		$table		=	$this->database->escape($table);

$query=<<<HAS_TABLE
			COUNT(*)
		from
			information_schema.tables
		where
			table_schema = '$database' and table_name='$table'
HAS_TABLE;

		$result = $this->select($query,1);

		return isset($result["COUNT(*)"]) && $result["COUNT(*)"]
	}
}