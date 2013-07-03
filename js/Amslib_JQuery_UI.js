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
 * 	class:	Amslib_JQuery_UI
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_JQuery_UI.js
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

/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_JQuery_UI.js: requires Amslib.js+my.class.min.js to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	//	FIXME: It looks from the method functionality that these two parameters should be swapped over
	//	NOTE: also logically it makes more sense that they are reversed also.
	var theme = Amslib.getQuery("theme",Amslib.getJSPath("Amslib_JQuery_UI.js"));
	
	//	FIXME: what to do if the selected theme is not available? nothing?
	
	Amslib.loadCSS(amslib+"/util/jqueryui/"+theme+"/styles.css");
	Amslib.loadJS("jquery.ui",amslib+"/util/jquery-ui-1.8.14.custom.min.js");
};

var Amslib_JQuery_UI = my.Amslib_JQuery_UI = my.Class(Amslib,
{
	STATIC:{
		/**
		 * 	method:	autoload
		 *
		 * 	todo: write documentation
		 */
		autoload: function(){
			var jqui = new Amslib_JQuery_UI();
			
			$(Amslib_JQuery_UI.options.autoload_datepicker).each(function(){
				jqui.setupDatepicker(this);
			});
		},
		
		options: {
			autoload_datepicker:	"[data-jqueryui-datepicker]",
			amslibName:				"Amslib_JQuery_UI"
		},
		
		datepicker: {
			dateFormat:	"yy-mm-dd",
			firstDay:	1
		}
	},
	
	/**
	 * 	method:	constructor
	 *
	 * 	todo: write documentation
	 */
	constructor: function(parent)
	{
		Amslib_JQuery_UI.Super.call(this,$(document),Amslib_JQuery_UI.options.amslibName);
	},
	
	/**
	 * 	method:	setupDatepicker
	 *
	 * 	todo: write documentation
	 */
	setupDatepicker: function(parent)
	{
		var o = $.extend({}, Amslib_JQuery_UI.datepicker);
		
		$(parent).datepicker(o);
	}
});

Amslib.hasJS("jquery.ui",Amslib_JQuery_UI.autoload);