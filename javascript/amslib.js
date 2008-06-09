function addEvent(obj, evType, fn, useCapture)
{
	if (obj.addEventListener){
		obj.addEventListener(evType, fn, useCapture);
		return true;
	} else if (obj.attachEvent){
		var r = obj.attachEvent("on"+evType, fn);
		return r;
	} else {
		alert("Handler could not be attached");
	}
}

function stopBubble(event)
{
	try{
		if(event && event.stopPropagation) {
			event.stopPropagation();
		}	
	}catch(err){}
	
	try{	window.event.cancelBubble = true;	}catch(err){}
	
	return false;
}

/**
	This code was taken from the page
	http://phrogz.net/JS/Classes/OOPinJS2.html
	
	Thanks for writing it and letting me use it.  It might not work!!!
*/
Function.prototype.inheritsFrom = function( parentClassOrObject ){ 
	if ( parentClassOrObject.constructor == Function ) 
	{ 
		//Normal Inheritance 
		this.prototype = new parentClassOrObject;
		this.prototype.constructor = this;
		this.prototype.parent = parentClassOrObject.prototype;
	} 
	else 
	{ 
		//Pure Virtual Inheritance 
		this.prototype = parentClassOrObject;
		this.prototype.constructor = this;
		this.prototype.parent = parentClassOrObject;
	} 
	return this;
} 