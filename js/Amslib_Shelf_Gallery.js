var Amslib_Shelf_Gallery = my.Amslib_Shelf_Gallery = my.Class(my.Amslib,
{
	items: false,
	mutex: false,
	
	STATIC: {
		autoload: function(){
			$(".amslib_shelf_gallery").each(function(){
				new Amslib_Shelf_Gallery($(this));
			});
		},
		
		options: {
			amslibName:	"Amslib_Shelf_Gallery",
			animate:	"animateAutoNext",
			selSlider:	".amslib_shelf_gallery_slider",
			selItem:	".amslib_shelf_gallery_item"
		}
	},
	
	constructor: function(parent)
	{
		Amslib_Shelf_Gallery.Super.call(this,parent,Amslib_Shelf_Gallery.options.amslibName);
		
		this.options	=	Amslib_Shelf_Gallery.options;
		this.slider		=	$(this.options.selSlider,this.parent);
		this.items		=	$(this.options.selItem,this.parent);
		this.mutex		=	false;
		
		this.start();
	},
	
	start: function()
	{
		this.timeout = setTimeout($.proxy(this,this.options.animate),5000);
	},
	
	stop: function()
	{
		if(this.timeout) clearTimeout(this.timeout);
	},
	
	setAnimation: function(type)
	{
		switch(type){
			case "animateAutoNext":
			case "animateAutoPrev":{
				this.options.animate = type;
			}break;
		}
	},
	
	prev: function(cb)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		var first	=	$(this.options.selItem+":first",this.parent);
		var last	=	$(this.options.selItem+":last",this.parent);
		//	Move the last element to the first position, grab the left to offset by
		this.slider.prepend(last.detach());
		var left	=	first.position().left;
		this.slider.css("left","-"+left+"px");
		//	Animate to left:0
		this.slider.animate({left:"0px"},"slow",$.proxy(function(){
			if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	next: function(cb)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		var first = $(this.options.selItem+":first",this.parent);
		var left = first.next(this.options.selItem).position().left;
		
		this.slider.animate({left:"-="+left},"slow",$.proxy(function(){
			this.slider.append(first.detach());
			this.slider.css("left","0px");
			
			if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	animateAutoNext: function()
	{
		this.next($.proxy(function(){
			setTimeout($.proxy(this,this.options.animate),5000);
		},this));
	},
	
	animateAutoPrev: function()
	{
		this.prev($.proxy(function(){
			setTimeout($.proxy(this,this.options.animate),5000);
		},this));
	}
});

$(document).ready(Amslib_Shelf_Gallery.autoload);