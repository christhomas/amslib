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
		
		firebug: function()
		{
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
		
		getJSPath: function(search,path)
		{
			var s = $("script[src*='"+search+"']").attr("src");
			
			return s && path ? s.split(search)[0] : s;
		},
		
		loadCSS: function(file)
		{
			$("head").append($("<link/>").attr({rel:"stylesheet",type:"text/css",href: file}));
		},
		
		loader:			{},
		__lready:		{},
		__lcback:		{},
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
		
		this.initValues();

		return this;
	},
	
	getAmslibName: function()
	{
		return this.__amslibName;
	},
	
	getParentNode: function()
	{
		return this.parent;
	},
	
	initValues: function()
	{
		var mvc	=	this.parent.find(".__amslib_mvc_values");
		var po	=	this;
		
		this.__value		= $("<div/>");
		this.__services		= $("<div/>");
		this.__translation	= $("<div/>");
		this.__images		= $("<div/>");
		this.__events		= $("<div/>");
		
		try{
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
