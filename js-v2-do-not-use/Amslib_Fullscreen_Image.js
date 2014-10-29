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
 * 	class:	Amslib_Fullscreen_Image
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Fullscreen_Image.js
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
var Amslib_Fullscreen_Image = my.Amslib_Fullscreen_Image = my.Class(my.Amslib,
{
	container:	false,
	imageRatio:	false,
	resizeNode:	false,
	
	STATIC: {
		/**
		 * 	method:	autoload
		 *
		 * 	todo: write documentation
		 */
		autoload: function(){
			$(Amslib_Fullscreen_Image.options.autoload).each(function(){
				new Amslib_Fullscreen_Image(this);
			});
		},
		
		options: {
			amslibName:	"Amslib_Fullscreen_Image",
			autoload:	".amslib_fullscreen_image.amslib_autoload",
			container:	".amslib_fullscreen_image_container",
			vertical:	"vertical",
			horizontal:	"horizontal"
		}
	},
	
	/**
	 * 	method:	constructor
	 *
	 * 	todo: write documentation
	 */
	constructor: function(image,container){
		Amslib_Fullscreen_Image.Super.call(this,image,Amslib_Fullscreen_Image.options.amslibName);
		
		this.container = container || this.parent.closest(Amslib_Fullscreen_Image.options.container) || $(document.body);
		
		//	TODO: we need to implement the event system for this to work
		//this.observe("change-image",this.setImage.bind(this));
		this.setImage();
		
		this.enable();
	},
	
	/**
	 * 	method:	enable
	 *
	 * 	todo: write documentation
	 */
	enable: function()
	{
		if(!this.resizeNode)	this.resizeNode = $(document.onresize ? document : window);
		if(this.resizeNode)		this.resizeNode.on("resize",$.proxy(this,"resize"));
		
		this.resize();
		
		return this;
	},
	
	/**
	 * 	method:	disable
	 *
	 * 	todo: write documentation
	 */
	disable: function()
	{
		this.resizeNode.off("resize");
		
		return this;
	},
	
	/**
	 * 	method:	setImage
	 *
	 * 	todo: write documentation
	 */
	setImage: function(parent)
	{
		parent = parent || this.parent;
		
		if(parent && parent.nodeType && parent.nodeName == "IMG"){
			this.parent		=	parent;
			this.imageRatio	=	this.parent.width() / this.parent.height();
		}
	},
	
	/**
	 * 	method:	resize
	 *
	 * 	todo: write documentation
	 */
	resize: function() {
		//	Sometimes, the image doesn't load until very late, causing the ratio calc to fail
		//	FIXME: this might cause a huge number of calls to a method which will fail everytime
		if(isNaN(this.imageRatio)) this.setImage();
		
		var rContainer	=	this.container.width() / this.container.height();
		
		this.parent
				.removeClass(Amslib_Fullscreen_Image.options.horizontal)
				.removeClass(Amslib_Fullscreen_Image.options.vertical);
		
		var c = (rContainer > this.imageRatio) ? Amslib_Fullscreen_Image.options.horizontal : Amslib_Fullscreen_Image.options.vertical;
		
		this.parent.addClass(c);
		
		//	NOTE: we need the new event system for this to be re-instated
		//this.callObserver("resize",this.parent,this.container,c);
	}
});

$(document).ready(Amslib_Fullscreen_Image.autoload);
