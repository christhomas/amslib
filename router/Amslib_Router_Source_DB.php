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
 * 	class:	Amslib_Router_Source_DB
 *
 *	group:	router
 *
 *	file:	Amslib_Router_Source_DB.php
 *
 *	description:
 *		write description
 *
 * 	todo:
 * 		-	write documentation
 * 		-	refactor against the XML source for common processing methods
 *
 */
class Amslib_Router_Source_DB
{
	protected $database;
	protected $table;
	protected $route;
	protected $import;
	protected $valid;

	protected function getURLByRouteId($id_route)
	{
		if(!$id_route || !is_numeric($id_route)) return false;

		$id_route = intval($id_route);

$query=<<<QUERY
			url,
			ifnull(l.code_4,"default") as lang
		from
			{$this->table["url"]} as u
		left join
			{$this->table["lang"]} as l on l.id_lang = u.id_lang
		where
			id_route = $id_route
				and
			u.active = 1
QUERY;

		$list = Amslib_Array::valid($this->database->select($query));

		foreach($list as $key=>$item){
			unset($list[$key]);
			$list[$item["lang"]] = $item["url"];
		}

		return $list;
	}

	protected function getParam($id_route,$type="param")
	{
		if(!$id_route || !is_numeric($id_route)) return false;

		$id_route = intval($id_route);
		$type = $this->database->escape($type);

$query=<<<QUERY
			pm.name,
			pm.data as value,
			pt.name as type
		from
			{$this->table["param"]} as pm
		inner join
			{$this->table["param_type"]} as pt on pt.id_type = pm.id_type and pt.name='$type'
		inner join
			{$this->table["route"]} as ru on ru.id_route = pm.id_route and ru.id_route = $id_route
QUERY;

		$list = Amslib_Array::valid($this->database->select($query));

		foreach($list as $key=>$item){
			switch($item["type"]){
				case "param":
				case "resource":{
					unset($list[$key]);
					$list[$item["name"]] = $item["value"];
				}break;

				case "stylesheet":
				case "javascript":{
					//	FIXME:	I am hard coding __CURRENT_PLUGIN__ here and
					//			I have no idea (yet) how to specify yet the plugin to attach it

					$list[$key] = array(
						"value"		=>	$item["value"],
						"plugin"	=>	"__CURRENT_PLUGIN__"
					);
				}break;
			};
		}

		return $list;
	}

	protected function getHandlerByRouteId($id_route)
	{
		if(!$id_route || !is_numeric($id_route)) return false;

		$id_route = intval($id_route);

$query=<<<QUERY
			hd.id_handler,
			hd.id_route,
			hd.id_type,
			ht.name as type,
			hd.plugin,
			hd.object,
			hd.method,
			ifnull(hi.name,ri.name) as input,
			ifnull(hp.name,rp.name) as output,
			ifnull(hr.name,rr.name) as record,
			ifnull(hf.name,rf.name) as failure
		from
			{$this->table["handler"]} as hd
		inner join
			{$this->table["handler_type"]} as ht on ht.id_type = hd.id_type
		inner join
			{$this->table["route"]} as ru on ru.id_route = hd.id_route and ru.id_route=$id_route
		left join
			{$this->table["options"]} as ho on ho.id_options = hd.id_options
		left join
			{$this->table["options_input"]} as hi on hi.id_input = ho.id_input
		left join
			{$this->table["options_output"]} as hp on hp.id_output = ho.id_output
		left join
			{$this->table["options_record"]} as hr on hr.id_record = ho.id_record
		left join
			{$this->table["options_failure"]} as hf on hf.id_failure = ho.id_failure
		left join
			{$this->table["options"]} as ro on ro.id_options = ru.id_options
		left join
			{$this->table["options_input"]} as ri on ri.id_input = ro.id_input
		left join
			{$this->table["options_output"]} as rp on rp.id_output = ro.id_output
		left join
			{$this->table["options_record"]} as rr on rr.id_record = ro.id_record
		left join
			{$this->table["options_failure"]} as rf on rf.id_failure = ro.id_failure
QUERY;

		$list = Amslib_Array::valid($this->database->select($query));

		//	Allowed handler types
		$handler_types = array("service","terminator_common","terminator_success","terminator_failure");

		foreach($list as &$item){
			//	Grab the default route input and output values, in order to potentially use them when setting up the handlers
			$item["input"]	=	self::sanitise("input",array($item,"input"));
			$item["output"]	=	self::sanitise("output",array($item,"output"));
			$item["record"]	=	self::sanitise("record",array($item,"record"));

			//	Ignore the webservice type is not valid
			if(!in_array($item["type"],$handler_types)){
				$item = NULL;
				continue;
			}

			//	Failure will block unless you tell the system to ignore failure
			//	A reason for wanting to ignore failures is that you want to accumulate all the results
			//	and post-process them into a final result, however this requires you setup the webservices with care
			//	and attention that failures will not cause unpredictable errors, however, that this, it is useful
			if(!isset($item["failure"]) || !in_array($item["failure"],array("break","stop","ignore"))){
				$item["failure"] = "break";
			}
		}

		return array_filter($list);
	}

