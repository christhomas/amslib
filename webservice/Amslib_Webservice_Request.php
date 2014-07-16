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

class Amslib_Webservice_Request
{
	protected $webservice;
	protected $url;
	protected $params;

	public function __construct($webservice)
	{
		$this->setWebservice($webservice);
		$this->setURL(false);
		$this->setParameters(array());
	}

	public function setWebservice($webservice)
	{
		if($webservice instanceof Amslib_Webservice){
			$this->webservice = $webservice;
		}else{
			$this->webservice = NULL;
		}
	}

	public function setURL($url)
	{
		$this->url = $url;
	}

	public function setParameters($params)
	{
		$this->params = $params;
	}

	public function execute($raw=false)
	{
		$reply = $exception = false;

		try{
			if(strlen($this->url) == 0) throw new Exception("webservice url was invalid");

			$params = http_build_query(Amslib_Array::valid($this->params));

			$curl_options = array(
					CURLOPT_URL				=> $this->url,
					CURLOPT_POST			=> true,
					CURLOPT_POSTFIELDS		=> $params,
					CURLOPT_HTTP_VERSION	=> 1.0,
					CURLOPT_RETURNTRANSFER	=> true,
					CURLOPT_HEADER			=> false
			);

			$curl = curl_init();
			curl_setopt_array($curl,$curl_options);
			$reply = curl_exec($curl);
			curl_close($curl);

			$response = new Amslib_Webservice_Response($this);
			$response->setResponse($reply);
			$response->setState("raw",$raw);

			return $response;
		}catch(Exception $e){
			$exception = $e->getMessage();
		}

		Amslib::errorLog(
			"EXCEPTION: ",		$exception,
			"WEBSERVICE URL: ",	$this->url,
			"PARAMS: ",			$params,
			"DATA: ",			$reply
		);

		return false;
	}
}