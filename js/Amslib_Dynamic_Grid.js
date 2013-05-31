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
 * 	class:	Amslib_Dynamic_Grid
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Dynamic_Grid.js
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
var Amslib_Dynamic_Grid = my.Amslib_Dynamic_Grid = my.Class(
{
	list:	false,
	
	STATIC: {
		autoload: function(){
			new Amslib_Dynamic_Grid($(Amslib_Dynamic_Grid.options.parent+".amslib_autoload"));
		},
		
		options: {
			parent:	".amslib_dynamic_grid",
			child:	".amslib_dynamic_grid_child"
		}
	},
	
	constructor: function(parent)
	{
		this.list	=	$(Amslib_Dynamic_Grid.options.child,parent);
		
		this.resize();
		
		$(document.onresize ? document : window).bind("resize",$.proxy(this,"resize"));
	},
	
	resize: function()
	{
		if(this.list.length == 0) return;
		
		var element = this.list.first();
		
		while(element.length){
			var row = this.list.map(function(f){
				if($(this).position().top == element.position().top) return this;
			});
			
			if(row.length){
				var max = 0;
				row.each(function(){ 
					if((h=$(this).outerHeight()) > max) max = h;
					$(this).attr("data-calc-height",h);
				});
				
				row.each(function(){
					var h = $(this).outerHeight();
					var m = max - h;
					
					if(m > 0) $(this).css("marginBottom",m+"px");
				});
				
				element = row.last().next(Amslib_Dynamic_Grid.options.child);
			}else{
				element = element.next(Amslib_Dynamic_Grid.options.child);
			}
		}
	}
});

$(document).ready(Amslib_Dynamic_Grid.autoload);