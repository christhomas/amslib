<?php
class Amslib_Plugin_Application extends Amslib_Plugin
{
	static protected $version;
	protected $path;
	protected $translator;

	protected function expandTemplates($string)
	{
		$string =	str_replace("__WEBSITE__",	$this->path["website"],	$string);
		$string =	str_replace("__ADMIN__",	$this->path["admin"],	$string);
		$string	=	str_replace("__AMSLIB__",	$this->path["amslib"],	$string);
		$string =	str_replace("__DOCROOT__",	$this->path["docroot"],	$string);

		return str_replace("//","/",$string);
	}

	protected function readValue($query,$default=NULL)
	{
		$node = $this->xpath->query($query);

		return ($node && $node->length) ? $node->item(0)->nodeValue : $default;
	}

	protected function findResource($plugin,$node)
	{
		$node->nodeValue = $this->expandTemplates($node->nodeValue);

		return parent::findResource($plugin,$node);
	}

	protected function setVersion()
	{
		self::$version = array(
			"date"		=>	$this->readValue("//package/version/date"),
			"number"	=>	$this->readValue("//package/version/number"),
			"name"		=>	$this->readValue("//package/version/name"),
		);
	}

	protected function setPaths()
	{
		$node = $this->xpath->query("//package/path");
		if($node->length == 0) return;

		$path = $node->item(0);

		foreach($path->childNodes as $p)
		{
			$name	=	$p->nodeName;
			$value	=	$this->expandTemplates($p->nodeValue);

			//	Ignore this type of node
			if($name[0] == "#") continue;

			if($name == "include"){
				Amslib::addIncludePath(Amslib_Filesystem::absolute($value));
			}else{
				$this->path[$name] = $value;

				if($name == "plugin"){
					Amslib_Plugin_Manager::addLocation(Amslib_Filesystem::absolute($this->path["plugin"]));
				}
			}
		}


		//	Die with an error, all of these parameters must be a valid string
		//	or the whole system doesnt work
		//	NOTE:	Is this true? will the whole system fail without this info?
		//	NOTE:	Perhaps I should gracefully fail with a nice error instead of dying horribly
		//	NOTE:	I think if the system is the admin yes, but if it is the normal website no
		if(	strlen($this->path["website"] != "__WEBSITE__") == 0 ||
			strlen($this->path["admin"] != "__ADMIN__") == 0 ||
			strlen($this->path["plugin"] != "__PLUGIN__") == 0){
				print("<pre>");
				var_dump($this->path);
				print("</pre>");
			}
	}

	protected function loadTranslators()
	{
		//	TODO:	how to configure the language xml section to obtain information
		//			relevant to the requirements
	}

	protected function getTranslator($name)
	{
		//	TODO:	how to return the translators based on requirements
	}

	protected function initialisePlugin()
	{
		//	Set the version of the admin this panel is running
		$this->setVersion();
		//	Set all the important paths for the admin to run
		$this->setPaths();
		//	Load the content translators, which will provide all the page text translations
		$this->loadTranslators();
		//	Load the router (need to initialise the router first, but execute it after everything is loaded from the plugins)
		$this->initRouter();

		return true;
	}

	protected function finalisePlugin()
	{
		//	Load all the customised configuration into the plugins
		$this->configurePlugins();

		//	Load the required router library and execute it to setup everything it needs
		$this->executeRouter();

		return true;
	}

	protected function initRouter()
	{
		//	TODO: need to get rid of the need to do this constructor
		//	TODO: Why are we hardcoding the type of source to XML? what if we chose a DB source instead?
		$r = Amslib_Router3::getInstance();
		Amslib_Router3::setSource(Amslib_Router3::getObject("source:xml"));
	}

	//	NOTE:	It might be worth noting that in the future, this functionality might be useless
	//			because we don't load any routes from the amslib_router.xml in the administration panel
	//			any way, so this functionality is practically worthless.
	protected function executeRouter()
	{
		$source = $this->readValue("//package/router_source");
		$source = $this->expandTemplates($source);

		//	Initialise and execute the router
		//	TODO: As noted in initRouter, why are we hardcoding an XML source?
		$xml = Amslib_Router3::getObject("source:xml");
		$xml->load($source);

		Amslib_Router3::setSource($xml);
		Amslib_Router3::execute();
	}

	protected function configurePlugins()
	{
		$config = $this->xpath->query("//package/plugin");

		foreach($config as $plugin){
			$name = $plugin->getAttribute("name");

			if($name){
				$api = Amslib_Plugin_Manager::getAPI($name);

				if($api){
					foreach($plugin->childNodes as $item){
						$api->setValue($item->nodeName,$item->nodeValue);
					}
				}
			}
		}
	}

	public function __construct($name,$location)
	{
		parent::__construct();

		$this->path = array(
			"amslib"	=>	Amslib::locate(),
			"website"	=>	"__WEBSITE__",
			"admin"		=>	"__ADMIN__",
			"plugin"	=>	"__PLUGIN__",
			"docroot"	=>	Amslib_Filesystem::documentRoot()
		);

		$api = $this->load($name,$location);
		Amslib_Plugin_Manager::import($name,$this);
	}

	public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	public function setModel($model)
	{
		Admin_Panel_Model::setConnection($model);

		parent::setModel($model);
	}

	static public function getVersion($element=NULL)
	{
		return (!isset(self::$version[$element])) ? self::$version : self::$version[$element];
	}

	public function getAdminTranslator()
	{
		return $this->translator["admin"];
	}

	public function getContentTranslator()
	{
		return $this->translator["content"];
	}

	//	NOTE:	This method looks like it's out of date and needs
	//			to be revamped with a new way to obtain this info
	//			because it looks a little bit hacky
	public function getPageTitle()
	{
		$api = Amslib_Plugin_Manager::getAPI(self::getActivePlugin());

		$title = false;

		if($api)	$title	=	$api->getValue("page_title");
		if(!$title)	$title	=	"MISSING PAGE TITLE";

		return $title;
	}

	//	NOTE: I don't like this method, I should find a nicer way to do this
	static public function getActivePlugin()
	{
		static $activePlugin = NULL;

		if($activePlugin === NULL){
			$routeName		=	Amslib_Router3::getName();
			$activePlugin	=	Amslib_Plugin_Manager::getPluginNameByRouteName($routeName);
		}

		return $activePlugin;
	}
}