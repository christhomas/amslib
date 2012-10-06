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