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

	public function setStatusValue($table,$field,$pkid,$pkval,$value)
	{
		if($this->getConnectionStatus() == false) return false;

		$table	=	$this->escape($table);
		$field	=	$this->escape($field);
		$pkid	=	$this->escape($pkid);
		$pkval	=	$this->escape($pkval);
		$value	=	$this->escape($value);

		return $this->update("$table set $field='$value' where $pkid='$pkval'");
	}

	public function setStatusValueByIndent($table,$field,$pkid,$pkval,$ident)
	{
		$ident = $this->escape($ident);
		$value = $this->selectValue("id","id from {$this->table} where ident='$ident' order by id desc",1,true)

		return $this->setStatusValue($table,$field,$pkid,$pkval,$value);
	}

	public function setStatusIndentByValue($table,$field,$pkid,$pkval,$value)
	{
		$value = $this->escape($value);
		$ident = $this->selectValue("ident","ident from {$this->table} where id='$value' order by id desc",1,true);
	}

	public function getStatusIdentByValue($value)
	{
		if($this->getConnectionStatus() == false) return false;

		$value = $this->escape($value);

		return $this->selectValue("ident","ident from {$this->table} where id='$value'",1,true);
	}

	public function getStatusValueByIdent($ident)
	{
		if($this->getConnectionStatus() == false) return false;

		$ident = $this->escape($ident);

		return $this->selectValue("id","id from {$this->table} where ident='$ident'",1,true);
	}

	public function getStatusByValue($value)
	{
		if($this->getConnectionStatus() == false) return false;

		$value = $this->escape($value);

		return $this->select("* from {$this->table} where id='$value'",1,true);
	}

	public function getStatusByIdent($ident)
	{
		if($this->getConnectionStatus() == false) return false;

		$ident = $this->escape($ident);

		return $this->select("* from {$this->table} where ident='$ident'",1,true);
	}
}