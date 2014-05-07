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
	throw("Amslib_Tablesorter.js: requires amslib to be loaded first");
}

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
var Amslib_Tablesorter = my.Amslib_Tablesorter = my.Class(Amslib,
{
	debug: false,
	
	STATIC: {
		autoload: function(){
			var c = Amslib_Tablesorter,
				f = c.files.get;
			
			c.setLocation();
			
			Amslib.css.load(f("theme","normal"));
			
			Amslib.js.loadSeq(
				["tablesorter",f("js","ts")],
				["tablesorter.widgets",f("js","widgets")]
			);
			
			Amslib.js.has("tablesorter","tablesorter.widgets",function(){
				c.instances = $(c.options.autoload);

				c.instances.each(function(){
					new c(this,{});
				});
			});
		},
		
		options:{
			amslibName:	"Amslib_Tablesorter",
			autoload: 	"[data-role='jquery.tablesorter']",
			
			//	Default options for the tablesorter
			tablesorter:{
				sortReset:		true,
				widgets:		["zebra"],
				showProcessing:	true				
			},
			
			filter:{
				ajax: {
					filter_searchDelay: 1000
				},
				
				non_ajax: {
					
				}
			},
			
			pager:{
				//	Number of visible rows - default is 10
				size: 25,
				
				removeRows: false,
				
				output:		'{startRow} - {endRow} / {filteredRows} (total: {totalRows})',
		
				//	Overridden defaults for these css elements in the pager
				cssGoto:		".goto",				//	select a particular page
				cssPageDisplay:	".display",				//	location of where the "output" is displayed
				cssPageSize:	".count",				//	page size selector - select dropdown that sets the "size" option
			},
			
			//	There are all the default options when you enable the ajax-pager option
			//	All the options here are mixed with the pager options, but it's done when you trigger it
			ajaxPager:{
				//	use this format: "http://mydatabase.com?page={page}&size={size}&{sortList:col}"
				//	where {page} is replaced by the page number (or use {page+1} to get a one-based index),
				//	{size} is replaced by the number of records to show,
				//	{sortList:col} adds the sortList to the url into a "col" array, and {filterList:fcol} adds
				//	the filterList to the url into an "fcol" array.
				//	So a sortList = [[2,0],[3,0]] becomes "&col[2]=0&col[3]=0" in the url
				//	and a filterList = [[2,Blue],[3,13]] becomes "&fcol[2]=Blue&fcol[3]=13" in the url
				ajaxUrl: null,

				//	customised option to allow or block the autoloading of new data across ajax on page load
				//	sometimes you want to preload the table with content and only load more when somebody
				//	clicks on a page button
				ajaxAutoload: true,
				
				//	add more ajax settings here
				//	see http://api.jquery.com/jQuery.ajax/#jQuery-ajax-settings
				ajaxObject: {
					dataType: 'json'
				},
				
				//	process ajax so that the following information is returned:
				//	[ 
				//		total_rows (number), 
				//		rows (array of arrays), 
				//		headers (array; optional)
				//	]
				//	example:
				//	[
				//		100,  // total rows
				//		[
				//			[ "row1cell1", "row1cell2", ... "row1cellN" ],
				//			[ "row2cell1", "row2cell2", ... "row2cellN" ],
				//			...
				//			[ "rowNcell1", "rowNcell2", ... "rowNcellN" ]
				//		],
				//		[
				//			"header1",
				//			"header2",
				//			...
				//			"headerN" ] // optional
				//	]
				//	OR
				//	return [ total_rows, $rows (jQuery object; optional), headers (array; optional) ]
				ajaxProcessing: function(data){
					if (!data || !data.hasOwnProperty('rows')) return;
					
					var r, row, c, d = data.rows,
					//	total number of rows (required)
					total = data.total_rows,
					//	array of header names (optional)
					headers = data.headers,
					//	all rows: array of arrays; each internal array has the table cell data for that row
					rows = [];
					//	process all the rows rows
					for(r in d){
						//	new row array
						row = []; 
						//	process all the cells
						for ( c in d[r] ) {
							// If the cellname is a string add each table cell data to row array
							if (typeof(c) === "string"){
								row.push(d[r][c]); 
							}
						}
						// add new row array to rows array
						rows.push(row);						
					}
					//	in version 2.10, you can optionally return $(rows) a set of table rows within a jQuery object
					return [ total, rows, headers ];
				}
			}
		},
		
		instances:	false,

		location:	false,
		setLocation: function(){
			Amslib_Tablesorter.location = Amslib_Tablesorter.files.base.replace("__AMSLIB__",Amslib.locate());
		},
		
		files: {
			base: "__AMSLIB__/util/jquery.tablesorter/",
			
			theme:{
				normal:		"css/theme.default.css",
				blackice:	"css/theme.black-ice.css",
				blue:		"css/theme.blue.css",
				bootstrap:	"css/theme.bootstrap.css",
				dark:		"css/theme.dark.css",
				dropbox:	"css/theme.dropbox.css",
				green:		"css/theme.green.css",
				grey:		"css/theme.grey.css",
				ice:		"css/theme.ice.css",
				juimal:		"css/theme.jui.css"
			},
			
			css:{
				pager:		"pager/jquery.tablesorter.pager.css"
			},
			
			js:{
				ts:			"js/jquery.tablesorter.js",
				widgets:	"js/jquery.tablesorter.widgets.js",
				pager:		"pager/jquery.tablesorter.pager.js"
			},
			
			get: function(type,name){
				try{
					var t = Amslib_Tablesorter.files[type];
					
					return Amslib_Tablesorter.location + (name ? t[name] : t);
				}catch(e){
					console.log("[ERROR]: Amslib_Tablesorter(inst).files.get, file not found",type,name);
				}
				
				return "";
			}
		},
		
		datakey: {
			pager:			"tablesorter-pager",
			totalrows:		"tablesorter-totalrows",
			length:			"tablesorter-length",
			widgets:		"tablesorter-widgets",
			ajax:			"tablesorter-ajax",
			ajax_auto:		"tablesorter-ajax-autoload",
			pager_format:	"tablesorter-pager-format"
		}
	},
	
	constructor: function(parent,options)
	{
		var c = Amslib_Tablesorter,
			o = c.options,
			d = c.datakey;
		
		c.Super.call(this,parent,o.amslibName);
		
		this.options = $.extend(true, {}, o, options);
		
		var widgets	= this.parent.data(d.widgets),
			ajaxUrl	= this.parent.data(d.ajax),
			pager	= this.parent.data(d.pager);
		
		var f = ajaxUrl ? this.options.filter.ajax : this.options.filter.non_ajax;
		this.options.tablesorter.widgetOptions = $.extend(true,{},f);
		
		if(widgets && widgets.length){
			this.options.tablesorter.widgets = widgets.split(",");
		}
		
		this.parent	= this.parent;
		this.ts		= this.parent.tablesorter(this.options.tablesorter);
		
		this.on("update",$.proxy(this,"update"));
	
		if(ajaxUrl) this.setupAjax(ajaxUrl);
	
		if($(pager).length) this.setupPager(pager);
	},
	
	setupPager: function(pager)
	{
		var s = Amslib_Tablesorter,
			f = s.files.get,
			t = this,
			o = t.options,
			d = s.datakey;

		Amslib.css.load(f("css","pager"));
		
		Amslib.js.load("tablesorter.pager",f("js","pager"),function(){
			var length = t.parent.data(d.length);
			if(length) o.pager.size = length;

			var totalrows = t.parent.data(d.totalrows);
			if(totalrows){
				o.pager.totalRows		= totalrows;
				o.pager.filteredRows	= o.pager.totalRows;
			}
			
			if(totalrows && length){
				o.pager.totalPages		= Math.ceil(o.pager.totalRows/o.pager.size);
				o.pager.filteredPages	= o.pager.totalPages;
			}
			
			var format = t.parent.data(d.pager_format);
			if(format) o.pager.output = format;
			
			o.pager.container	= pager;
			o.pager.cssGoto		= pager+" "+o.pager.cssGoto;
			t.ts.tablesorterPager(o.pager);	
		});
	},
	
	setupAjax: function(ajaxUrl)
	{
		if(typeof(ajaxUrl) != "string") return;
		
		//	If there is no query string, append the default one, this is so you know the information in the webservice
		//	any important information that the pager webservice can use
		if(ajaxUrl.indexOf("?") == -1){
			ajaxUrl = ajaxUrl+"?page={page}&size={size}&{sortList:col}&{filterList:fcol}";
		}
		
		this.options.ajaxPager.ajaxUrl = ajaxUrl;
		
		if(this.parent.data(Amslib_Tablesorter.datakey.ajax_auto) == false){
			this.options.ajaxPager.processAjaxOnInit = false;
		}
		
		$.extend(true, this.options.pager, this.options.ajaxPager);
	},
	
	addRow: function(row)
	{
		this.parent.trigger("addRows.tablesorter",[row]);
	},
	
	update: function()
	{	
		this.parent.trigger("update.tablesorter");
	}
});

$(document).ready(Amslib_Tablesorter.autoload);