<?php
class Amslib_Plugin_Config_XML
{
	protected $location;

	protected $packageName;

	protected $queryPath;

	/**
	 * 	method:	toArray
	 *
	 * 	todo: write documentation
	 */
	protected function toArray($node,$recursive=true)
	{
		if(!$node || $node->count() == 0) return false;

		try{
			$data			=	array();
			$data["attr"]	=	$node->attr();
			$data["tag"]	=	$node->tag();
			$childNodes		=	$node->branch()->children();

			//	recurse to decode the child or merely store the child to process later
			foreach($childNodes as $c){
				$data["child"][] = $recursive
					? $this->toArray($c,$recursive)
					: array("tag"=>$c->tag(),"child"=>$c);
			}

			//	If the node doesn't have children, obtain the text and store as it's value
			if(count($childNodes) == 0) $data["value"] = $node->text();
		}catch(Exception $e){
			Amslib::errorLog("QueryPath Exception",$e->getMessage);
		}

		return $data;
	}

	/**
	 * 	method:	__construct
	 *
	 * 	parameters:
	 * 		$package_name - The name of the package name to use when loading configuration files
	 *
	 * 	notes:
	 * 		-	If the $packageName is NULL, "package.xml" will be used as a default
	 */
	public function __construct($packageName="package.xml")
	{
		$this->location		= false;
		$this->packageName	= false;
		$this->queryPath	= false;

		$this->setValue("package",$packageName);
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	public function getStatus()
	{
		return file_exists($this->getValue("filename"));
	}

	public function setValue($key,$value)
	{
		switch($key){
			case "package":{
				$this->packageName = is_string($value) && strlen($value) ? $value : NULL;
			}break;

			case "location":{
				if($this->packageName !== NULL && is_string($value) && strlen($value)){
					$this->location = $value;
				}
			}break;

			default:{
				$value = false;
			}break;
		}

		return $value;
	}

	public function getValue($key)
	{
		switch($key){
			//	We can put specific keys which do something other than get the value of selectors
			//	But of course this stops us from using those keys as XML Nodes in the code
			//	This isn't really a problem, since I can't see them overlapping right now

			case "filename":{
				return Amslib_File::reduceSlashes(
					Amslib_File::absolute("{$this->location}/{$this->packageName}")
				);
			}break;
		}

		return false;
	}

	public function process($key,$callback,$object=NULL)
	{
		if(!$this->queryPath || !is_callable($callback)) return;

		try{
			$results = $this->queryPath->branch()->find($key);
		}catch(Exception $e){
			Amslib::errorLog("QueryPath Exception",$e->getMessage);
		}

		foreach($results as $r){
			$r = $this->toArray($r);

			call_user_func($callback,$r["tag"],$r,$object);
		}
	}

	public function prepare()
	{
		$filename = $this->getValue("filename");

		try{
			$this->queryPath = Amslib_QueryPath::qp($filename);
		}catch(Exception $e){
			Amslib::errorLog("QueryPath Exception",$e->getMessage);
		}
	}
}