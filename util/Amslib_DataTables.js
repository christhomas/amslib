/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_DataTables.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	var theme  = Amslib.getQuery("theme",$("script[src*='Amslib_DataTables.js']").attr("src"));
	
	var themeList = {
		"smooth":	amslib+"/util/jquery.dataTables/theme.dataTables.smooth.css"
	};
	
	if(!themeList[theme]) theme = "smooth";
	
	Amslib.loadCSS(themeList[theme]);
	Amslib.loadJS("jquery.dataTables",amslib+"/util/jquery.dataTables/jquery.dataTables.min.js");
};
