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
 * file: Amslib_Plugin_Manager.php
 * title: Antimatter Plugin: Plugin Manager object
 * description: An object to store all the plugins and provide a central method
 * 				to access them all
 * version: 1.3
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Plugin_Manager
{
	static protected $plugins	=	array();
	static protected $api		=	array();
	static protected $location	=	array();

	static protected function findPlugin($name,$location=NULL)
	{
		$search = array_merge(array($location),self::$location);

		foreach($search as $location)
		{
			if(file_exists("$location/$name/package.xml")){
				//	double check that the location starts and ends with a slash
				//	something this isn't the case and the programmer forgets
				//	then the plugin doesnt load, all because of a simple missing slash
				return Amslib_Filesystem::reduceSlashes("/$location/");
			}
		}

		return false;
	}

	static public function isLoaded($name)
	{
		return isset(self::$plugins[$name]) ? true : false;
	}

	static public function addLocation($location)
	{
		self::$location[] = Amslib_Website::abs($location);
	}

	static public function getLocation()
	{
		return self::$location;
	}

	static public function add($name,$location=NULL)
	{
		$location = self::findPlugin($name,$location);

		//	Protect against missing plugins
		if(!$location) return false;

		//	Plugin was already loaded, so return it's API directly
		if(self::isLoaded($name)) return self::getAPI($name);

		//	Plugin was not present, so create it, load everything required and return it's API
		$plugin = new Amslib_Plugin();
		$plugin->load($name,$location.$name);

		self::import($name,$plugin);

		return $plugin->getAPI();
	}

	static public function import($name,$plugin)
	{
		if($name && $plugin){
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
	 * 		NOTE: This example is out of date and needs rewriting
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
	
	static public function getPlugin($name)
	{
		return isset(self::$plugins[$name]) ? self::$plugins[$name] : false;
	}

	static public function getPluginNameByRouteName($routeName)
	{
		foreach(self::$api as $pluginName=>$api)
		{
			if($api->hasRoute($routeName)) return $pluginName;
		}

		return false;
	}
	
	static public function listPlugins()
	{
		return array_keys(self::$plugins);
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
	
	static public function getServiceURL($plugin,$service)
	{
		$api = self::getAPI($plugin);

		return $api ? $api->getServiceURL($service) : false;
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

	static public function render($plugin,$layout="default",$parameters=array())
	{
		$api = self::getAPI($plugin);
		
		return $api ? $api->render($layout,$parameters) : false;
	}
}