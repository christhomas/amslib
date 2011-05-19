if(typeof(Prototype) == "undefined")
	throw "Amslib_Event_Init requires Prototype to be loaded.";

Amslib_Event_Init = {
	set: function(node,name,callback)
	{
		if(!node) return;
		
		var cb = node.retrieve(name+"_init_callback");
		
		if(!cb) cb = new Array();
		
		if(callback) cb.push(callback);
		
		node.store(name+"_init_callback",cb);
	},

	get: function(node,name)
	{
		if(!node) return;
		
		return node.retrieve(name+"_init_callback");
	},

	run: function(node,name,context)
	{
		if(!node) return;
		
		var cb = Amslib_Event_Init.get(node,name);
		
		if(cb) cb.each(function(callback){
			callback(context);
		});
	}
};

/************************************************************************************/