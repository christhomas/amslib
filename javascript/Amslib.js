if(typeof(Prototype) == "undefined")
	throw "Amslib requires Prototype to be loaded.";

//	This class provides some simple common routines used all over the place
var Amslib = Class.create(
{
	//	NOTE: mainWidget should be removed because it was replaced by this.parent
	mainWidget: 	false,
	
	parent:			false,
	value:			false,
	services:		false,
	translation:	false,
	callback:		false,
	images:			false,

	initialize: function(parent)
	{
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
				
		this.readParameters();
		this.setupDefaultObservers();
		this.setupAmslib();
		
		//	NOTE: This should be eventually removed in favour of using parent all the time
		this.mainWidget		=	this.parent;
		
		return this;
	},
	
	setupDefaultObservers: function()
	{
		//	Do nothing, overload in your child class and put the default observers you want
	},
	
	setupAmslib: function()
	{
		//	Setup the amslib_controller to make this object available on the node it was associated with
		this.parent.store("amslib_controller",this);
		
		//	If there is an init callback, it means someone tried to execute something but the object
		//	was not available yet, so this way we get to "pick up our messages" and act upon them
		var initcb = this.parent.retrieve("amslib_init_callback");
		if(initcb) initcb(this);
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
		this.callback.set(eventName,callback);
	},
	
	callObserver: function(eventName)
	{
		var handle = this.callback.get(eventName);
		
		//	We slice off the first parameter because it's eventName and we 
		//	dont want that passed along to the function
		return (handle) ? handle.apply(handle,$A(arguments).slice(1)) : false;
	},
	
	getObserver: function(eventName)
	{
		var cb = this.callback.get(type);
		
		return (cb) ? cb : this.defaultObserver;
	},
	
	defaultObserver: function(){
		if(console && console.log) console.log("DEFAULT CALLBACK CALLED");
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