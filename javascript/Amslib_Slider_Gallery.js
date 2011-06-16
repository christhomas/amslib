if(typeof Prototype == "undefined")
	throw("Prototype is required to use this tool");

Amslib_Slider_Gallery = Class.create(Amslib,
{
	item:	false,
	slider:	false,
	
	initialize: function($super,parent)
	{
		$super(parent,"Amslib_Slider_Gallery");
		
		this.item	=	Amslib_Slider_Gallery.css.item;
		this.slider	=	this.parent.down(Amslib_Slider_Gallery.css.slider);
		
		if(	this.parent.select(this.item).length > 1 && 
			this.slider.getDimensions().width > this.parent.getDimensions().width)
		{
			new PeriodicalExecuter(this.animate.bind(this),5);
		}
	},
		
	animate: function()
	{
		var item	=	this.parent.down(this.item);
		var clone	=	item.cloneNode(true);
		
		this.slider.insert(clone);
		
		var next	=	item.next(this.item);
		var left	=	next.positionedOffset().left;
		
		this.slider.morph("left:-"+left+"px",{
			afterFinish: function(){
				item.remove();
				this.slider.setStyle({left:0});
			}.bind(this)
		});
	}
});

Amslib_Slider_Gallery.css = {
	parent: ".amslib_slider_gallery",
	slider:	".amslib_slider_gallery_container",
	item:	".amslib_slider_gallery_item"
}

Amslib_Slider_Gallery.autoload = function()
{
	$$(Amslib_Slider_Gallery.css.parent).each(function(s){
		new Amslib_Slider_Gallery(s);
	});
}

Event.observe(window,"load",Amslib_Slider_Gallery.autoload);