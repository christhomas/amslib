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

class Amslib_Webservice_Response_Raw
{
	protected $response;

	public function __construct($data=array())
	{
		$this->setResponse($data);

		if(!$this->hasResponse()){
			$this->resetResponse();
		}
	}

	public function setResponse($data)
	{
		$this->response = $data;
	}

	public function resetResponse()
	{
		$this->response = array();
	}

	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * 	method:	hasData
	 *
	 * 	todo: write documentation
	 */
	public function hasResponse()
	{
		return $this->response && is_array($this->response);
	}

	public function setKey($key,$value=NULL)
	{
		if(!$this->hasResponse()){
			return false;
		}

		$a = &$this->response;

		if(is_string($key)){
			$key = array($key);
		}

		while($count = count($key)){
			$index = array_shift($key);

			if($count > 1){
				if(!isset($a[$index])){
					$a[$index] = array();
				}

				$a = &$a[$index];
			}else{
				$a[$index] = $value;
			}
		}

		unset($a);

		return $value;
	}

	public function getKey($key=NULL,$default=NULL)
	{
		if(!$this->hasResponse()){
			return $default;
		}

		$a = $this->response;

		if(is_string($key)){
			$key = array($key);
		}

		foreach($key as $index){
			if(!isset($a[$index])){
				return $default;
			}

			$a = $a[$index];
		}

		return $a;
	}

	public function deleteKey($key)
	{
		if(!$this->hasResponse()){
			return NULL;
		}

		$a = &$this->response;

		if(is_string($key)){
			$key = array($key);
		}

		while($count = count($key)){
			$index = array_shift($key);

			if($count > 1){
				if(!isset($a[$index])){
					return NULL;
				}

				$a = &$a[$index];
			}else{
				$value = $a[$index];
				unset($a[$index],$a);

				return $value;
			}
		}

		return NULL;
	}

	public function hasKey($vargs)
	{
		if(!$this->hasResponse()) return false;

		$args = func_get_args();

		$count = count($args);

		if(!$count) return false;

		$response = $this->response;

		do{
			$a = array_shift($args);

			if((is_string($a) || is_numeric($a)) && array_key_exists($a,$response)){
				$response = $response[$a];
			}else{
				return false;
			}
		}while(count($args));

		return true;
	}
}