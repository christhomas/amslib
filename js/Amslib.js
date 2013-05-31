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
 * 	class:	Amslib
 * 
 *	group:	javascript
 * 
 *	file:	Amslib.js
 * 
 *	title:	Antimatter Studios Core Amslib Javascript
 * 
 *	description:
 *		todo, write description 
 *
 * 	todo:
 * 		write documentation
 */
var Amslib = my.Amslib = my.Class({
	__amslibDefaultName:	"Amslib_Default_Controller",
	__amslibName:			false,
	//	we "abuse" jquery dom-data functionality to store groups of data
	__value:				false,
	__services:				false,
	__translation:			false,
	__images:				false,
	//	now we're "abusing" it all over the place, here we use it to store custom events
	__events:				false,
	
	STATIC: {
		/**
		 * 	method:	autoload
		 *
		 * 	todo: write documentation
		 */
		autoload: function()
		{
			// usage: log('inside coolFunc', this, arguments);
			// paulirish.com/2009/log-a-lightweight-wrapper-for-consolelog/
			window.log = function(){
				log.history = log.history || [];   // store logs to an array for reference
				log.history.push(arguments);
				if(this.console){
					arguments.callee = arguments.callee.caller;
					var newarr = [].slice.call(arguments);
					(typeof console.log === 'object' ? log.apply.call(console.log, console, newarr) : console.log.apply(console, newarr));
				}
			};
			
			// make it safe to use console.log always
			(function(b){function c(){}for(var d="assert,count,debug,dir,dirxml,error,exception,group,groupCollapsed,groupEnd,info,log,timeStamp,profile,profileEnd,time,timeEnd,trace,warn".split(","),a;a=d.pop();){b[a]=b[a]||c}})((function(){try
			{console.log();return window.console;}catch(err){return window.console={};}})());			 	
		},
		
		/**
		 * 	method:	getController
		 *
		 * 	todo: write documentation
		 */
		getController: function(amslib_object)
		{
			if(typeof(amslib_object.instances) != "undefined" && amslib_object.instances != false){
				return amslib_object.instances.data(amslib_object.options.amslibName);
			}
			
			return false;
		},
		
		/**
		 * 	method:	firebug
		 *
		 * 	todo: write documentation
		 */
		firebug: function()
		{
			//	NOTE: This is suspiciously similar to paulirishes window.log method shown above in the autoload method
			//	NOTE: apparently some people found this function would cause an error in google chrome, dunno why.
			if(console && console.log) console.log.apply(console,arguments);
		},
		
		//	DEPRECATED getPath, use Amslib.locate() instead, it does exactly what I was supposed to do here
		/**
		 * 	method:	getPath
		 *
		 * 	todo: write documentation
		 */
		getPath: function(file)
		{
			//	Copied from how scriptaculous does it's "query string" thing
			var re 		=	new RegExp("^(.*?)"+file.replace(/[-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")+"$","g");
			var path	=	false;
			
			$("script[src]").each(function(){
				var matches = re.exec(this.src);
				
				if(matches){
					path = matches[1]; 
					return false;
				}
			});
			
			if(!path) Amslib.firebug("requested path["+file+"] using regexp["+re+"] was not found");
				
			return path;
		},
		
		/**
		 * 	method:	locate
		 *
		 * 	todo: write documentation
		 */
		locate: function()
		{
			Amslib.__location = Amslib.getJSPath("/js/Amslib.js").split("/js/Amslib.js")[0];
			
			return Amslib.__location || false;
		},

		/**
		 * 	method:	getQuery
		 *
		 * 	todo: write documentation
		 */
		getQuery: function()
		{
			var p = function(s){
				var e,
		        a = /\+/g,  // Regex for replacing addition symbol with a space
		        r = /([^&=]+)=?([^&]*)/g,
		        d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
		        f = {};
		        
		        if(i=s.indexOf("?")) s = s.substring(i+1);
		        
		        while(e = r.exec(s)){
		        	var k = d(e[1]), v = d(e[2]);
		        	
		        	//	This works with arrays of url params[]
		        	if(k.indexOf("[]")>=0){
		        		if(!f[k]) f[k] = new Array();
		        		f[k].push(v);
		        	}else{
		        		f[k] = v;
		        	}
		        }
		        
		        return f;
			};
			
			if(arguments.length == 1) return p(arguments[0]);
			if(arguments.length >= 2) return (f=p(arguments[1])) && (arguments[0] in f) ? f[arguments[0]] : f;
			
			return false;
		},
		
		////////////////////////////////////////////////////////////////////
		//	The load/hasJS API
		//
		//	This API will allow you to quickly and simply load a javascript
		//	and apply a function that will wait until it's loaded to execute
		//
		//	NOTE: refactor this against the wait API, it's cleaner
		////////////////////////////////////////////////////////////////////
		/**
		 * 	method:	loadJS
		 *
		 * 	todo: write documentation
		 */
		loadJS: function(name,file,onReady)
		{
			if(typeof(require) != 'function') return;
			
			if(typeof(Amslib.loader[name]) == "undefined"){
				Amslib.__lready[name]	=	false;
				Amslib.loader[name]		=	require(file);
			}
		
			if(typeof onReady == "function") scope(onReady,Amslib.loader[name]);
			
			scope(function(){
				Amslib.__lready[name] = true;
				
				if(Amslib.__lcback[name]) for(k in Amslib.__lcback[name]){
					Amslib.__lcback[name][k]();
				}
			},Amslib.loader[name]);
		},
		
		/**
		 * 	method:	hasJS
		 *
		 * 	todo: write documentation
		 */
		hasJS: function(name,callback)
		{
			if(typeof name == "string") name = new Array(name);
			
			var checkGroup = function(){
				for(n=0;n<name.length;n++){
					if(!Amslib.__lready[name[n]]) return false;
				}

				if(callback) $(document).ready(callback);
				
				return true;
			};
			
			for(var n=0;n<name.length;n++){
				try{
					if(Amslib.__lready[name[n]] && checkGroup()) return true;
				}catch(e){}
				
				if(typeof(Amslib.__lcback[name[n]]) == "undefined") Amslib.__lcback[name[n]] = new Array();
				Amslib.__lcback[name[n]].push(checkGroup);
			}
			
			return false;
		},
		
		/**
		 * 	method:	getJSPath
		 *
		 * 	todo: write documentation
		 */
		getJSPath: function(search,path)
		{
			var s = $("script[src*='"+search+"']").attr("src");
			
			return s && path ? s.split(search)[0] : s;
		},
		
		/**
		 * 	method:	loadCSS
		 *
		 * 	todo: write documentation
		 */
		loadCSS: function(file)
		{
			$("head").append($("<link/>").attr({rel:"stylesheet",type:"text/css",href: file}));
		},
		
		loader:			{},
		__lready:		{},
		__lcback:		{},
		__urlParams:	[],
		__location:		false,
		
		////////////////////////////////////////////////////////////////////
		//	The wait object API
		//
		//	This object will allow you to set a bunch of string keys and 
		//	it will create deferred object for each key, when you resolve 
		//	each key, depending on the combinations requested, it'll trigger
		//	the done or fail callbacks, allowing you to easily synchronise 
		//	asychronous actions between objects which are loaded and 
		//	created dynamically at different times
		////////////////////////////////////////////////////////////////////
		waitObject:  false,
		
		/**
		 * 	method:	waitUntil
		 *
		 * 	todo: write documentation
		 */
		waitUntil:  function()
		{
			if(Amslib.waitObject == false) Amslib.waitObject = {};
			
			var deferred = [], done = false, fail = false;
			
			for(var a=0,len=arguments.length;a<len;a++){
				var arg = arguments[a];
				var type = typeof(arg);
				
				if(type == "string"){
					if(typeof(Amslib.waitObject[arg]) == "object"){
						var d = Amslib.waitObject[arg];
					}else{
						var d = $.Deferred();
						
						Amslib.waitObject[arg] = d;
					}
					
					deferred.push(d);
				}else if(type == "function"){
					if(!done) done = arg;
					if(!fail) fail = arg;
				}
			}
			
			var w = $.when.apply($,deferred);

			if(done) w.done(done);
			if(fail) w.fail(fail);

			return w;
		},
		
		/**
		 * 	method:	waitResolve
		 *
		 * 	todo: write documentation
		 */
		waitResolve:  function(name)
		{
			if(typeof(Amslib.waitObject[name]) == "object"){
				Amslib.waitObject[name].resolve();
			}
		}
	},	
	
	/**
	 * 	method:	constructor
	 *
	 * 	todo: write documentation
	 */
	constructor: function(parent,name)
	{
		this.parent = $(parent) || false;
		if(!this.parent) return false;
		
		this.__amslibName = name || this.__amslibDefaultName;
		
		//	Setup the amslib_controller to make this object available on the node it was associated with
		this.parent.data(this.__amslibName,this);
		
		this.__value		= $("<div/>");
		this.__services		= $("<div/>");
		this.__translation	= $("<div/>");
		this.__images		= $("<div/>");
		this.__events		= $("<div/>");
		
		this.readMVC();

		return this;
	},
	
	/**
	 * 	method:	readMVC
	 *
	 * 	todo: write documentation
	 */
	readMVC: function()
	{
		try{
			var mvc	=	this.parent.find(".__amslib_mvc_values");

			var input	=	mvc.find("input[type='hidden']");
			var data	=	{};
			
			if(input.length){
				//	interpret input values
				input.each(function(){
					data[$(this).attr("name")] = $(this).val();
				});
			}else{
				data = $.parseJSON(mvc.text());
			}
			
			for(k in data){
				if(k.indexOf("service:") >=0){
					this.setService(k.replace("service:",""),data[k]);
				}else if(k.indexOf("translation:") >=0){
					this.setTranslation(k.replace("translation:",""),data[k]);
				}else if(k.indexOf("image:") >=0){
					this.setImage(k.replace("image:",""),data[k]);
				}else{
					this.setValue(k,data[k]);
				}
			}	
		}catch(e){
			console.log("Exception caused whilst reading Amslib.readMVC");
			console.log(e);
		}
	},
	
	/**
	 * 	method:	getAmslibName
	 *
	 * 	todo: write documentation
	 */
	getAmslibName: function()
	{
		return this.__amslibName;
	},
	
	/**
	 * 	method:	getParentNode
	 *
	 * 	todo: write documentation
	 */
	getParentNode: function()
	{
		return this.parent;
	},
	
	/**
	 * 	method:	bind
	 *
	 * 	todo: write documentation
	 */
	bind: function(event,callback,live)
	{
		this.__events.bind(event,callback);
	},
	
	/**
	 * 	method:	on
	 *
	 * 	todo: write documentation
	 */
	on: function(event,callback)
	{
		this.__events.on(event,callback);
	},
	
	/**
	 * 	method:	live
	 *
	 * 	todo: write documentation
	 */
	live: function(event,callback)
	{
		this.on(event,callback);
	},
	
	/**
	 * 	method:	trigger
	 *
	 * 	todo: write documentation
	 */
	trigger: function(event,data)
	{
		this.__events.trigger(event,[data]);
	},
	
	//	Getter/Setter for the object values
	/**
	 * 	method:	setValue
	 *
	 * 	todo: write documentation
	 */
	setValue: function(name,value){			
		this.__value.data(name,value);			
	},
	
	/**
	 * 	method:	getValue
	 *
	 * 	todo: write documentation
	 */
	getValue: function(name)
	{				
		return this.__value.data(name);			
	},
	
	//	Getter/Setter for web services
	/**
	 * 	method:	setService
	 *
	 * 	todo: write documentation
	 */
	setService: function(name,value)
	{		
		this.__services.data(name,value);		
	},
	
	/**
	 * 	method:	getService
	 *
	 * 	todo: write documentation
	 */
	getService: function(name)
	{				
		return this.__services.data(name);		
	},
	
	//	Getter/Setter for text translations
	/**
	 * 	method:	setTranslation
	 *
	 * 	todo: write documentation
	 */
	setTranslation: function(name,value)
	{	
		this.__translation.data(name,value);	
	},
	
	/**
	 * 	method:	getTranslation
	 *
	 * 	todo: write documentation
	 */
	getTranslation: function(name)
	{			
		return this.__translation.data(name);	
	},
	
	//	Getter/Setter for images
	/**
	 * 	method:	setImage
	 *
	 * 	todo: write documentation
	 */
	setImage: function(name,value)
	{			
		this.__images.data(name,value);			
	},
	
	/**
	 * 	method:	getImage
	 *
	 * 	todo: write documentation
	 */
	getImage: function(name)
	{				
		return this.__images.data(name);		
	}
});

$(document).ready(Amslib.autoload);