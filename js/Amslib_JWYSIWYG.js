/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_JWYSIWYG.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	var jwysiwyg = amslib+"/util/jwysiwyg/";
	
	Amslib.loadCSS(jwysiwyg+"jquery.wysiwyg.css");
	Amslib.loadJS("jwysiwyg",jwysiwyg+"jquery.wysiwyg.js",function(){
		Amslib.loadJS("jwysiwyg.link",jwysiwyg+"controls/wysiwyg.link.js");
	});
};
