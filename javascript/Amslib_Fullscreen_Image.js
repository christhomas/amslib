Amslib_Fullscreen_Image = Class.create(
{
	container:	false,
	image:		false,
	dImage:		false,
	rImage:		false,
	
	initialize: function(image,container){
		this.container	=	container;
		this.image		=	image;
		this.dImage		=	image.getDimensions();
		this.rImage		=	this.dImage.width / this.dImage.height;
		
		Event.observe(document.onresize ? document : window, "resize",this.resize.bind(this));
		
		this.resize();
	},
	
	resize: function() {
		var dContainer	=	this.container.getDimensions();
		var rContainer	=	dContainer.width / dContainer.height;
		
		this.image.removeClassName("horizontal").removeClassName("vertical");
		
		if(rContainer > this.rImage) this.image.addClassName("horizontal");
		else this.image.addClassName("vertical");
	}
});

Amslib_Fullscreen_Image.autoload = function(){
	$$(".amslib_fullscreen_image_container .amslib_autoload").each(function(image)
	{
		var container = image.up(".amslib_fullscreen_image_container") || document.body;
		
		new Amslib_Fullscreen_Image(image,container);
	});
}

Event.observe(window,"load",Amslib_Fullscreen_Image.autoload);