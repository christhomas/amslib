Amslib_Fullscreen_Image = Class.create(Amslib,
{
	container:	false,
	imageRatio:	false,
	
	initialize: function($super,image,container){
		$super(image,"Amslib_Fullscreen_Image");
		
		this.container	=	container;
		
		this.observe("change-image",this.changeImage.bind(this));
		this.changeImage(this.parent);
		
		Event.observe(document.onresize ? document : window, "resize",this.resize.bind(this));
		
		this.resize();
	},
	
	changeImage: function(parent)
	{
		if(parent && parent.nodeType && parent.nodeType == "IMG"){
			this.parent		=	parent;
			
			dimensions		=	this.parent.getDimensions();
			this.imageRatio	=	dimensions.width / dimensions.height;
		}
	},
	
	resize: function() {
		var dContainer	=	this.container.getDimensions();
		var rContainer	=	dContainer.width / dContainer.height;
		
		this.parent.removeClassName("horizontal").removeClassName("vertical");
		
		if(rContainer > this.imageRatio) this.parent.addClassName("horizontal");
		else this.parent.addClassName("vertical");
	}
});

Amslib_Fullscreen_Image.autoload = function(){
	$$(".amslib_fullscreen_image.amslib_autoload").each(function(image)
	{
		var container = image.up(".amslib_fullscreen_image_container") || $(document.body);
		
		new Amslib_Fullscreen_Image(image,container);
	});
}

Event.observe(window,"load",Amslib_Fullscreen_Image.autoload);