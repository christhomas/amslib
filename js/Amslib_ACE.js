/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_ACE.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	Amslib.loadJS("ace",amslib+"/util/ace/ace-noconflict.js",function(){
		Amslib.loadJS("ace/html",amslib+"/util/ace/mode-html-noconflict.js");
	});
}
