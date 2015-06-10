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
	protected $route;
	protected $import;
	protected $valid;

	protected function sanitise($type,$data)
	{
		//	NOTE:	This happened for any other type than "type" because the sanitise check is different
		//			and it's much simpler for the other types of sanitation
		if($type != "type"){
			$data = array_key_exists($type,$data) ? $data[$type] : false;
		}
		
		//	Data was invalid, so we cannot sanitise it, return null
		//	NOTE: we should throw an exception probably in the future
		if(!$data) return NULL;
		
		switch($type){
			case "input":{
				if(!in_array($data,array("get","post","previous"))){
					$data = "post";
				}
			}break;

			case "output":{
				if(!in_array($data,array("json","session"))){
					$data = "session";
				}
			}break;

			case "record":{
				if(!in_array($data,array("true","false","global"))){
					$data = "true";
				}
			}break;
			
			case "optimise":{
				if(!in_array($data,array("true","false"))){
					$data = "false";
				}
			}break;

			case "type":{
				$data = $data[0] == "terminator"
					? (isset($data[1]["state"]) ? "terminator_{$data[1]["state"]}" : "terminator_common")
					: "service";
			}break;
		}

		return $data;
	}

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct($source)
	{
		$this->route	=	false;
		$this->import	=	false;
		$this->valid	=	false;

		$filename = false;

		try{
			//	NOTE: This is ugly and I believe it's a failure of Amslib_File::find() to not do this automatically
			if(file_exists($source)){
				$filename = $source;
			}else if(file_exists($f=Amslib_File::find(Amslib_Website::relative($source),true))){
				$filename = $f;
			}else if(file_exists($f=Amslib_File::find(Amslib_Website::absolute($source),true))){
				$filename = $f;
			}

			if(!$filename){
				Amslib_Debug::log("The filename was not valid, we could not getRoutes from this XML Source");
			}else{
				Amslib_QueryPath::qp($filename);
				Amslib_QueryPath::execCallback("router > path[name]",		array($this,"configPath"),		$this);
				Amslib_QueryPath::execCallback("router > service[name]",	array($this,"configService"),	$this);
				Amslib_QueryPath::execCallback("router > callback",			array($this,"configCallback"),	$this);
				Amslib_QueryPath::execCallback("router > import",			array($this,"configImport"),	$this);
				Amslib_QueryPath::execCallback("router > export",			array($this,"configExport"),	$this);

				$this->valid = true;
			}
		}catch(Exception $e){
			Amslib_Debug::log("Exception: ",$e->getMessage(),"file=",$filename,"source=",$source);
			Amslib_Debug::log("stack_trace");
		}
	}

	public function isValid()
	{
		return $this->valid;
	}

	public function configPath($name,$array,$object)
	{
		//	Ignore if there are no children
		if(empty($array["child"])) return;

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
		//	Ignore if there are no children
		if(empty($array["child"])) return;

		$d 				=	array("input"=>"post","output"=>"session","record"=>"true","optimise"=>"false");
		$a 				=	array_merge($d,array_map("strtolower",$array["attr"]));
		$a["type"]		=	$array["tag"];
		$a["src"]		=	array();
		$a["handler"]	=	array();

		//	Allowed handler types
		$handler_types = array("service","terminator_common","terminator_success","terminator_failure");

		//	Grab the default route input and output values, in order to potentially use them when setting up the handlers
		$a["input"]		=	self::sanitise("input",$a);
		$a["output"]	=	self::sanitise("output",$a);
		$a["record"]	=	self::sanitise("record",$a);
		$a["optimise"]	=	self::sanitise("optimise",$a);

		foreach($array["child"] as $child){
			switch($child["tag"]){
				case "src":{
					$a["src"]["default"] = $child["value"];
				}break;

				case "handler":
				case "terminator":{
					$c = $child["attr"];

					//	Set the type of webservice this will be attached
					$c["type"] = self::sanitise("type",array($child["tag"],$c));

					//	Ignore the webservice type is not valid
					if(!in_array($c["type"],$handler_types)) continue;

					//	Failure will block unless you tell the system to ignore failure
					//	A reason for wanting to ignore failures is that you want to accumulate all the results
					//	and post-process them into a final result, however this requires you setup the webservices with care
					//	and attention that failures will not cause unpredictable errors, however, that this, it is useful
					if(!isset($c["failure"]) || !in_array($c["failure"],array("break","stop","ignore"))){
						$c["failure"] = "break";
					}

					$a["handler"][] = $c;
				}break;
			}
		}

		$this->route[] = $a;
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
		$import = $array["attr"];

		if(!isset($import["name"])) $import["name"] = "import_".count($this->import);

		if(array_key_exists("child",$array)){
			foreach($array["child"] as $c){
				if($c["tag"] == "url" && isset($c["attr"]["callback"])){
					$callback = $c["attr"]["callback"];
					
					if(is_callable($callback)){
						$c["value"] = call_user_func($callback);
					}
				}
				
				$import[$c["tag"]] = $c["value"];
			}
		}

		if(!isset($import["output"]) || !isset($import["url"])) return false;

		if(!empty($import)) $this->import[] = $import;

		return true;
	}

	public function configExport($name,$array,$object)
	{
		foreach($array["child"] as $c){
			if($c["tag"] == "restrict"){
				Amslib_Router::setExportRestriction($c["value"], false);
			}
		}

		return true;
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