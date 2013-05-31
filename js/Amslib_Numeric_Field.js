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
 * 	class:	Amslib_Numeric_Field
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Numeric_Field.js
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
var Amslib_Numeric_Field = my.Amslib_Numeric_Field = my.Class(
{
	timer:			false,
	value:			false,
	min:			false,
	max:			false,
	defaultValue:	false,
	increment:		false,
	interval:		false,
	selector:		false,
	
	STATIC: {
		autoload: function(){
			$(".amslib_numeric_field.amslib_autoload").each(function(){
				new Amslib_Numeric_Field(this);
			});
		}
	},

	constructor: function(parent)
	{
		this.parent	=	$(parent);
		this.timer	=	false;
		this.value	=	$("span.value",this.parent);

		this.readConfig();
						
		this.value.text(this.defaultValue);

		$("span.button",this.parent)
			.click($.proxy(this,"update"))
			.mousedown($.proxy(this,"startUpdate"))
			.mouseup($.proxy(this,"stopUpdate"))
			.mouseout($.proxy(this,"stopUpdate"));
	},

	//	Example config: 0,0,0,1,250,input[name='duration']
	readConfig: function()
	{
		if(c = $("span.config",this.parent)){
			c = c.text().split(",");

			this.min			=	+c[0];
			this.max			=	+c[1];
			this.defaultValue	=	+c[2];
			this.increment		=	+c[3];
			this.interval		=	+c[4];
			this.selector		=	c[5];
		}
	},

	update: function(event)
	{
		var element = event.currentTarget || this;
		
		var delta	=	$(element).hasClass("decrease") ? -this.increment : this.increment;
		var update	=	(+this.value.text())+delta;

		if(update <= this.min){
			$(element).css("opacity","0").animate({opacity:1},100);
			update = this.min;
		}
		
		if(update >= this.max && this.min != this.max){
			$(element).css("opacity","0").animate({opacity:1},100);
			update = this.max;
		}
		 
		this.value.text(update);
		$(this.selector).val(update);
	},

	startUpdate: function(event)
	{
		if(this.timer) this.stopUpdate();
		this.timer = setInterval($.proxy(function(){
			this.update(event);
		},this),this.interval);

		return false;
	},

	stopUpdate: function()
	{
		if(this.timer) clearInterval(this.timer);

		return false;
	}
});

$(document).ready(Amslib_Numeric_Field.autoload);