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
 * 	class:	Amslib_Service_Response
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Service_Response.js
 * 
 *	title:	todo, give title
 * 
 *	description:
 *		todo, write description 
 *
 * 	todo:
 * 		write documentation
 * 
 */
var Amslib_Service_Response = my.Amslib_Service_Response = my.Class(
{
	json: false,

	/**
	 * 	method:	constructor
	 *
	 * 	todo: write documentation
	 */
	constructor: function(json)
	{
		this.setJSON(json);
	},
	
	/**
	 * 	method:	setJSON
	 *
	 * 	todo: write documentation
	 */
	setJSON: function(json)
	{
		this.json = json;
	},
	
	/**
	 * 	method:	getJSON
	 *
	 * 	todo: write documentation
	 */
	getJSON: function()
	{
		return this.json;
	},
	
	/**
	 * 	method:	hasSuccess
	 *
	 * 	todo: write documentation
	 */
	hasSuccess: function()
	{
		return this.json.success ? true : false;
	},

	/**
	 * 	method:	hasPlugin
	 *
	 * 	todo: write documentation
	 */
	hasPlugin: function(plugin)
	{
		return this.readData(plugin) != undefined ? true : false;
	},
	
	/**
	 * 	method:	handlerCount
	 *
	 * 	todo: write documentation
	 */
	handlerCount: function()
	{
		try{
			return this.json["handlers"].length;
		}catch(e){}
		
		return 0;
	},

	/**
	 * 	method:	readData
	 *
	 * 	todo: write documentation
	 */
	readData: function(plugin,group,name,handlerindex)
	{
		if(handlerindex === undefined || handlerindex === null) handlerindex = 0;
		
		try{
			var data = this.json["handlers"][handlerindex][plugin];
			if(group != undefined || group != null)	data = data[group];
			if(name != undefined || name != null)	data = data[name];
			return data;
		}catch(e){}

		return undefined;
	},

	/**
	 * 	method:	getServiceData
	 *
	 * 	todo: write documentation
	 */
	getServiceData: function(plugin,name,handlerindex)
	{	
		return this.readData(plugin,"service/data",name,handlerindex);		
	},
	
	/**
	 * 	method:	getServiceErrors
	 *
	 * 	todo: write documentation
	 */
	getServiceErrors: function(plugin,name,handlerindex)
	{	
		return this.readData(plugin,"service/errors",name,handlerindex);		
	},
	
	/**
	 * 	method:	getValidationData
	 *
	 * 	todo: write documentation
	 */
	getValidationData: function(plugin,name,handlerindex)
	{	
		return this.readData(plugin,"validation/errors",name,handlerindex);	
	},
	
	/**
	 * 	method:	getValidationErrors
	 *
	 * 	todo: write documentation
	 */
	getValidationErrors: function(plugin,name,handlerindex)
	{	
		return this.readData(plugin,"validation/data",name,handlerindex);	
	}
	//	TODO: getDatabaseErrors
});