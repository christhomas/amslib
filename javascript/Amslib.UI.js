if(Amslib == "undefined")
	throw "Amslib.UI requires Amslib to be loaded.";

Amslib.UI = Class.create(Amslib,
{
	parent:		false,
	callback:	false,
	
	initialize: function($super,parent){
		$super();
		
		this.parent		=	parent;
		this.callback	=	new Hash();
	},
	
	observe: function(eventName,callback)
	{
		this.callback.set(eventName,callback);
	},
	
	callObserver: function(eventName,data)
	{
		var handle = this.callback.get(eventName);
		
		if(handle) handle(data);
	}
})