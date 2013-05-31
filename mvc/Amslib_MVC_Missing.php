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
 * 	class:	Amslib_MVC_Missing
 *
 *	group:	mvc
 *
 *	file:	Amslib_MVC_Missing.php
 *
 *	title:	Implements a fake interface where everything returns false and it logs errors
 *
 *	description:
 *		This object's purpose is to provide an error reporting mechanism whilst
 * 		avoiding the fatal errors, warnings, etc when you try to obtain a plugin which 
 * 		doesn't exist, this object will be returned and it's using a __call interface
 * 		to hijack and control the system, report errors and attempt to correct failures
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_MVC_Missing
{
	public function __construct(){}
	
	public function __call($name,$args)
	{
		//	TODO: we need a logging interface to record this error
		
		return false;
	}
}