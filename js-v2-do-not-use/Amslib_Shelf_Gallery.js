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
 * 	class:	Amslib_Shelf_Gallery
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Shelf_Gallery.js
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
var Amslib_Shelf_Gallery = my.Amslib_Shelf_Gallery = my.Class(my.Amslib,
{
	items: false,
	mutex: false,
	
	STATIC: {
		/**
		 * 	method:	autoload
		 *
		 * 	todo: write documentation
		 */
		autoload: function(){
			$(Amslib_Shelf_Gallery.options.parent).each(function(){
				new Amslib_Shelf_Gallery($(this));
			});
		},
		
		options: {
			amslibName:		"Amslib_Shelf_Gallery",
			animate:		"animateAutoNext",
			parent:			".amslib_shelf_gallery",
			selSlider:		".amslib_shelf_gallery_slider",
			selItem:		".amslib_shelf_gallery_item",
			autoTimeout:	5000,
			animTimeout:	1000,
			clickTimeout:	500
		}
	},
	
	/**
	 * 	method:	constructor
	 *
	 * 	todo: write documentation
	 */
	constructor: function(parent)
	{
		Amslib_Shelf_Gallery.Super.call(this,parent,Amslib_Shelf_Gallery.options.amslibName);
		
		this.options	=	Amslib_Shelf_Gallery.options;
		this.slider		=	$(this.options.selSlider,this.parent);
		this.items		=	$(this.options.selItem,this.parent);
		this.mutex		=	false;
		
		this.start();
	},
	
	/**
	 * 	method:	start
	 *
	 * 	todo: write documentation
	 */
	start: function()
	{
		this.timeout = setTimeout($.proxy(this,this.options.animate),this.options.autoTimeout);
	},
	
	/**
	 * 	method:	stop
	 *
	 * 	todo: write documentation
	 */
	stop: function()
	{
		if(this.timeout) clearTimeout(this.timeout);
	},
	
	/**
	 * 	method:	setAnimation
	 *
	 * 	todo: write documentation
	 */
	setAnimation: function(type)
	{
		switch(type){
			case "animateAutoNext":
			case "animateAutoPrev":{
				this.options.animate = type;
			}break;
		}
	},
	
	/**
	 * 	method:	prev
	 *
	 * 	todo: write documentation
	 */
	prev: function(cb)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		var first	=	$(this.options.selItem+":first",this.parent);
		var last	=	$(this.options.selItem+":last",this.parent);
		
		if(!last){
			this.mutex = false;
			return;
		}
		
		//	Move the last element to the first position, grab the left to offset by
		this.slider.prepend(last.detach());

		this.slider.css("left","-"+first.position().left+"px");
		//	Animate to left:0
		this.slider.animate({left:"0px"},this.options.animTimeout,$.proxy(function(){
			if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	/**
	 * 	method:	next
	 *
	 * 	todo: write documentation
	 */
	next: function(cb)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		var first = $(this.options.selItem+":first",this.parent);
		var next = first.next(this.options.selItem);

		//	If there is no next, return and don't do anything
		if(!next){
			this.mutex = false;
			return;
		}
		
		this.slider.animate({left:"-="+next.position().left},this.options.animTimeout,$.proxy(function(){
			this.slider.append(first.detach());
			this.slider.css("left","0px");
			
			if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	/**
	 * 	method:	toIndex
	 *
	 * 	todo: write documentation
	 */
	toIndex: function(index)
	{
		var first	=	$(this.options.selItem+":first",this.parent);
		var start	=	this.items.index(first);
		var finish	=	this.items.index(this.items.get(index));
		
		if(start == finish) return;
		
		if(start > finish){
			this.toIndexPrev(start,finish);
		}else{
			this.toIndexNext(start,finish);
		}
	},
	
	/**
	 * 	method:	toIndexPrev
	 *
	 * 	todo: write documentation
	 */
	toIndexPrev: function(s,f)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		for(a=s-1;a>=f;a--) this.slider.prepend($(this.items.get(a)).detach());
		
		var left = $(this.items.get(s)).position().left;
		this.slider.css("left","-"+left+"px");
		var time = (s-f)*this.options.clickTimeout;

		//	Animate to left:0
		this.slider.animate({left:"0px"},time,$.proxy(function(){
			//if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	/**
	 * 	method:	toIndexNext
	 *
	 * 	todo: write documentation
	 */
	toIndexNext: function(s,f)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		var end = $(this.items.get(f));
		var left = end.position().left;
		var time = (f-s)*this.options.clickTimeout;

		this.slider.animate({left:-left+"px"},time,$.proxy(function(){
			for(a=s;a<f;a++) this.slider.append($(this.items.get(a)).detach());
			this.slider.css("left","0px");

			//	NOTE: Why is this commented out? and what is cb anyway? callback for what?
			//if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	/**
	 * 	method:	animateAutoNext
	 *
	 * 	todo: write documentation
	 */
	animateAutoNext: function()
	{
		this.next($.proxy(this,"start"));
	},
	
	/**
	 * 	method:	animateAutoPrev
	 *
	 * 	todo: write documentation
	 */
	animateAutoPrev: function()
	{
		this.prev($.proxy(this,"start"));
	}
});

$(document).ready(Amslib_Shelf_Gallery.autoload);