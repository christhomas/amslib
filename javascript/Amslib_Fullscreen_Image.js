Amslib_Fullscreen_Image = Class.create(Amslib,
{
	container:	false,
	imageRatio:	false,
	resizeNode:	false,
	
	initialize: function($super,image,container){
		$super(image,"Amslib_Fullscreen_Image");
		
		this.container = container || image.up(Amslib_Fullscreen_Image.css.container) || $(document.body);
		
		this.observe("change-image",this.changeImage.bind(this));
		this.setImage();
		
		this.enable();
	},
	
	enable: function()
	{
		if(!this.resizeNode) this.resizeNode = document.onresize ? document : window;
		Event.observe(this.resizeNode, "resize",this.resize.bind(this));
		
		this.resize();
		
		return this;
	},
	
	disable: function()
	{
		Event.stopObserving(this.resizeNode,"resize");
		
		return this;
	},
	
	setImage: function(parent)
	{
		parent = parent || this.parent;
		
		if(parent && parent.nodeType && parent.nodeName == "IMG"){
			this.parent		=	parent;
			
			var layout		=	new Element.Layout(this.parent);
			this.imageRatio	=	layout.get("width") / layout.get("height");
		}
	},
	
	resize: function() {
		//	Sometimes, the image doesn't load until very late, causing the ratio calc to fail
		if(isNaN(this.imageRatio)) this.setImage();
		
		var dContainer	=	this.container.getDimensions();
		var rContainer	=	dContainer.width / dContainer.height;
		
		this.parent
				.removeClassName(Amslib_Fullscreen_Image.css.horizontal)
				.removeClassName(Amslib_Fullscreen_Image.css.vertical);
		
		var c = (rContainer > this.imageRatio) ? Amslib_Fullscreen_Image.css.horizontal : Amslib_Fullscreen_Image.css.vertical;
		
		this.parent.addClassName(c);
	},
	
	//	DEPRECATED METHOD
	changeImage: function(parent){ this.setImage(parent); }
});

Amslib_Fullscreen_Image.css = {
	autoload:	".amslib_fullscreen_image.amslib_autoload",
	container:	".amslib_fullscreen_image_container",
	vertical:	"vertical",
	horizontal:	"horizontal"
};

Amslib_Fullscreen_Image.autoload = function(){
	$$(Amslib_Fullscreen_Image.css.autoload).each(function(image)
	{
		new Amslib_Fullscreen_Image(image);
	});
};

Event.observe(window,"load",Amslib_Fullscreen_Image.autoload);
