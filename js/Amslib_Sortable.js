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
 * 	class:	Amslib_Sortable
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Sortable.js
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
if(typeof(Amslib) == "undefined") throw("Amslib_Sortable.js: requires amslib/my.common to be loaded first");

var Amslib_Sortable = my.Amslib_Sortable = my.Class(my.Amslib,
{
	STATIC: {
		autoload: function(){
			var c = Amslib_Sortable;
			
			Amslib.js.load("jquery.sortable",Amslib.locate()+"/util/html5sortable/jquery.sortable.min.js",function(){
				c.instances = $(c.options.autoload);
				c.instances.sortable();
				
				c.instances.each(function(){
					var f = $(this).data(c.datakey.connect_from);
					var t = $(this).data(c.datakey.connect_to);
					
					if(!f || !t) return;
					
					$(f).sortable({
						connectWith: t
					});
				});
			});
		},
		
		options: {
			amslibName: "Amslib_Sortable",
			autoload:	"[data-role='jquery.sortable']"
		},
		
		datakey: {
			connect_from:	"sortable-connect-from",
			connect_to:		"sortable-connect-to"
		}
	}
});

$(document).ready(Amslib_Sortable.autoload);