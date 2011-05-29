if(typeof(Amslib_Event) == "undefined")
	throw "Amslib requires Amslib_Event to be loaded.";

//	This class provides some simple common routines used all over the place
var Amslib = Class.create(Amslib_Event,
{
	__objectName:	false,
	parent:			false,
	value:			false,
	services:		false,
	translation:	false,
	images:			false,
	canDebug:		false,

	initialize: function($super,parent,name)
	{
		$super();
		
		//	NOTE: I am not sure this will work with every situation
		//	Try first to just pass it through prototype, then if fails, use as a selector
		this.parent = $(parent);// test this in the future -> || $(document.body).down(parent) || false;
		if(!this.parent) this.parent = $(document.body).down(parent);
		if(!this.parent) return false;

		this.__objectName	=	name || "amslib_controller";
		this.value			=	new Hash();
		this.services		=	new Hash();
		this.callback		=	new Hash();
		this.translation	=	new Hash();
		this.images			=	new Hash();
		
		this.readParameters();
		this.runBefore();
		
		//	Setup the amslib_controller to make this object available on the node it was associated with
		this.parent.store(this.__objectName,this);
		
		//	If there is an init callback, it means someone tried to execute something but the object
		//	was not available yet, so this way we get to "pick up our messages" and act upon them
		Amslib_Event_Init.run(this.parent,this.__objectName,this);
		
		return this;
	},
	
	runBefore: function()
	{
		//	NOTE:	Override this in your child class if you need something to happen BEFORE
		//			The init callbacks are executed
		
		//	NOTE:	override this in your child class to set the default observers
		//	NOTE:	if you don't do this, they might not get called in any amslib_init_callback
	},
	
	getInstanceName: function()
	{
		return this.__objectName;
	},
	
	getNode: function()
	{
		return this.parent;
	},
	
	identify: function()
	{
		return this.parent.identify();
	},
	
	debug: function(string)
	{
		if(this.canDebug) Amslib.firebug(string);
	},
	
	setDebug: function(state)
	{
		this.canDebug = state;
	},
	
	readParameters: function(override)
	{
		if(!this.parent) return false;
		
		var parent = override || this.parent;
		
		var processBlock = function(p){
			if(p.name.indexOf("service:") >= 0){
				this.setService(p.name.replace("service:",""),p.value);
			}else if(p.name.indexOf("translation:") >= 0){
				this.setTranslation(p.name.replace("translation:",""),p.value);
			}else if(p.name.indexOf("image:") >= 0){
				this.setImage(p.name.replace("image:",""),p.value);
			}else{
				this.setValue(p.name,p.value);
			}
		}.bind(this);

		//	Should remove the widget parameters call, because it's out of date
		parent.select(".widget_parameters:first input[type='hidden']").each(processBlock);		
		parent.select(".plugin_parameters:first input[type='hidden']").each(processBlock);
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

Amslib.firebug = function(string)
{
	if(console && console.log) console.log(string);
};

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
	if(!src || !src.node || !dst || !dst.node || !callback) return;
	
	src.object = src.node.retrieve(src.name);
	dst.object = dst.node.retrieve(dst.name);
	
	if(src.object && dst.object){	
		//	Both Source and Destination object exist, execute the handlers directly
		callback(src,dst);
	}else if(src.object){
		//	Source object exists, but Destination object does not
		Amslib_Event_Init.set(dst.node,dst.name,function(context){
			dst.object = context;
			callback(src,dst); 
		});
	}else if(dst.object){	
		//	Destination object exists, but Source object does not
		Amslib_Event_Init.set(src.node,src.name,function(context){
			src.object = context;
			callback(src,dst); 
		});
	}else{
		//	both don't exist, but when one does exist, recall this method, cause now this can't happen again
		Amslib_Event_Init.set(src.node,src.name,function(){
			Amslib.bindObjects(src,dst,callback);
		});
	}
};
