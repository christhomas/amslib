/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *     
 *******************************************************************************/

/**
 * 	class:	Amslib_Simple_Fader_Slideshow
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Simple_Fader_Slideshow.js
 * 
 *	title:	todo, give title
 * 
 *	description:
 *		todo, write description 
 *
 * 	todo:
 * 		write documentation
 * 
 */
var Amslib_Simple_Fader_Slideshow = my.Amslib_Simple_Fader_Slideshow = my.Class(my.Amslib,
{
	images:		false,
	timeout:	false,
	handle:		false,
	
	STATIC: {
		//		NOTE:	This version doesnt work if the slideshow doesnt exist at page load time
		//		NOTE:	There was another system employed in another project which let you "register" a callback
		//		NOTE:	We need to load this callback and let it call the autoloader
		autoload: function(){
			$(Amslib_Simple_Fader_Slideshow.config.pselector+".amslib_autoload").each(function(){
				new Amslib_Simple_Fader_Slideshow(this);
			});
		},
		
		config: {
			pselector: ".amslib_simple_fader_slideshow",
			cselector: "img"
		}
	},
	
	constructor: function(parent)
	{
		Amslib_Simple_Fader_Slideshow.Super.call(this,parent,"Amslib_Simple_Fader_Slideshow");
		
		var selector = Amslib_Simple_Fader_Slideshow.config.cselector;
			
		this.images		=	$(selector,this.parent);
		this.timeout	=	$("input['amslib_simple_fader_slideshow_timeout']",this.parent).val();

		//	Default a missing timeout to 5 seconds
		if(!this.timeout) this.timeout = 5000;
		
		//	Make sure one of the images is set to active, if not, set it to the first image
		var active = $(selector+".active",this.parent);
		if(!active.length) $(selector+":first",this.parent).addClass("active");
		
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
		var selector	=	Amslib_Simple_Fader_Slideshow.config.cselector;
		var active		=	$(selector+".active",this.parent);
		var inactive	=	active.next(selector);
		
		if(!inactive.length) inactive = $(selector+":first",this.parent);
		
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