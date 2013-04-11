/**
This script will automatically include all the other resources without any fuss
*/

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

var Amslib_DataTables = my.Amslib_DataTables = my.Class(my.Amslib,{
	STATIC: {
		autoload: function(){
			$(Amslib_DataTables.options.autoload).each(function(){
				new Amslib_DataTables(this);
			});
		},
		
		options: {
			autoload:			"[data-enable-amslib-datatables]",
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
		console.log(o);
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