if(typeof(Prototype) == "undefined")
	throw "Amslib_Event_Init requires Prototype to be loaded.";

Amslib_Event_Init = {
	set: function(node,name,callback)
	{
		if(!node) return;

		//	Does the object already exist?
		var obj = $(node).retrieve(name);
		
		if(obj){
			//	If the object already exists, just execute the callback on the object
			callback(obj);
		}else{
			//	If the object doesn't exist, create a callback for it
			var cb = $(node).retrieve(name+"_init_callback");
			
			if(!cb) cb = new Array();
			
			if(callback) cb.push(callback);
			
			node.store(name+"_init_callback",cb);
		}
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