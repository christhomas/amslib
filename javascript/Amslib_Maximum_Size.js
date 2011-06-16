Amslib_Maximum_Size = Class.create(Amslib_Event,
{
	elements: false,
	
	initialize: function($super)
	{
		$super();
		
		this.elements = new Array();
		
		Event.observe(window,"resize",this.resize.bind(this));
	},
	
	resize: function()
	{
		//	All the height-padding things here could be done with content-box-height
		this.elements.each(function(e){
			var clayout = new Element.Layout(e);
			var playout = new Element.Layout(e.up());
			
			//	If the width is too large, swap the landscape->portrait classes
			if(clayout.get("width") >= playout.get("width")){
				e.removeClassName("amslib_maximum_size_portrait");
				e.addClassName("amslib_maximum_size_landscape");
			}
			
			//	If the width is too large, swap the portrait->landscape classes
			if(clayout.get("height") >= playout.get("height")){
				
				e.removeClassName("amslib_maximum_size_landscape");
				e.addClassName("amslib_maximum_size_portrait");
			}
		}.bind(this));
		
		this.callObserver("resize-complete");
	},
	
	setBaseClass: function(element)
	{
		var layout = new Element.Layout(element);
		
		if((layout.get("width") / layout.get("height")) > 1){
			element.addClassName("amslib_maximum_size_landscape");
		}else{
			element.addClassName("amslib_maximum_size_portrait");
		}
		
		element.removeClassName("amslib_maximum_size_element");
	},
	
	add: function(element)
	{
		this.setBaseClass(element);
		
		this.elements.push(element);
	}
});

Amslib_Maximum_Size.autoload = function()
{
	var list = $$(".amslib_maximum_size.amslib_autoload .amslib_maximum_size_element");
	
	if(list.length){
		var max = new Amslib_Maximum_Size();
		
		list.each(function(element){
			max.add(element);
		});
		
		max.resize();
	}
};

Event.observe(window,"load",Amslib_Maximum_Size.autoload);