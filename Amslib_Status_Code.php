<?php
class Amslib_Status_Code extends Amslib_Database_MySQL
{
	public function __construct($db=NULL)
	{
		parent::__construct(false);

		if($db) $this->copyConnection($db);
	}

	static public function &getInstance($db=NULL)
	{
		static $instance === NULL;

		if($instance === NULL) $instance = new self($db);

		return $instance;
	}

	public function setStatusValueByValue($table,$field,$pkid,$value)
	{
		$table	=	$this->escape($table);
		$field	=	$this->escape($field);
		$pkid	=	$this->escape($pkid);
		$value	=	$this->escape($value);

		$db->update("$table set $field='$value'")
	}

	public function setStatusValueByIndent($table,$field,$ident)
	{
		$table	=	$this->escape($table);
		$field	=	$this->escape($field);
		$ident	=	$this->escape($ident);
	}

	public function setStatusIndentByValue($table,$field,$value)
	{
		$table	=	$this->escape($table);
		$field	=	$this->escape($field);
		$value	=	$this->escape($value);
	}

	public function setStatusIdentByIdent($table,$field,$ident)
	{
		$table	=	$this->escape($table);
		$field	=	$this->escape($field);
		$ident	=	$this->escape($ident);
	}

	public function getStatusValueByIdent($ident)
	{
		$ident	=	$this->escape($ident);
	}

	public function getStatusIdentByValue($value)
	{
		$value	=	$this->escape($value);
	}
}