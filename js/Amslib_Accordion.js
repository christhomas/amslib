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
 * 	class:	Amslib_Accordion
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Accordion.js
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
var Amslib_Accordion = my.Amslib_Accordion = my.Class(
{
	STATIC: {
		/**
		 * 	method:	autoload
		 *
		 * 	todo: write documentation
		 */
		autoload: function(){
			$(Amslib_Accordion.options.src).each(function(){
				new Amslib_Accordion($(this));
			})
		},
		
		options: {
			src: ".amslib_accordion_trigger",
			dst: ".amslib_accordion_target",
			open: "open",
			time: 1000
		}
	},
	
	/**
	 * 	method:	constructor
	 *
	 * 	todo: write documentation
	 */
	constructor: function(parent)
	{
		var p = $(parent);
		var d = Amslib_Accordion.options.dst;
		var t = Amslib_Accordion.options.time;
		var o = Amslib_Accordion.options.open;
		
		p.bind("click",function(){
			l = p.next(d);
			p.removeClass(o);
			
			if(l.hasClass(o)){
				l.slideUp().removeClass(o);
			}else{
				p.siblings(d+"."+o).slideUp().removeClass(o);
				p.addClass(o);
				l.slideDown(t).addClass(o);
			}
			
			return false;
		});
	}
});

$(document).ready(Amslib_Accordion.autoload);
 