var Amslib = my.Amslib = my.Class(
{
	__amslibDefaultName:	"Amslib_Default_Controller",
	__amslibName:			false,
	__options:				{},
	
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
			Amslib.__location = $("script[src*='/js/Amslib.js']").attr("src").split("/js/Amslib.js")[0];
			
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
		
		getJSPath: function(search)
		{
			return $("script[src*='"+search+"']").attr("src");
		}
		
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
		
		//	Now merge all the specific options from the static parameter "options" into this
		//	NOTE: Hmm....I can't think how to obtain the name of the parent class.....shit....
		//	NOTE: perhaps this is why other plugins don't use a static object?
		//	NOTE: well shit, that means I have to convert all the javascript objects to not using them
		//	NOTE: yes, well, what do you want? a medal?
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
	}
});

$(document).ready(Amslib.autoload);
