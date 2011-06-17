Amslib_Simple_Fader_Slideshow = my.Class(Amslib,
{
	images:		false,
	timeout:	false,
	handle:		false,
	
	STATIC: {
		//		NOTE:	This version doesnt work if the slideshow doesnt exist at page load time
		//		NOTE:	There was another system employed in another project which let you "register" a callback
		//		NOTE:	We need to load this callback and let it call the autoloader
		autoload: function(){
			$(".amslib_simple_fader_slideshow.amslib_autoload").each(function(){
				new Amslib_Simple_Fader_Slideshow(this);
			});
		}
	},
	
	constructor: function(parent)
	{
		Amslib_Simple_Fader_Slideshow.Super.call(this,parent,"Amslib_Simple_Fader_Slideshow");
			
		this.images		=	$("img",this.parent);
		this.timeout	=	$("input['amslib_simple_fader_slideshow_timeout']",this.parent).val();

		//	Default a missing timeout to 5 seconds
		if(!this.timeout) this.timeout = 5000;
		
		//	Make sure one of the images is set to active, if not, set it to the first image
		var active = $("img.active",this.parent);
		if(!active.length) $("img:first",this.parent).addClass("active");
		
		if(this.images.length > 1) this.start();
	},
	
	start: function()
	{
		//	NOTE: disabled until Amslib_Event is rewritten
		//this.callObserver("start");
		
		this.handle = setTimeout($.proxy(this,"animate"),this.timeout);
	},
	
	stop: function()
	{
		//	NOTE: disabled until Amslib_Event is rewritten
		//this.callObserver("stop");
		
		clearTimeout(this.handle);
		
		this.handle = false;
	},
	
	animate: function()
	{
		var active		=	$("img.active",this.parent);
		var inactive	=	active.next("img");
		
		if(!inactive.length) inactive = $("img:first",this.parent);
		
		//	NOTE: disabled until Amslib_Event is rewritten
		//this.callObserver("fade-start",inactive);
		
		inactive.fadeIn("slow",$.proxy(function(){
			inactive.addClass("active");
			active.removeClass("active");
			
			//	NOTE: disabled until Amslib_Event is rewritten	
			//this.callObserver("fade-complete",inactive);
			
			this.handle = setTimeout($.proxy(this,"animate"),this.timeout);
		},this));
		
		active.fadeOut("slow");
	}
});

$(document).ready(Amslib_Simple_Fader_Slideshow.autoload);