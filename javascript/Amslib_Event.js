if(typeof(Prototype) == "undefined")
	throw "Amslib_Event requires Prototype to be loaded.";

//	This class provides some basic event functions so you can trigger events programatically
//	this also lets others tie into functionality so that when something happens, you can
//	trigger other things external to this class to happen also
var Amslib_Event = Class.create(
{
	callback:		false,

	initialize: function()
	{
		this.callback = new Hash();
				
		this.observe("default-observer",this.defaultObserver.bind(this));
		
		return this;
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
	
	connectObserver: function(node,prototypeObserver,amslibObserver)
	{
		node.observe(prototypeObserver,this.bindObserver(amslibObserver));
	},
	
	//	NOTE:	the only function this method does, is return a bound(this) function call to an observer
	//	NOTE:	does that actually perform a useful function in many cases? I'm not so sure anymore.
	//	NOTE:	if a purpose is found, write a piece of documentation for this method so I dont forget again
	bindObserver: function()
	{
		var args = $A(arguments);
		
		return function(){
			this.callObserver.apply(this,args);
		}.bind(this);
	},
	
	defaultObserver: function(){}
});

/************************************************************************************/