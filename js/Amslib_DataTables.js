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

if(Amslib == undefined || window.exports == undefined){
	throw("FATAL ERRORS: please load [amslib, my.common, my.class] first, they are missing");
}

/**
 * 	class:	Amslib_DataTables
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_DataTables.js
 * 
 *	description:
 *		todo, write description 
 *
 * 	todo:
 * 		write documentation
 * 
 */
var Amslib_DataTables = my.Amslib_DataTables = my.Class(my.Amslib,{
	STATIC: {
		/**
		 * 	method:	autoload
		 *
		 * 	todo: write documentation
		 */
		autoload: function(){
			var amslib = Amslib.locate();

			if(amslib){
				var src			=	Amslib.getJSPath("Amslib_DataTables.js");
				var theme		=	Amslib.getQuery("theme",src);
				var pagination	=	Amslib.getQuery("pagination",src);
				var datatables	=	amslib+"/util/jquery.dataTables/";
				
				var themeList = {
					"smooth":	datatables+"theme.dataTables.smooth.css"
				};
				
				if(!themeList[theme]) theme = "smooth";
				
				Amslib.loadCSS(themeList[theme]);
				Amslib.loadJS("jquery.dataTables",datatables+"jquery.dataTables.min.js");
			};
			
			Amslib.waitUntil("jquery.dataTables",function(){
				$(Amslib_DataTables.options.autoload).each(function(){
					new Amslib_DataTables(this);
				});
			});
		},
		
		options: {
			autoload:			"[data-datatables-autoload='true']",
			amslibName:			"Amslib_DataTables",
			//	jquery datatables options
			bJQueryUI:			true,
		    sPaginationType:	"full_numbers",
		    sDom:				"<'top floatfix'lpf>rt",
		    iDisplayLength:		25,
		    aaSorting:			[],
		    bAutoWidth:			false
		}
	},
	
	/**
	 * 	method:	constructor
	 *
	 * 	todo: write documentation
	 */
	constructor: function(parent)
	{
		var o = $.extend({}, Amslib_DataTables.options);
		
		Amslib_DataTables.Super.call(this,parent,o.amslibName);
		
		$.fn.dataTableExt.sErrMode = "throw";
		
		//	TODO: implement a way to add customised sorting rules without defaults in the code
		
		var scroll = this.parent.data("scroll");
		if(scroll != undefined) o.sScrollY = scroll;
		
		var paginate = this.parent.data("paginate")
		if(paginate != undefined) o.bPaginate = paginate;
		
		var dom = this.parent.data("dom");
		if(dom != undefined){
			o.sDom = dom;
			if(o.sDom.indexOf("p") < 0){
				o.bPaginate			=	false;
				o.iDisplayLength	=	0;
			}
		}
		
		var page_count = this.parent.data("page-count");
		if(page_count != undefined) o.iDisplayLength	= page_count;
		
		var ajax_source = this.parent.data("ajax-source");
		if(ajax_source != undefined){
			o.bProcessing	=	true;
			o.bServerSide	=	true;
			o.sServerMethod	=	"POST";
			o.sAjaxSource	=	ajax_source;
			o.fnServerData	=	function ( sSource, aoData, fnCallback, oSettings ) {
				aoData.push({"name": "button_action", "value":oSettings.button_action});
				
				oSettings.jqXHR = $.ajax({
					"dataType":	"json",
					"type":		"POST",
					"url":		sSource,
					"data":		aoData,
					"success":	fnCallback
			    });
			}
		}
		
		//	TODO: in future, find a way to know this value, it's for the full number of records in the system
		var defer_loading = this.parent.data("defer-loading");
		if(defer_loading != undefined) o.iDeferLoading = 0;

		var pagination = this.parent.data("pagination");
		$this = this;
		if(pagination != undefined){
			Amslib.loadJS("jquery.dataTables.pagination",Amslib.locate()+"/util/jquery.dataTables/pagination."+pagination+".js",function(){
				o.sPaginationType = pagination;
				
				$this.parent.dataTable(o);
			});
		}else{
			this.parent.dataTable(o);	
		}
	}
});

$(document).ready(Amslib_DataTables.autoload);