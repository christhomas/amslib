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

if(Prototype.Browser.IE){
	Element.Methods.setOpacity = function(element, value) {
		function stripAlpha(filter){
			return filter.replace(/alpha\([^\)]*\)/gi,'');
		}	
		element = $(element);
		var currentStyle = element.currentStyle;
	    if ((currentStyle && !currentStyle.hasLayout) ||
	    		(!currentStyle && element.style.zoom == 'normal'))
	    	element.style.zoom = 1;
	
	    var filter = element.getStyle('filter'), style = element.style;
	     
	    if (value < 0.00001) value = 0;
		style.filter = stripAlpha(filter) + 
			'alpha(opacity=' + (value * 100) + ')';
		
		return element;
	};
}