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
 * 	class:	Amslib_Apache
 *
 *	group:	core
 *
 *	file:	Amslib_Apache.php
 *
 *	description:  todo, write description
 *
 * 	todo: write documentation
 *
 */
class Amslib_Apache
{
	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		//	Request apache headers, so you can find the content length
		$headers = apache_request_headers();
		$cl = "Content-Length";
		$ct = "Content-Type";

		//	return either the content length, or false
		if(isset($headers[$cl]) && isset($headers[$ct])){
			$totalBytes = $headers[$cl];
			
			//	Extract boundary from content type header
			$boundary = $headers[$ct];
			$boundary = explode(";",$boundary);
			$boundary = $boundary[1];
			$boundary = explode("=",$boundary);
			$boundary = $boundary[1];
			
			print("boundary length = ".strlen($boundary)."<br/>");
			print("boundary = ".$boundary."<br/>");
			
			return true;
		}
				
		return false;
	}
}