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
* File: Amslib_File_Secure_Transfer.php
* Title: A way to securely transfer a url to a remote server for download
* Project: Amslib (antimatter studios library)
*
* Contributors/Author:
*    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
*******************************************************************************/
class Amslib_File_Secure_Transfer
{
	static public $check		=	false;
	static public $password	=	false;

	static public function setCheck($value)
	{
		self::$check = sha1($value);
	}

	static public function setPassword($value)
	{
		self::$password = sha1($value);
	}

	static public function getCheck()
	{
		if(self::$check) return self::$check;

		//	NOTE: At least use something "secure" so it stops stupid eavesdroppers.
		//	NOTE: however, if you have the source, this isnt secure anymore
		return "43v023874vn2948723n49b8234difdwd".
				"3v498vn09v5y4paeurtnadqc4aeadera".
				"v98437n5vrhewfjsdbfo7awy4nq2423v".
				"pv5987nournawocfrt30v424b1eqnowu";
	}

	static public function getPassword()
	{
		if(self::$password) return self::$password;

		//	NOTE: At least use something "secure" so it stops stupid eavesdroppers
		//	NOTE: however, if you have the source, this isnt secure anymore
		return "76nc575n389475yrjc089h34n07fg30m".
				"5789y3n4rc87q34x9fng4fn783g4fm07".
				"978yn4xf8xh3mf074h23fm078z1hfm01".
				"fuhasdfrgibqwdc84y2bf98uv23bf2fu";
	}

	static public function message($status,$message="",$url="")
	{
		die(json_encode(array(
				"success"	=>	$status,
				"message"	=>	$message,
				"url"		=>	$url
		)));
	}

	static public function encrypt($payload,$post_url)
	{
		$data = array(
				"check"		=>	self::getCheck(),
				"payload"	=>	$payload,
				"time"		=>	microtime(true)
		);

		$encrypted	= 	AesCtr::encrypt(json_encode($data),self::getPassword());
		$remote_url	=	$post_url.base64_encode($encrypted);

		return file_get_contents($remote_url);
	}

	static public function decrypt()
	{
		$base64		=	Amslib::getGET("encrypted");

		if(!$base64) self::message(false,"missing parameter");

		$encrypted	=	base64_decode($base64);
		$decrypted	=	AesCtr::decrypt($encrypted, self::getPassword());
		$json		=	json_decode($decrypted);

		if(!$json || !isset($json->check)) self::message(false,"invalid data");

		if($json->check != self::getCheck()) self::message(false,"compare failed");

		return $json;
	}
}