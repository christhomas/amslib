/***************************************************************
 *	file:	prototype.fix.js
 *	title:	Prototype Fix
 *
 *	description:	This is a file of methods which override prototypes methods and extend them to 
 *					fix bugs in their programming style and make logical things work again
 */

Element.addMethods({
	show: function(element,override){
		if(!override || override == "undefined") override = "block";
		
		element = $(element);
    	element.style.display = override;
    	return element;
	},
	
	setOpacity: function(element,value){
		element = $(element);
	    element.style.opacity = (value < 0.00001) ? 0 : value;
	    return element;	
	}
});