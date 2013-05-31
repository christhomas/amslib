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
 * 	class:	Amslib_Status_Code
 *
 *	group:	core
 *
 *	file:	Amslib_Status_Code.php
 *
 *	description: todo, write description
 *
 * 	todo: write documentation
 */
class Amslib_Status_Code extends Amslib_Database_MySQL
{
	public function __construct($db=NULL)
	{
		parent::__construct(false);

		if($db) $this->copyConnection($db);
	}

	static public function &getInstance($db=NULL)
	{
		static $instance = NULL;

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