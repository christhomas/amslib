Amslib_Simple_Fader_Slideshow = Class.create(Amslib,
{
	images: false,
	
	initialize: function($super,parent)
	{
		$super(parent);
		
		var images	=	this.parent.select("img");
		var timeout	=	this.parent.down("input['amslib_simple_fader_slideshow_timeout']");

		//	Default a missing timeout to 5 seconds
		timeout = timeout ? timeout.value : 5;
		
		//	Make sure one of the images is set to active, if not, set it to the first image
		var active = this.parent.down("img.active");
		if(!active) this.parent.down("img").addClassName("active");
		
		if(images.length > 1)
		{
			new PeriodicalExecuter(function(){
				var active		=	this.parent.down("img.active");
				var inactive	=	active.next("img");
				
				if(!inactive){
					inactive = this.parent.down("img");
				}
				
				inactive.appear({
					afterFinish: function(){
						inactive.addClassName("active");
						active.removeClassName("active");
						this.callObserver("change_image",active);
					}.bind(this)
				});
				active.fade();
			}.bind(this),timeout);
		}
	}
});

//	NOTE:	This version doesnt work if the slideshow doesnt exist at page load time
//	NOTE:	There was another system employed in another project which let you "register" a callback
//	NOTE:	We need to load this callback and let it call the autoloader
Amslib_Simple_Fader_Slideshow.autoload = function(){
	$$(".amslib_simple_fader_slideshow.amslib_autoload").each(function(parent){
		new Amslib_Simple_Fader_Slideshow(parent);
	});
}

Event.observe(window,"load",Amslib_Simple_Fader_Slideshow.autoload);