	protected function processPath()
	{
$query=<<<QUERY
			ru.id_route,
			ru.name,
			rt.name as type,
			pm.data as resource
		from
			{$this->table["route"]} as ru
		inner join
			{$this->table["route_type"]} as rt on rt.id_type = ru.id_type and rt.name="path"
		inner join
			{$this->table["param"]} as pm on pm.id_route = ru.id_route
		inner join
			{$this->table["param_type"]} as pt on pt.id_type = pm.id_type and pt.name="resource"
QUERY;

		$list = Amslib_Array::valid($this->database->select($query));

		foreach($list as &$route){
			$route["src"] = $this->getURLByRouteId($route["id_route"]);
			$route["route_param"] = $this->getParam($route["id_route"],"param");
			$route["javascript"] = $this->getParam($route["id_route"],"javascript");
			$route["stylesheet"] = $this->getParam($route["id_route"],"stylesheet");

			$this->route[] = $route;
		}
	}

	protected function processService()
	{
$query=<<<QUERY
			ru.id_route,
			ru.name,
			rt.name as type,
			ur.url as url,
			ifnull(ip.name,"post") as input,
			ifnull(ot.name,"session") as output,
			ifnull(rd.name,"true") as record,
			ifnull(fl.name,"break") as failure
		from
			{$this->table["route"]} as ru
		inner join
			{$this->table["route_type"]} as rt on rt.id_type = ru.id_type and rt.name="service"
		inner join
			{$this->table["url"]} as ur on ur.id_route = ru.id_route
		left join
			{$this->table["options"]} as op on op.id_options = ru.id_options
		left join
			{$this->table["options_input"]} as ip on ip.id_input = op.id_input
		left join
			{$this->table["options_output"]} as ot on ot.id_output = op.id_output
		left join
			{$this->table["options_record"]} as rd on rd.id_record = op.id_record
		left join
			{$this->table["options_failure"]} as fl on fl.id_failure = op.id_failure
QUERY;

		$list = Amslib_Array::valid($this->database->select($query));

		foreach($list as &$route){
			$route["src"]		=	$this->getURLByRouteId($route["id_route"]);
			$route["handler"]	=	$this->getHandlerByRouteId($route["id_route"]);

			$this->route[] = $route;
		}
	}

	protected function processConfig()
	{
		/*
		Amslib_QueryPath::execCallback("router > callback",			array($this,"configCallback"),	$this);
		Amslib_QueryPath::execCallback("router > import",			array($this,"configImport"),	$this);
		Amslib_QueryPath::execCallback("router > export",			array($this,"configExport"),	$this);
		*/
	}

	protected function sanitise($type,$data)
	{
		if(count($data) != 2) return $data;

		if(in_array($type,array("input","output","record"))){
			$data = array_key_exists($data[1],$data[0]) ? $data[0][$data[1]] : false;
		}

		switch($type){
			case "input":{
				if(!in_array($data,array("get","post"))){
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

		$v = new Amslib_Validator($source);
		$v->add("lang","text",true);
		$v->add("route","text",true);
		$v->add("route_type","text",true);
		$v->add("options","text",true);
		$v->add("options_input","text",true);
		$v->add("options_output","text",true);
		$v->add("options_record","text",true);
		$v->add("options_failure","text",true);
		$v->add("url","text",true);
		$v->add("param","text",true);
		$v->add("param_type","text",true);
		$v->add("handler","text",true);
		$v->add("handler_type","text",true);
		$v->add("database","instanceof",true,array("type"=>"Amslib_Database_MySQL"));

		$s = $v->execute();
		$d = $v->getValid();
		$e = $v->getErrors();

		if($s){
			try{
				$this->database = $d["database"];

				$this->table = $d;
				unset($this->table["database"]);

				$this->processPath();
				$this->processService();
				$this->processConfig();

				$this->valid = true;
			}catch(Exception $e){
				Amslib_Debug::log("Exception: ",$e->getMessage());
				Amslib_Debug::log("stack_trace");
			}
		}
	}

	public function isValid()
	{
		return $this->valid;
	}

	public function configCallback($name,$array,$object)
	{
		/*	The original XML code commented out for reference
		if(!isset($array["value"]) || !strlen($array["value"])) return;

		$callback = is_callable($array["value"]) ? $array["value"] : false;

		if($callback && is_sting($callback) && strlen($callback)){
			Amslib_Router::setCallback($callback);
		}
		*/
	}

	public function configImport($name,$array,$object)
	{
		/*	The original XML code commented out for reference
		$import = $array["attr"];

		if(!isset($import["name"])) $import["name"] = "import_".count($this->import);

		if(array_key_exists("child",$array)){
			foreach($array["child"] as $c){
				$import[$c["tag"]] = $c["value"];
			}
		}

		if(!isset($import["output"]) || !isset($import["url"])) return false;

		if(!empty($import)) $this->import[] = $import;

		return true;
		*/

		return false;
	}

	public function configExport($name,$array,$object)
	{
		/*	The original XML code commented out for reference
		foreach($array["child"] as $c){
			if($c["tag"] == "restrict"){
				Amslib_Router::setExportRestriction($c["value"], false);
			}
		}
		*/

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