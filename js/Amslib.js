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
 * file: Amslib.js
 * title: Antimatter core javascript
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/
if(typeof(my) == "undefined" || typeof(my.Class) == "undefined"){
	 throw("Amslib.js: requires my.[class/common] to be loaded first");
}

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
		
		getController: function(amslib_object)
		{
			if(typeof(amslib_object.instances) != "undefined" && amslib_object.instances != false){
				return amslib_object.instances.data(amslib_object.options.amslibName);
			}
			
			return false;
		},
		
		firebug: function()
		{
			//	NOTE: This is suspiciously similar to paulirishes window.log method shown above in the autoload method
			//	NOTE: apparently some people found this function would cause an error in google chrome, dunno why.
			if(console && console.log) console.log.apply(console,arguments);
		},
		
		//	DEPRECATED getPath, use Amslib.locate() instead, it does exactly what I was supposed to do here
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
		
		locate: function()
		{
			Amslib.__location = Amslib.js.find("/js/Amslib.js",true);
			
			return Amslib.__location || false;
		},

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
		
		//	DEPRECATED API, use Amslib.[js,css].* instead
		loadJS: function(name,file,callback){	Amslib.js.load(name,file,callback);	},
		hasJS: function(name,callback){			Amslib.js.has(name,callback);		},
		getJSPath: function(search,path){		return Amslib.js.find(search,path);	},
		loadCSS: function(file){				Amslib.css.load(file);				},
		
		__urlParams:	[],
		__location:		false
	},	
	
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
	
	readMVC: function()
	{
		try{
			var mvc	=	this.parent.find(".__amslib_mvc_values");
			
			if(mvc.length == 0) return;

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
	
	getAmslibName: function()
	{
		return this.__amslibName;
	},
	
	getParentNode: function()
	{
		return this.parent;
	},
	
	bind: function(event,callback,live)
	{
		this.__events.bind(event,callback);
	},
	
	on: function(event,callback)
	{
		this.__events.on(event,callback);
	},
	
	live: function(event,callback)
	{
		this.on(event,callback);
	},
	
	trigger: function(event,data)
	{
		this.__events.trigger(event,[data]);
	},
	
	//	Getter/Setter for the object values
	setValue: function(name,value){			this.__value.data(name,value);			},
	getValue: function(name){				return this.__value.data(name);			},
	
	//	Getter/Setter for web services
	setService: function(name,value){		this.__services.data(name,value);		},
	getService: function(name){				return this.__services.data(name);		},
	
	//	Getter/Setter for text translations
	setTranslation: function(name,value){	this.__translation.data(name,value);	},
	getTranslation: function(name){			return this.__translation.data(name);	},
	
	//	Getter/Setter for images
	setImage: function(name,value){			this.__images.data(name,value);			},
	getImage: function(name){				return this.__images.data(name);		}
});

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
Amslib.wait = {
	__handles: false,

	__init: function()
	{
		if(Amslib.wait.__handles == false){
			Amslib.wait.__handles = {};
		}
	},

	until: function()
	{
		Amslib.wait.__init();
		
		var deferred = [];
		var done = false; 
		var fail = false;
		
		for(var a=0,len=arguments.length;a<len;a++){
			var arg = arguments[a];
			var type = typeof(arg);
			
			if(type == "string"){
				if(typeof(Amslib.wait.__handles[arg]) == "object"){
					var d = Amslib.wait.__handles[arg];
				}else{
					var d = $.Deferred();
					
					if(typeof(Amslib.wait.__handles[arg]) == "string" && arg == Amslib.wait.__handles[arg]){
						d.resolve();
					}
					
					Amslib.wait.__handles[arg] = d;
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
		
		if(!done && !fail){
			console.log("ERROR[Amslib.wait.until]: both done and fail callbacks were not set, so what do we execute when this trigger executes?",arguments);
		}
	
		return w;
	},
	
	resolve: function(name)
	{
		Amslib.wait.__init();
		
		if(typeof(Amslib.wait.__handles[name]) == "object"){
			Amslib.wait.__handles[name].resolve();
		}else{
			Amslib.wait.__handles[name] = name;
		}
	}
};

////////////////////////////////////////////////////////////////////
//	The JS API
//
//	This API will allow you to quickly and simply load a javascript
//	and apply a function that will wait until it's loaded to execute
//
//	NOTE: refactor this against the wait API, it's cleaner
//	NOTE: if I can refactor it, I'm having second thoughts
////////////////////////////////////////////////////////////////////
Amslib.js = {
	__loadHandles: false,
	
	__init: function(){
		if(Amslib.js.__loadHandles == false){
			Amslib.js.__loadHandles = {};
		}
	},
	
	load: function(name,filename,callback){
		var self = Amslib.js;
			
		self.__init();
		
		if(typeof(require) != 'function'){
			console.log("ERROR[Amslib.js.load]: We cannot proceed because the 'require' function is not available, did you load my.common into your website?");
			return;
		}
		
		if(typeof(self.__loadHandles[name]) == "undefined"){
			self.__loadHandles[name] = require(filename);
		}
		
		scope(function(){
			Amslib.wait.resolve(name);
			
			if(typeof(callback) == "function") callback();
		},self.__loadHandles[name]);
	},
	
	loadSeq: function()
	{
		if(arguments.length == 0) return;
		
		var prev = arguments[0];
		if(prev.length == 2) prev[2] = false;
		Amslib.js.load(prev[0],prev[1],prev[2]);

		for(var a=0;a<arguments.length;a++){
			var item = arguments[a];

			Amslib.js.has(prev[0],function(){
				if(item.length == 2) item[2] = false;
				Amslib.js.load(item[0],item[1],item[2]);
			});
			
			prev = item;
		}
	},
	
	//	a variable number of names and possible two callbacks for success and failure
	//	if you pass has(a,b,c,d,function(){}) it'll treat a,b,c,d like names to succeed together and run the function as a success callback
	has: function(name,callback)
	{
		return Amslib.wait.until.apply(null,arguments);
	},
	
	find: function(search,path)
	{
		var s = $("script[src*='"+search+"']").attr("src");
		
		return s && path ? s.split(search)[0] : s;
	}
};

Amslib.css = {
	load: function(file)
	{
		$("head").append($("<link/>").attr({rel:"stylesheet",type:"text/css",href: file}));
	}		
};

////////////////////////////////////////////////////////////////////
//	FUTURE: an mvc api, but this needs to extend Amslib, not be static
////////////////////////////////////////////////////////////////////
Amslib.mvc = {
	
};

$(document).ready(Amslib.autoload);