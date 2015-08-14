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
 * 	class:	Amslib_Webservice
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Webservice
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
var Amslib_Webservice = my.Amslib_Webservice = my.Class(
{
	promise:	false,
	url:		false,
	cbSuccess:	false,
	cbFailure:	false,
	data:		false,
	
	constructor: function(){
		this.data = {};
		
		this.setJSON(false);
	},
	
	post: function(url,data,cors)
	{
		this.url = url;
		
		var config = {
			type:		"POST",
			url:		this.url,
			data:		data,
			dataType:	"json"
		};
		
		if(cors){
			config.xhrFields = {
				withCredentials: true
			};
		}
		
		this.promise	=	$.ajax(config);
		this.cbSuccess	=	[];
		this.cbFailure	=	[];
		
		return this;
	},
	
	success: function(handler)
	{
		var _this = this;
		
		if(typeof(handler) == "function"){
			this.cbSuccess.push(handler);
			
			this.promise.success(function(json){
				_this.setJSON(json);
				
				!_this.hasSuccess()
					? _this.executeFailure(json)
					: _this.executeSuccess(json);
			});
		}
		
		return this;
	},
	
	failure: function(handler)
	{
		var _this = this;
		
		if(typeof(handler) == "function"){
			this.cbFailure.push(handler);
			
			this.promise.error(function(json){
				_this.setJSON(json);
				_this.executeFailure(json);
			});
		}
		
		return this;
	},
	
	always: function(handler)
	{
		this.promise.always(handler);
		
		return this;
	},
	
	executeSuccess: function(json)
	{
		if(this.cbSuccess) for(i in this.cbSuccess){
			this.cbSuccess[i](this,json);
		}
	},
	
	executeFailure: function(json)
	{
		if(this.cbFailure) for(i in this.cbFailure){
			this.cbFailure[i](this,json);
		}
	},
	
	getURL: function()
	{
		return this.url;
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
	 * 	method:	hasSuccess
	 *
	 * 	todo: write documentation
	 */
	getSuccessURL: function()
	{
		return this.json.url_success || false;
	},
	
	/**
	 * 	method:	hasSuccess
	 *
	 * 	todo: write documentation
	 */
	getFailureURL: function()
	{
		return this.json.url_failure || false;
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
		return this.readData(plugin,"service.data",name,handlerindex);		
	},
	
	/**
	 * 	method:	getServiceErrors
	 *
	 * 	todo: write documentation
	 */
	getServiceErrors: function(plugin,name,handlerindex)
	{	
		return this.readData(plugin,"service.errors",name,handlerindex);		
	},
	
	/**
	 * 	method:	getValidationData
	 *
	 * 	todo: write documentation
	 */
	getValidationData: function(plugin,name,handlerindex)
	{	
		return this.readData(plugin,"validation.errors",name,handlerindex);	
	},
	
	/**
	 * 	method:	getValidationErrors
	 *
	 * 	todo: write documentation
	 */
	getValidationErrors: function(plugin,name,handlerindex)
	{	
		return this.readData(plugin,"validation.data",name,handlerindex);	
	},
	
	//	TODO: getDatabaseErrors
	
	set: function(name,value)
	{
		this.data[name] = value;
		
		return this;
	},
	
	get: function(name,defaultValue)
	{
		return this.data[name] || defaultValue;
	}
});

wait.resolve("Amslib_Webservice",Amslib_Webservice);