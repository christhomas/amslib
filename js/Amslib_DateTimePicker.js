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

if(typeof(Amslib) == "undefined"){
	throw("Amslib_Bootstrap_DateTimePicker.js: requires amslib to be loaded first");
}

/**
 * 	class:	Amslib_DateTimePicker
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_DateTimePicker.js
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
var Amslib_DateTimePicker = my.Amslib_DateTimePicker = my.Class(Amslib,
{
	STATIC: {
		autoload: function()
		{
			var u = Amslib.locate()+"/util",
				b = u+"/bootstrap.datetimepicker/bootstrap-datetimepicker.min",
				c = Amslib_DateTimePicker;
			
			Amslib.css.load(b+".css");
			
			Amslib.js.loadSeq(
				["moment",u+"/moment.2.5.1-min.js"],
				["bootstrap.datetimepicker",b+".js"]
			);
			
			Amslib.js.has("moment","bootstrap.datetimepicker",function(){
				c.instances = $(c.options.autoload);
				
				c.instances.each(function(){
					new c(this,{});
				});
			});
		},
		
		instances: false,
		
		options: {
			amslibName:	"Amslib_DateTimePicker",
			autoload:	"[data-role='bootstrap-datetimepicker']",
			format:		"DD/MM/YYYY",
			pickTime:	true
		},
		
		dataKey: {
			defaultDate:	"date",
			format:			"date-format",
			pickTime:		"pick-time"
		}
	},
	
	constructor: function(parent,options)
	{
		var c = Amslib_DateTimePicker,
			o = c.options,
			d = c.dataKey;
		
		c.Super.call(this,parent,o.amslibName);
		
		this.options = $.extend(true,{},o,options);
		
		for(k in d){
			var v = this.parent.data(d[k]);
			if(v != undefined) this.options[k] = v;
		}
		
		if(this.options.defaultDate == "today"){
			this.options.defaultDate = moment().format(this.options.dateFormat);
		}
		
		this.parent.datetimepicker(this.options);
	}
});

$(document).ready(Amslib_DateTimePicker.autoload);