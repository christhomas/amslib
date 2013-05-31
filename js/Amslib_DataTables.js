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

if(Amslib == undefined || window.exports == undefined) throw("Amslib_DataTables.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	var theme		=	Amslib.getQuery("theme",$("script[src*='Amslib_DataTables.js']").attr("src"));
	var pagination	=	Amslib.getQuery("pagination",$("script[src*='Amslib_DataTables.js']").attr("src"));
	var datatables	=	amslib+"/util/jquery.dataTables/";
	
	var themeList = {
		"smooth":	datatables+"theme.dataTables.smooth.css"
	};
	
	if(!themeList[theme]) theme = "smooth";
	
	Amslib.loadCSS(themeList[theme]);
	Amslib.loadJS("jquery.dataTables",datatables+"jquery.dataTables.min.js");
};

/**
 * 	class:	Amslib_DataTables
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_DataTables.js
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
var Amslib_DataTables = my.Amslib_DataTables = my.Class(my.Amslib,{
	STATIC: {
		autoload: function(){
			$(Amslib_DataTables.options.autoload).each(function(){
				new Amslib_DataTables(this);
			});
		},
		
		options: {
			autoload:			"[data-autoload-datatables]",
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
	
	constructor: function(parent)
	{
		var o = $.extend({}, Amslib_DataTables.options);
		
		Amslib_DataTables.Super.call(this,parent,o.amslibName);
		
		$.fn.dataTableExt.sErrMode = "throw";
		
		//	TODO: implement a way to add customised sorting rules without defaults in the code
		
		var scroll = this.parent.data("dt-scroll");
		if(scroll != undefined) o.sScrollY = scroll;
		
		var paginate = this.parent.data("dt-paginate")
		if(paginate != undefined) o.bPaginate = paginate;
		
		var dom = this.parent.data("dt-dom");
		if(dom != undefined){
			o.sDom = dom;
			if(o.sDom.indexOf("p") < 0){
				o.bPaginate			=	false;
				o.iDisplayLength	=	0;
			}
		}
		
		var page_count = this.parent.data("dt-page-count");
		if(page_count != undefined) o.iDisplayLength	= page_count;
		
		var ajax_source = this.parent.data("dt-ajax-source");
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
		var defer_loading = this.parent.data("dt-defer-loading");
		if(defer_loading != undefined) o.iDeferLoading = 0;

		var pagination = this.parent.data("dt-pagination");
		if(pagination != undefined){
			Amslib.loadJS("jquery.dataTables.pagination",Amslib.locate()+"/util/jquery.dataTables/pagination."+pagination+".js",function(){
				o.sPaginationType = pagination;
				
				this.parent.dataTable(o);
			});
		}else{
			this.parent.dataTable(o);	
		}
	}
});

Amslib.hasJS("jquery.dataTables",Amslib_DataTables.autoload);