/*
 *	Amslib_Tablesorter
 * 
 */
var Amslib_Tablesorter = my.Amslib_Tablesorter = my.Class(Amslib,
{
	STATIC: {
		autoload: function(){
			var s = Amslib_Tablesorter,
				f = s.files.get;
			
			s.setLocation();
			
			Amslib.css.load(f("theme","normal"));
			
			Amslib.js.loadSeq(
				["tablesorter",f("js","ts")],
				["tablesorter.widgets",f("js","widgets")]
			);
			
			Amslib.js.has("tablesorter","tablesorter.widgets",function(){
				s.instances = $(s.options.autoload);

				s.instances.each(function(){
					new s(this,{});
				});
			});
		},
		
		options:{
			amslibName:	"Amslib_Tablesorter",
			autoload: 	"[data-amslib-tablesorter='true']",
			
			//	Default options for the tablesorter
			tablesorter:{
				sortReset:	true,
				widgets:	["zebra"]
			},
			
			filter:{
				
			},
			
			pager:{
				//	target the pager markup - see the HTML block below
				container:	false,
				//	starting page of the pager (zero based index)
				page: 0,				
				//	Number of visible rows - default is 10
				size: 25,
				
				output:		'{startRow} - {endRow} / {filteredRows} ({totalRows})',
		
				//	Default css values for all the pager DOM nodes
				cssGoto:		"select",				//	select a particular page
				cssNext:		".next",				//	next page arrow
				cssPrev:		".prev",				//	previous page arrow
				cssFirst:		".first",				//	go to first page arrow
				cssLast:		".last",				//	go to last page arrow
				cssPageDisplay:	".display",				//	location of where the "output" is displayed
				cssPageSize:	".count",				//	page size selector - select dropdown that sets the "size" option
				cssErrorRow:	"tablesorter-errorRow",	//	error information row

				//	remove rows from the table to speed up the sort of large tables.
				//	setting this to false, only hides the non-visible rows; needed if 
				//	you plan to add/remove rows with the pager enabled.
				removeRows: false
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
					if (!data || data.hasOwnProperty('rows')) return;
					
					var r, row, c, d = data.rows,
					//	total number of rows (required)
					total = data.total_rows,
					//	array of header names (optional)
					headers = data.headers,
					//	all rows: array of arrays; each internal array has the table cell data for that row
					rows = [],
					//	len should match pager set size (c.size)
					len = d.length; 
					//	process all the rows rows
					for(r=0;r<len;r++){
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
			pager:		"amslib-tablesorter-pager",
			length:		"amslib-tablesorter-length",
			widgets:	"amslib-tablesorter-widgets",
			ajax:		"amslib-tablesorter-ajax",
			ajax_auto:	"amslib-tablesorter-ajax-autoload"
		}
	},
	
	constructor: function(parent,options)
	{
		var s = Amslib_Tablesorter,
			o = s.options,
			d = s.datakey;
		
		s.Super.call(this,parent,o.amslibName);
		
		this.options = $.extend(true, {}, o, options);
		
		var widgets = this.parent.data(d.widgets).split(",");
		if(widgets && widgets.length){
			this.options.tablesorter.widgets = widgets;
		}
		
		this.parent	= this.parent;
		this.ts		= this.parent.tablesorter(this.options.tablesorter);
	
		ajaxURL = this.parent.data(d.ajax);
		if(ajaxURL) this.setupAjax(ajaxURL);
		
		selector = this.parent.data(d.pager);
		if($(selector).length) this.setupPager(selector);
	},
	
	setupPager: function(selector)
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
			
			o.pager.container	= selector;
			o.pager.cssGoto		= selector+" "+o.pager.cssGoto;
			console.log(o.pager);
			t.ts.tablesorterPager(o.pager);	
		});
	},
	
	setupAjax: function(ajaxURL)
	{
		if(typeof(ajaxURL) != "string") return;
		
		this.options.ajaxPager.ajaxUrl = ajaxURL;
		
		var autoload = this.parent.data(Amslib_Tablesorter.datakey.ajax_auto);
		if(autoload == false){
			this.parent.on('pagerBeforeInitialized', $.proxy(this,"preventAjaxAutoload"));
		}
		
		$.extend(true, this.options.pager, this.options.ajaxPager);
	},
	
	preventAjaxAutoload: function(event,options)
	{
		var config	= event.currentTarget.config;
		var pager	= config.pager;
		
		options.page			= pager.page;			// current page
		options.size			= pager.size;			// current size
		options.totalPages		= pager.totalPages;		// total pages
		options.currentFilters	= pager.currentFilters;	// any filters

		this.parent.data('pagerLastPage', options.page);
		this.parent.data('pagerLastSize', options.size);
	
		options.last = {
			page:			options.page,
			size:			options.size,
			sortList:		(config.sortList || []).join(','),
			totalPages:		options.totalPages,
			currentFilters:	[]
		};
	}
});

$(document).ready(Amslib_Tablesorter.autoload);