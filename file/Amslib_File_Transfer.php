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
 * 	class:	Amslib_File_Transfer
 *
 *	group:	file
 *
 *	file:	Amslib_File_Transfer.php
 *
 *	title:	A way to transfer a url to a remote server for download in a secure fashion
 *
 *	description:
 *		todo, write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_File_Transfer
{
	static protected $check		=	false;
	static protected $password	=	false;
	
	/**
	 * 	method:	getCheck
	 *
	 * 	todo: write documentation
	 */
	static protected function getCheck()
	{
		if(self::$check) return self::$check;
	
		//	NOTE: At least use something "secure" so it stops stupid eavesdroppers.
		//	NOTE: however, if you have the source, this isnt secure anymore
		return "43v023874vn2948723n49b8234difdwd".
				"3v498vn09v5y4paeurtnadqc4aeadera".
				"v98437n5vrhewfjsdbfo7awy4nq2423v".
				"pv5987nournawocfrt30v424b1eqnowu";
	}
	
	/**
	 * 	method:	getPassword
	 *
	 * 	todo: write documentation
	 */
	static protected function getPassword()
	{
		if(self::$password) return self::$password;
	
		//	NOTE: At least use something "secure" so it stops stupid eavesdroppers
		//	NOTE: however, if you have the source, this isnt secure anymore
		return "76nc575n389475yrjc089h34n07fg30m".
				"5789y3n4rc87q34x9fng4fn783g4fm07".
				"978yn4xf8xh3mf074h23fm078z1hfm01".
				"fuhasdfrgibqwdc84y2bf98uv23bf2fu";
	}

	/**
	 * 	method:	setCheck
	 *
	 * 	todo: write documentation
	 */
	static public function setCheck($value)
	{
		self::$check = sha1($value);
	}
	
	/**
	 * 	method:	setPassword
	 *
	 * 	todo: write documentation
	 */
	static public function setPassword($value)
	{
		self::$password = sha1($value);
	}
	
	/**
	 * 	method:	message
	 *
	 * 	todo: write documentation
	 */
	static public function reply($status,$message="",$payload="")
	{
		die(json_encode(array(
				"success"	=>	$status,
				"message"	=>	$message,
				"payload"	=>	$payload
		)));
	}
	
	/**
	 * 	method:	send
	 *
	 * 	Securely encrypt and send a small payload to a receiving function to decode and download
	 */
	static public function send($payload,$post_url)
	{
		$data = array(
			"check"		=>	self::getCheck(),
			"payload"	=>	$payload,
			"time"		=>	microtime(true),
			"url"		=>	true
		);
	
		$encrypted	= 	AesCtr::encrypt(json_encode($data),self::getPassword());
		$remote_url	=	"$post_url?encrypted=".base64_encode($encrypted);
		
		$reply = file_get_contents($remote_url);
	
		$json = json_decode($reply,true);
		
		if(!$json){
			Amslib_Debug::log("json failed to decode",$reply);
		}
		
		return $json;
	}

	/**
	 * 	method:	recv
	 *
	 * 	todo: write documentation
	 */
	static public function recv()
	{
		$base64 = Amslib_GET::get("encrypted");
		
		if(!$base64) self::reply(false,"missing 'encrypted' parameter");
		
		$encrypted	=	base64_decode($base64);
		$decrypted	=	AesCtr::decrypt($encrypted, self::getPassword());
		
		try{
			$json = json_decode($decrypted,true);
		}catch(Exception $e){
			//	do nothing
		}
		
		if(!isset($json) || !$json || !isset($json["check"])) self::reply(false,"invalid data");
		
		if($json["check"] != self::getCheck()) self::reply(false,"check compare failed");
		
		unset($json["check"]);
		
		//	TODO:	the sender might have posted an actual file, so we need to maybe check this and 
		//			provide the file data from the $_FILES array
		
		return $json;
	}
}