<?php
class Amslib_Framework extends Amslib_MVC
{
	public function __construct()
	{
		parent::__construct();
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	/**
	 * method:	render
	 *
	 * This method will render this plugin using the default information,
	 * unless you ignore that and do your own thing
	 *
	 * params:
	 * 	$id		-	the id of the view to render, the "default" view is the first one to load
	 * 	$params	-	an array of variables to make available to the view
	 */
	public function render($id="default",$params=array())
	{
		$resource	=	Amslib_Router::getResource();
		$route		=	Amslib_Router::getName();

		print(__METHOD__.", DEAD".Amslib_Debug::pdump(true,$resource,$route));

		switch($route){
			case "hello_world":{
				//	Do you want to do something cusomised?
				$params["jabba_the_hut"]	= "gimme a kiss beautiful";
				$params["luke_skywalker"]	= "kissed his sister";
				$params["han_solo"]			= "he shot first";
			}
		}

		$params["content"] = $this->renderView($resource,$params);

		return $this->renderView("Skeleton",$params);
	}
}