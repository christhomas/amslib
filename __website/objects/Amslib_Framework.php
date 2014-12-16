<?php
class Amslib_Framework extends Amslib_MVC
{
	public function __construct()
	{
		parent::__construct();
	}

	static public function getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	public function getImage($id,$relative=true)
	{
		if(is_string($id)){
			$id = "/__website/$id";
		}

		return parent::getImage($id,$relative);
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
		$params["resource"]	=	Amslib_Router::getResource();
		$params["body"]		=	strtolower($params["resource"]);
		$params["route"]	=	Amslib_Router::getName();

		$params["site_title"] = false;
		if(!$params["site_title"]){
			$params["site_title"] = $this->getRouteParam("site_title",false);
		}
		if(!$params["site_title"]){
			$params["site_title"] = $this->getValue("site_title",false);
		}
		if(!$params["site_title"]){
			$params["site_title"] = "Welcome to the website";
		}

		$params["url_home"] = $this->getURL("home");

		$params["meta_description"]	=	$this->getValue("meta_description");
		$params["meta_author"]		=	$this->getValue("meta_author");

		$params["logo"] = $this->getFile("/resources/logo.png");

		$params["url_about"]			=	$this->getURL("about-framework");
		$params["url_gettingstarted"]	=	$this->getURL("getting-started");
		$params["url_plugins"]			=	$this->getURL("plugins");
		$params["url_webservices"]		=	$this->getURL("webservices-api");
		$params["url_documentation"]	=	$this->getURL("documentation");
		$params["url_testframework"]	=	$this->getURL("test-framework");

		$params["content"] = $this->renderView($params["resource"],$params);

		return $this->renderView("Skeleton",$params);
	}
}