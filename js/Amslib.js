var Amslib = my.Amslib = my.Class(
{
	__amslibDefaultName:	"Amslib_Default_Controller",
	__amslibName:			false,
	__options:				{},
	//	we "abuse" jquery dom-data functionality to store groups of data
	__value:				$("<div/>"),
	__services:				$("<div/>"),
	__translation:			$("<div/>"),
	__images:				$("<div/>"),
	//	now we're "abusing" it all over the place, here we use it to store custom events
	__events:				$("<div/>"),
	
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
		
		firebug: function()
		{
			if(console && console.log) console.log.apply(null,arguments);
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
			Amslib.__location = Amslib.getJSPath("/js/Amslib.js").split("/js/Amslib.js")[0];
			
			return Amslib.__location || false;
		},
		
		getQuery: function()
		{
			var p = function(s){
				var e,
		        a = /\+/g,  // Regex for replacing addition symbol with a space
		        r = /([^&=]+)=?([^&]*)/g,
		        d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
		        f = [];
		        
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
		
		loadJS: function(name,file,onReady)
		{
			if(typeof require == 'function'){
				Amslib.loader[name] = require(file);
			
				if(typeof onReady == "function") scope(onReady,Amslib.loader[name]);
				
				scope(function(){
					Amslib.__waitJS(name);
				},Amslib.loader[name]);
			}
		},
		
		hasJS: function(name,callback)
		{
			if(typeof name == "string") name = new Array(name);
			
			var checkGroup = function(){
				var loaded = true;
				
				for(n=0;n<name.length;n++){
					if(!Amslib.__loaderReady[name[n]]) return false;
				}

				if(callback) callback();
				
				return true;
			};
			
			for(var n=0;n<name.length;n++){
				if(Amslib.__loaderReady[name[n]] && checkGroup()) return true;
				else{
					if(!Amslib.__loaderCallback[name[n]]) Amslib.__loaderCallback[name[n]] = new Array();
					Amslib.__loaderCallback[name[n]].push(checkGroup);
				}
			}
			
			return false;
		},
		
		getJSPath: function(search,path)
		{
			var s = $("script[src*='"+search+"']").attr("src");
			
			return s && path ? s.split(search)[0] : s;
		},
		
		loadCSS: function(file)
		{
			$("head").append($("<link/>").attr({rel:"stylesheet",type:"text/css",href: file}));
		},
		
		loader: {},
		__loaderReady:		{},
		__loaderCallback:	{},
		__urlParams:		[],
		__location:			false,
		
		//	Support asynchronous loading a provides a "notification callback" system
		__waitJS: function(name)
		{
			Amslib.__loaderReady[name] = true;
			
			if(Amslib.__loaderCallback[name]){
				for(k in Amslib.__loaderCallback[name]){
					Amslib.__loaderCallback[name][k]();
				}
			}
		}
	},	
	
	constructor: function(parent,name)
	{
		this.parent = $(parent) || false;
		if(!this.parent) return false;
		
		this.__amslibName = name || this.__amslibDefaultName;
		
		//	Setup the amslib_controller to make this object available on the node it was associated with
		this.parent.data(this.__amslibName,this);
		
		this.initValues();
		
		//	Now merge all the specific options from the static parameter "options" into this
		//	NOTE: Hmm....I can't think how to obtain the name of the parent class.....shit....
		//	NOTE: perhaps this is why other plugins don't use a static object?
		//	NOTE: well shit, that means I have to convert all the javascript objects to not using them
		//	NOTE: yes, well, what do you want? a medal?
		//	NOTE: what was the point of all this again? (6/03/2012)
		//this.__options = this.__options.extend()
		
		return this;
	},
	
	getAmslibName: function()
	{
		return __amslibName;
	},
	
	getParentNode: function()
	{
		return this.parent;
	},
	
	initValues: function()
	{
		var mvc	=	this.parent.find(".__amslib_mvc_values");
		var po	=	this;
		
		try{
			var input = mvc.find("input[type='hidden']");
			if(input.length){
				//	interpret input values
				input.each(function(){
					var n = $(this).attr("name");
					var v = $(this).val();
					Amslib.firebug(n,v);
					
					if(n.indexOf("service:") >=0){
						po.setService(n.replace("service:",""),v);
					}else if(n.indexOf("translation:") >=0){
						po.setTranslation(n.replace("translation:",""),v);
					}else if(n.indexOf("image:") >=0){
						po.setImage(n.replace("image:",""),v);
					}else{
						po.setValue(n,v);
					}
				});
			}else{
				//	interpret json values
			}	
		}catch(e){}
	},
	
	bind: function(event,callback,live)
	{
		if(live){
			this.__events.live(event,callback);
		}else{
			this.__events.bind(event,callback)
		}
	},
	
	trigger: function(event,data)
	{
		this.__events.trigger(event,data);
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

$(document).ready(Amslib.autoload);
