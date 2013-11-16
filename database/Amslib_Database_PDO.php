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
class Amslib_Database_PDO extends Amslib_Database
{
	/**
	 * -	We need to look at the mysql code and figure out how to reimplement the same api here
	 * -	If we are creating a lot of shadow methods, we need to look at how to use the mixin object
	 * -	But in order to do this, perhaps the mixin object needs to change how it is implemented to be more generic
	 * -	Although I need to figure out whether I should inherit from PDO, or wrap it up, I'm favouring wrapping it
	 */
}