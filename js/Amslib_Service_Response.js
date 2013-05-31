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

	constructor: function(json){
		this.setJSON(json);
	},
	
	setJSON: function(json){
		this.json = json;
	},
	
	getJSON: function()
	{
		return this.json;
	},
	
	hasSuccess: function(){
		return this.json.success ? true : false;
	},

	hasPlugin: function(plugin){
		return this.readData(plugin) != undefined ? true : false;
	},
	
	handlerCount: function()
	{
		try{
			return this.json["handlers"].length;
		}catch(e){}
		
		return 0;
	},

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

	getServiceData: function(plugin,name,handlerindex){	
		return this.readData(plugin,"service/data",name,handlerindex);		
	},
	
	getServiceErrors: function(plugin,name,handlerindex){	
		return this.readData(plugin,"service/errors",name,handlerindex);		
	},
	
	getValidationData: function(plugin,name,handlerindex){	
		return this.readData(plugin,"validation/errors",name,handlerindex);	
	},
	
	getValidationErrors: function(plugin,name,handlerindex){	
		return this.readData(plugin,"validation/data",name,handlerindex);	
	}
	//	TODO: getDatabaseErrors
});