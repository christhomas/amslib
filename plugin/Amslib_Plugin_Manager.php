<?php 
class Amslib_Plugin_Manager
{
	static protected $plugins	=	array();
	static protected $api		=	array();
	static protected $location	=	false;
	
	static public function isLoaded($name)
	{
		return isset(self::$plugins[$name]) ? true : false;
	}
	
	static public function setLocation($location)
	{
		self::$location = Amslib_Website::abs($location);
	}
	
	static public function getLocation()
	{
		return self::$location;
	}
	
	static public function add($name)
	{
		//	Location is invalid, so load the default "plugins" location
		if(!self::$location) self::setLocation("plugins");
		
		//	Plugin was already loaded, so return it's API directly
		if(self::isLoaded($name)) return self::getAPI($name);
		
		//	Plugin was not present, so create it, load everything required and return it's API
		$plugin = new Amslib_Plugin();
		$plugin->load($name,self::$location);
		
		self::import($name,$plugin);
		
		return $plugin->getAPI();
	}
	
	static public function import($name,$plugin)
	{
		if($plugin){
			$api = $plugin->getAPI();

			if($api){
				self::$plugins[$name]	=	$plugin;
				self::$api[$name]		=	$api;
			}
		}
	}
	
	static public function remove($name)
	{
		$r = self::$plugins[$name];
		
		unset(self::$plugins[$name],self::$api[$name]);
		
		return $r;
	}
		
	/**
	 * method: setAPI
	 *
	 * A way to provide a custom API object that deals with your application specific
	 * layer and perhaps provides a customised way to deal with the widget in question
	 * and your qpplication.
	 *
	 * parameters:
	 * 	$name	-	The name of the widget being overriden
	 * 	$api	-	An object which is to be used to override the default widget api
	 *
	 * example:
	 *		require_once("CustomApp_Amstudios_Message_Thread_List.php");
	 *		class CustomApp_Amstudios_Message_Thread_List extends Amstudios_Message_Thread_List{}
	 *		$api = CustomApp_Amstudios_Message_Thread_List::getInstance();
	 *		$api->setValue("key","value");
	 *		$widgetManager->overrideAPI("amstudios_message_thread_list",$api);
	 *		$widgetManager->render("amstudios_message_thread_list");
	 */
	static public function setAPI($name,$api)
	{
		if(isset(self::$plugins[$name])){
			self::$plugins[$name]->setAPI($api);
			self::$api[$name] = self::$plugins[$name]->getAPI();
		}
	}
	
	static public function getAPI($name)
	{
		return (isset(self::$api[$name])) ? self::$api[$name] : false;
	}	
	
	/*******************************************************************
	 	HELPER FUNCTIONS
	 	
	 	Below are methods that allow you to plugin functionality 
	 	by just knowing the name of the plugin and the manager 
	 	will find out which appropriate plugin to call to execute 
	 	the functionality
	********************************************************************/
	static public function getView($plugin,$view,$parameters=array())
	{
		$api = self::getAPI($plugin);

		return $api ? $api->getView($view,$parameters) : false;
	}

	static public function setService($plugin,$id,$service)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->setService($id,$service) : false;
	}

	static public function getService($plugin,$service)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->getService($service) : false;
	}

	static public function callService($plugin,$service)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->callService($service) : false;
	}

	static public function setStylesheet($plugin,$id,$file,$conditional=NULL)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->setStylesheet($id,$file,$conditional) : false;
	}

	static public function addStylesheet($plugin,$stylesheet)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->addStylesheet($stylesheet) : false;
	}

	static public function setJavascript($plugin,$id,$file,$conditional=NULL)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->setJavascript($id,$file,$conditional) : false;
	}

	static public function addJavascript($plugin,$javascript)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->addJavascript($javascript) : false;
	}

	static public function render($plugin,$parameters=array())
	{
		$api = self::getAPI($plugin);

		return $api ? $api->render("default",$parameters) : false;
	}
}