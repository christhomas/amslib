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
	protected $headers;
	protected $boundary;

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		//	Request apache headers, so you can find the content length, etc
		$this->headers	=	apache_request_headers();
		$this->boundary	=	false;
	}

	public function getContentLength()
	{
		$cl = "Content-Length";

		return isset($this->headers[$cl])
			? $this->headers[$cl]
			: 0;
	}

	public function getContentBoundary()
	{
		$ct = "Content-Type";

		$this->boundary = false;

		if($this->headers[$ct] && strpos($this->headers[$ct],"boundary")){
			//	Extract boundary from content type header
			$this->boundary = $this->headers[$ct];
			$this->boundary = explode(";",$this->boundary);
			$this->boundary = $this->boundary[1];
			$this->boundary = explode("=",$this->boundary);
			$this->boundary = trim($this->boundary[1]);
		}

		return $this->boundary;
	}
}