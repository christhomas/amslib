/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_JQuery_UI.js: requires amslib/my.common to be loaded first");

var path = Amslib.getPath("/util/Amslib_JQuery_UI.js");

if(path){
	//	TODO: make the 'theme' changable somehow
	Amslib.loadCSS(path+"/css/jqueryui/smoothness/jquery-ui-1.8.14.custom.css");
	Amslib.loader.jqueryui = require(path+"/js/jquery-ui-1.8.14.custom.min.js");
};