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
 * file: Amslib_Database_Reflection.php
 * title: Antimatter Database: Reflection library
 * description: The purpose of this library is to obtain details about the database programatically
 * 				by querying the structure of the database in order to find the lengths of fields
 * 				or which tables are available, etc.  This might not work with all database schemeas
 * version: 0.5
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Database_Reflection extends Amslib_Database
{
	public function __construct()
	{
		parent::__construct();
	}
	
	static public function &getInstance()
	{
		static $instance = NULL;
		
		if($instance === NULL) $instance = new Amslib_Database_Reflection();
		
		return $instance;
	}
}