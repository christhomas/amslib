/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_JQuery_UI.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	var selected = Amslib.getQuery("theme",$("script[src*='Amslib_JQuery_UI.js']").attr("src"));

	var theme = {
		"smoothness":	amslib+"/css/jqueryui/smoothness/jquery-ui-1.8.14.custom.css",
		"Aristo":		amslib+"/css/jqueryui/Aristo/Aristo.css"
	}
	
	if(!theme[selected]) selected = "smoothness";

	Amslib.loadCSS(theme[selected]);
	Amslib.loadJS("jqueryui",amslib+"/js/jquery-ui-1.8.14.custom.min.js");
};