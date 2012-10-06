/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_JQuery_UI.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	//	FIXME: It looks from the method functionality that these two parameters should be swapped over
	//	NOTE: also logically it makes more sense that they are reversed also.
	var theme = Amslib.getQuery("theme",Amslib.getJSPath("Amslib_JQuery_UI.js"));

	var themeList = {
		"smoothness":	amslib+"/css/jqueryui/smoothness/jquery-ui-1.8.14.custom.css",
		"aristo":		amslib+"/css/jqueryui/Aristo/Aristo.css"
	};
	
	if(!themeList[theme]) theme = "smoothness";

	Amslib.loadCSS(themeList[theme]);
	Amslib.loadJS("jquery.ui",amslib+"/util/jquery-ui-1.8.14.custom.min.js");
};