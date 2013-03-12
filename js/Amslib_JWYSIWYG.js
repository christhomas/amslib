/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_JWYSIWYG.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	Amslib.loadCSS(amslib+"/util/jwysiwyg/jquery.wysiwyg.css");
	Amslib.loadJS("jquery.jwysiwyg",amslib+"/util/jwysiwyg/jquery.wysiwyg.js");
};
