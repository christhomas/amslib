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
 * 	class:	Amslib_Router_Source_XML
 *
 *	group:	router
 *
 *	file:	Amslib_Router_Source_XML.php
 *
 *	description:
 *		write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Router_Source_XML
{
	protected $filename;
	protected $route;
	protected $import;

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct($source)
	{
		$this->filename	=	false;
		$this->route	=	false;
		$this->import	=	false;

		try{
			//	NOTE: This is ugly and I believe it's a failure of Amslib_File::find() to not do this automatically
			if(!$this->filename && file_exists($source)){
				$this->filename = $source;
			}
			if(!$this->filename && file_exists($f=Amslib_File::find(Amslib_Website::rel($source),true))){
				$this->filename = $f;
			}
			if(!$this->filename && file_exists($f=Amslib_File::find(Amslib_Website::abs($source),true))){
				$this->filename = $f;
			}

			if(!$this->filename){
				Amslib::errorLog("The filename was not valid, we could not getRoutes from this XML Source");
			}else{
				Amslib_QueryPath::qp($this->filename);
				Amslib_QueryPath::execCallback("router > path[name]",		array($this,"configPath"),		$this);
				Amslib_QueryPath::execCallback("router > service[name]",	array($this,"configService"),	$this);
				Amslib_QueryPath::execCallback("router > callback",			array($this,"configCallback"),	$this);
				Amslib_QueryPath::execCallback("router > import",			array($this,"configImport"),	$this);
			}
		}catch(Exception $e){
			Amslib::errorLog("Exception: ",$e->getMessage(),"file=",$this->filename,"source=",$source);
			Amslib::errorLog("stack_trace");
		}
	}

	public function configPath($name,$array,$object)
	{
		$array["name"]			=	$array["attr"]["name"];
		$array["type"]			=	$array["tag"];
		$array["src"]			=	array();
		$array["javascript"]	=	array();
		$array["stylesheet"]	=	array();

		foreach($array["child"] as $child){
			switch($child["tag"]){
				case "src":{
					//	Obtain the language attribute for this path url/source
					$lang = isset($child["attr"]["lang"]) ? $child["attr"]["lang"] : "default";

					$array[$child["tag"]][$lang] = $child["value"];

					//	if there is no default, create one, all routers require a "default source"
					if(!isset($array[$child["tag"]]["default"])){
						$array[$child["tag"]]["default"] = $child["value"];
					}
				}break;

				case "resource":{
					$array[$child["tag"]] = $child["value"];
				}break;

				case "parameter":{
					//	If this parameter has no attribute id, you cannot process it
					if(!isset($child["attr"]["id"])) continue;

					$array["route_param"][$child["attr"]["id"]] = $child["value"];
				}break;

				case "stylesheet":
				case "javascript":{
					//	NOTE: interesting!! could this be the solution to for how to automatically assign the "plugin"
					//			attribute to routes so the system can identify which route belongs to which plugin?
					if(!isset($child["attr"]["plugin"])) $child["attr"]["plugin"] = "__CURRENT_PLUGIN__";

					$array[$child["tag"]][] = array("value"=>$child["value"],"plugin"=>$child["attr"]["plugin"]);
				}break;
			}
		}

		//	remove unwanted data that would just clog up the data array
		unset($array["tag"],$array["attr"],$array["child"]);

		$this->route[] = $array;
	}

	public function configService($name,$array,$object)
	{
		$attr				=	$array["attr"];
		$array["name"]		=	$attr["name"];
		$array["type"]		=	$array["tag"];
		$array["src"]		=	array();
		$array["handler"]	=	array();

		//	NOTE:	Maybe there is a better way to organise this code, seems we are
		//			strtolower()ing a bunch of times when perhaps is not necessary

		//	Grab the default route input and output values, in order to potentially use them when setting up the handlers
		$input	= isset($attr["input"])		? strtolower($attr["input"])	:	"post";
		$output	= isset($attr["output"])	? strtolower($attr["output"])	:	"session";

		//	Make sure the input source and output targets are valid, otherwise default to sensible values
		if(!in_array($input,	array("get","post")))		$input	=	"post";
		if(!in_array($output,	array("json","session")))	$output	=	"session";

		foreach($array["child"] as $child){
			if($child["tag"] == "src"){
				$array["src"]["default"] = $child["value"];
			}else if($child["tag"] == "handler"){
				$c = $child["attr"];
				//	Default input source if not found to default route source
				if(!isset($c["input"])) $c["input"] = strtolower($input);
				//	Validate it was set to a correct value
				if(!in_array($c["input"],array("get","post"))) $c["input"] = "post";

				//	By default, record each webservice as they happen, but not globally, separately.
				if(!isset($c["record"])){
					$c["record"] = true;
					$c["global"] = false;
				}else{
					//	By default, recording is disabled unless it's enabled by a valid value
					$record = false;

					//	If the record value was global, per-webservice recording is disabled, but global recording is enabled
					if(strpos($c["record"],"global")!== false)	$c["global"] = true;
					//	If the record value was "true" or "record" then obviously turn on per-webservice recording
					if(strpos($c["record"],"true")	!== false)	$record = true;
					if(strpos($c["record"],"record")!== false)	$record = true;

					$c["record"] = $record;
				}

				//	Failure will block unless you tell the system to ignore failure
				//	A reason for wanting to ignore failures is that you want to accumulate all the results
				//	and post-process them into a final result, however this requires you setup the webservices with care
				//	and attention that failures will not cause unpredictable errors, however, that this, it is useful
				$c["failure"] = isset($c["failure"]) && strpos($c["failure"],"ignore") ? false : true;

				$array["handler"][] = $c;
			}
		}
		//	remove unwanted data that would just clog up the data array
		unset($array["tag"],$array["attr"],$array["child"]);

		$this->route[] = $array;
	}

	public function configCallback($name,$array,$object)
	{
		if(!isset($array["value"]) || !strlen($array["value"])) return;

		$callback = is_callable($array["value"]) ? $array["value"] : false;

		if($callback && is_sting($callback) && strlen($callback)){
			Amslib_Router::setCallback($callback);
		}
	}

	public function configImport($name,$array,$object)
	{
		if(!isset($array["attr"]["output"]) || !isset($array["attr"]["url"])) return;

		$this->import[] = array(
				"output"	=>	$array["attr"]["output"],
				"url"		=>	$array["attr"]["url"]
		);
	}

	/**
	 * 	method:	getRoutes
	 *
	 * 	todo: write documentation
	 */
	public function getRoutes()
	{
		return Amslib_Array::valid($this->route);
	}

	/**
	 * 	method:	getImports
	 *
	 * 	todo: write documentation
	 */
	public function getImports()
	{
		return Amslib_Array::valid($this->import);
	}
}