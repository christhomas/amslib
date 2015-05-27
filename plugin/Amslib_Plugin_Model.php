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
 * 	class:	Amslib_Plugin_Model
 *
 *	group:	plugin
 *
 *	file:	Amslib_Plugin_Model.php
 *
 *	description:
 *		todo, write description
 *
 * 	todo:
 * 		write documentation
 *
 * 	notes:
 * 		-	this object is getting smaller and smaller whilst it's
 * 			functionality is being absorbed into the parent object
 * 		-	perhaps this means I should work to delete this object
 * 			and redistribute it's code elsewhere.
 * 		-	I DONT THINK THIS IS THE CORRECT LOCATION FOR THIS FILE
 * 			SINCE ITS ALMOST 100% NOT A PLUGIN SPECIFIC OBJECT, ITS
 * 			A DATABASE OBJECT, PERHAPS I CAN CALL IT SOMETHING LIKE
 * 			Amslib_Database_Model INSTEAD?
 * 		-	(23/10/2014): I still think this object is stupid and
 * 			the debugging code is probably not very nice, I should
 * 			do something nicer, build into the base layer
 * 		-	(27/05/2015): Slowly but surely, being deleted and all the
 * 			functionality is being removed
 *
 */
class Amslib_Plugin_Model extends Amslib_Database
{
	/**
	 * 	object: $api
	 * 
	 * 	The API Object for the plugin which owns this model object
	 */
	protected $api;
	
	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct($connect=false)
	{
		parent::__construct($connect);
	}

	/**
	 * 	method:	isInitialised
	 *
	 * 	todo: write documentation
	 */
	public function isInitialised()
	{
		//	FIXME: this is overly simplistic, but quite realiable.

		return $this->api ? true : false;
	}

	/**
	 * 	method:	initialiseObject
	 *
	 * 	todo: write documentation
	 */
	public function initialiseObject($api)
	{
		if(!$api instanceof Amslib_MVC){
			throw new Exception("api variable passed was not valid");
		}

		$this->api = $api;
		$this->copyConnection($this->api->getModel());
	}
}