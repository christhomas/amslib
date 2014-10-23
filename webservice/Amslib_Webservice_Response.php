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

class Amslib_Webservice_Response
{
	protected $response;

	public function __construct($response=NULL)
	{
		$this->response = $response;
	}

	public function getData($name=NULL)
	{
		$map = array(
			"raw"		=>	"Amslib_Webservice_Response_Raw",
			"json"		=>	"Amslib_Webservice_Response_JSON",
			"amslib"	=>	"Amslib_Webservice_Response_Amslib"
		);

		if(!isset($map[$name])) $name = "raw";

		return new $map[$name]($this->response);
	}
}