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