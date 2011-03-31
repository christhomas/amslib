if(typeof(Prototype) == "undefined")
	throw "Amslib requires Prototype to be loaded.";

//	This class provides some simple common routines used all over the place
var Amslib = Class.create(
{
	parent:			false,
	value:			false,
	services:		false,
	translation:	false,
	callback:		false,
	images:			false,
	canDebug:		false,

	initialize: function(parent,name)
	{
		//	NOTE: I am not sure this will work with every situation
		//	Try first to just pass it through prototype, then if fails, use as a selector
		this.parent = $(parent);
		if(!this.parent) this.parent = $(document.body).down(parent);
		if(!this.parent) return false;

		this.callback		=	new Hash();
		this.value			=	new Hash();
		this.services		=	new Hash();
		this.callback		=	new Hash();
		this.translation	=	new Hash();
		this.images			=	new Hash();
				
		this.observe("default-observer",this.defaultObserver.bind(this));
		
		this.readParameters();
		this.runBefore();
		
		//	Setup the amslib_controller to make this object available on the node it was associated with
		this.parent.store(name || "amslib_controller",this);
		
		//	If there is an init callback, it means someone tried to execute something but the object
		//	was not available yet, so this way we get to "pick up our messages" and act upon them
		Amslib.runInitCallback(this.parent,name,this);
		
		return this;
	},
	
	runBefore: function()
	{
		//	NOTE:	Override this in your child class if you need something to happen BEFORE
		//			The init callbacks are executed
		
		//	NOTE:	override this in your child class to set the default observers
		//	NOTE:	if you don't do this, they might not get called in any amslib_init_callback
	},
	
	getNode: function()
	{
		return this.parent;
	},
	
	identify: function()
	{
		return this.parent.identify();
	},
	
	observe: function(eventName,callback)
	{
		var cb = this.callback.get(eventName);
		
		if(!cb) cb = new Array();
		
		cb.push(callback);
		
		this.callback.set(eventName,cb);
	},
	
	callObserver: function(eventName)
	{
		var handle = this.getObserver(eventName);
		
		//	We slice off the first parameter because it's eventName and we 
		//	dont want that passed along to the function
		if(handle){
			var args = $A(arguments).slice(1);
			
			handle.each(function(h){
				h.apply(h,args);
			});
		}
	},
	
	getObserver: function(eventName)
	{
		var cb = this.callback.get(eventName);
		
		return (cb) ? cb : this.callback.get("default-observer");
	},
	
	defaultObserver: function(){},
	
	debug: function(string)
	{
		if(this.canDebug && console && console.log) console.log(string);
	},
	
	setDebug: function(state)
	{
		this.canDebug = state;
	},
	
	//	DEPRECATED METHODS: leave them here for a while and then remove them
	setCallback: function(type,callback){	this.observe(type,callback);	},
	getCallback: function(type){			return this.getObserver(type);	},

	readParameters: function(override)
	{
		if(!this.parent) return false;
		
		var parent = override || this.parent;
		
		parent.select(".widget_parameters input[type='hidden']").each(function(p){
			if(p.name.indexOf("service:") >= 0){
				this.setService(p.name.replace("service:",""),p.value);
			}else if(p.name.indexOf("translation:") >= 0){
				this.setTranslation(p.name.replace("translation:",""),p.value);
			}else if(p.name.indexOf("image:") >= 0){
				this.setImage(p.name.replace("image:",""),p.value);
			}else{
				this.setValue(p.name,p.value);
			}
		}.bind(this));
	},
	
	//	Getter/Setter for the object values
	setValue: function(name,value){			this.value.set(name,value);			},
	getValue: function(name){				return this.value.get(name);		},
	
	//	Getter/Setter for web services
	setService: function(name,value){		this.services.set(name,value);		},
	getService: function(name){				return this.services.get(name);		},
	
	//	Getter/Setter for text translations
	setTranslation: function(name,value){	this.translation.set(name,value);	},
	getTranslation: function(name){			return this.translation.get(name);	},
	
	//	Getter/Setter for images
	setImage: function(name,value){			this.images.set(name,value);		},
	getImage: function(name){				return this.images.get(name);		}
});

/*****************************************************************
 * 	STATIC METHODS THAT DO GENERAL HOUSEKEEPING
*****************************************************************/
Amslib.setInitCallback = function(node,name,callback)
{
	var cb = node.retrieve(name+"_init_callback");
	
	if(!cb) cb = new Array();
	
	cb.push(callback);
	
	node.store(name+"_init_callback",cb);
}

Amslib.getInitCallback = function(node,name)
{
	return node.retrieve(name+"_init_callback");
}

Amslib.runInitCallback = function(node,name,context)
{
	var cb = Amslib.getInitCallback(node,name);
	
	if(cb) cb.each(function(callback){
		callback(context);
	});
}

/**
 * This is a way to call a function (the callback) in the presence of
 * two objects that must exist in order for the callback to be executable
 * 
 * This is normally used when you want to "tie" two objects together,
 * but are not 100% sure both objects exist at the time you requested it
 * 
 * NOTE:	There is no way to cancel this once it's called, perhaps we should
 * 			find a way to do that.
 * 
 * NOTE:	This method is quite expensive and should only really be used
 * 			when you really have to connect two objects to collaborate 
 * 			together, but really don't know when the other object will arrive
 */
Amslib.bindObjects = function(src,dst,callback)
{
	if(!src.node || !dst.node) return;
	
	src.object = src.node.retrieve(src.name);
	dst.object = dst.node.retrieve(dst.name);
	
	if(src.object && dst.object){	
		//	Both Source and Destination object exist, execute the handlers directly
		callback(src,dst);
	}else if(src.object){
		//	Source object exists, but Destination object does not
		Amslib.setInitCallback(dst.node,dst.name,function(context){
			dst.object = context;
			callback(src,dst); 
		});
	}else if(dst.object){	
		//	Destination object exists, but Source object does not
		Amslib.setInitCallback(src.node,src.name,function(context){
			src.object = context;
			callback(src,dst); 
		});
	}else{
		//	both don't exist, but when one does exist, recall this method, cause now this can't happen again
		Amslib.setInitCallback(src.node,src.name,function(){
			Amslib.bindObjects(src,dst,callback);
		});
	}
}