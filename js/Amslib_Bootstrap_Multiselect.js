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
 * 	class:	Amslib_Tablesorter
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Tablesorter.js
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
if(typeof(Amslib) == "undefined") throw("Amslib_Bootstrap_Multiselectesorter.js: requires amslib/my.common to be loaded first");

var Amslib_Bootstrap_Multiselect = my.Amslib_Bootstrap_Multiselect = my.Class(Amslib,
{
	STATIC: {
		autoload: function(){
			var base = Amslib.locate()+"/util/bootstrap.multiselect/bootstrap.multiselect";
			
			Amslib.css.load(base+".css");
			Amslib.js.load("multiselect",base+".js",function(){
				var c = Amslib_Bootstrap_Multiselect;
				c.instances = $(c.options.autoload);
				
				c.instances.each(function(){
					new c(this,{});
				});
			});		
		},
		
		options:{
			amslibName:	"Amslib_Bootstrap_Multiselect",
			autoload:	"[data-role='bootstrap.multiselect']"
		},
		
		instances: false,
		
		datakey: {
			"multiselect-maxheight":	"maxHeight",
			"multiselect-filtering":	"enableFiltering",
		}
	},
	
	constructor: function(parent,options)
	{
		var c = Amslib_Bootstrap_Multiselect,
			o = c.options;
		
		c.Super.call(this,parent,o.amslibName);
		
		for(key in c.datakey){
			var v = this.parent.data(key);
			if(v) options[c.datakey[key]] = v;
		}
		
		$(this.parent).multiselect(options);
	}
});

$(document).ready(Amslib_Bootstrap_Multiselect.autoload);