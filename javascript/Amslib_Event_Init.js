if(typeof(Prototype) == "undefined")
	throw "Amslib requires Prototype to be loaded.";

/*****************************************************************
 * 	STATIC METHODS THAT DO GENERAL HOUSEKEEPING
*****************************************************************/
Amslib_Event_Init.set = function(node,name,callback)
{
	if(!node) return;
	
	var cb = node.retrieve(name+"_init_callback");
	
	if(!cb) cb = new Array();
	
	if(callback) cb.push(callback);
	
	node.store(name+"_init_callback",cb);
}

Amslib_Event_Init.get = function(node,name)
{
	if(!node) return;
	
	return node.retrieve(name+"_init_callback");
}

Amslib_Event_Init.run = function(node,name,context)
{
	if(!node) return;
	
	var cb = Amslib.get(node,name);
	
	if(cb) cb.each(function(callback){
		callback(context);
	});
}