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
	protected $url;
	protected $params;
	protected $sharedSession;
	protected $debug;

	public function __construct($url,$params=array(),$sharedSession=false)
	{
		$this->setURL($url);
		$this->setParams($params);
		$this->setSharedSessionState($sharedSession);
		$this->setDebugState(false);
	}

	public function setDebugState($state)
	{
		$this->debug = !!$state;
	}

	public function setURL($url)
	{
		$this->url = $url;
	}

	public function setParams($params)
	{
		$this->params = $params;
	}

	public function setSharedSessionState($state)
	{
		$this->sharedSession = $state;
	}

	public function execute($raw=false)
	{
		$reply = $exception = false;

		if($this->debug){
			$raw = true;
		}

		try{
			if(strlen($this->url) == 0) throw new Exception("webservice url was invalid");

			$curl = curl_init();

			//	This is just the first version of this code, it works, but it's hardly very elegant.
			//	Also, I think I need to merge code from Amslib_Plugin_Service because we seem to be duplicating
			//	it a lot here, perhaps we need to have a common shared object with methods to set and get from
			//	the storage variable
			if($this->sharedSession){
				$key_remote		= "/amslib/webservice/session/remote/";
				$key_request	= "/amslib/webservice/session/request/";

				$id_session = Amslib_SESSION::get($key_remote);

				if($id_session){
					$cookie = "PHPSESSID=$id_session; path=/";//.Amslib_Router_URL::getFullURL();
					curl_setopt($curl,CURLOPT_COOKIE,$cookie);
					Amslib_SESSION::set("REQUEST_COOKIE",$cookie);
				}else{
					$this->params[$key_request] = true;
				}
			}

			$params = http_build_query(Amslib_Array::valid($this->params));

			curl_setopt($curl,CURLOPT_URL,				$this->url);
			curl_setopt($curl,CURLOPT_POST,				true);
			curl_setopt($curl,CURLOPT_HTTP_VERSION,		1.0);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,	true);
			curl_setopt($curl,CURLOPT_HEADER,			false);
			curl_setopt($curl,CURLOPT_POSTFIELDS,		$params);

			$reply = curl_exec($curl);
			curl_close($curl);

			$response = new Amslib_Webservice_Response();
			$response->setState("raw",$raw);
			$response->setResponse($reply);

			if($this->sharedSession && !$id_session){
				$data = $response->getRawData();
				if(isset($data[$key_remote])){
					Amslib_SESSION::set($key_remote,$data[$key_remote]);
				}
			}

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