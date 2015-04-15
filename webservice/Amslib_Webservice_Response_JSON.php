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

class Amslib_Webservice_Response_JSON extends Amslib_Webservice_Response_Raw
{
	public function __construct($data=array())
	{
		parent::__construct($data);
	}

	public function setResponse($data)
	{
		$exception = "";

		ob_start();
		try{
			if(is_string($data)){
				$this->response = json_decode($data,true);
			}else if(is_array($data)){
				$this->response = $data;
			}
		}catch(Exception $e){
			$exception = $e->getMessage();
		}
		$output = ob_get_clean();

		if(strlen($output) || strlen($exception)){
			Amslib_Debug::log("json_decode probably has failed with the following output",$output,$exception);
		}
	}
}