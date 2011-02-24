Amslib_Simple_Fader_Slideshow = Class.create(
{
	images: false,
	
	initialize: function(parent)
	{
		var images = parent.select("img");
		
		if(images.length > 1)
		{
			new PeriodicalExecuter(function(){
				var active		=	parent.down("img.active");
				var inactive	=	active.next("img");
				
				if(!inactive){
					inactive = parent.down("img");
				}
				
				active.fade();
				inactive.appear({
					afterFinish: function(){
						inactive.addClassName("active");
						active.removeClassName("active");
					}
				});	
			},5);
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