/**
This script will automatically include all the other resources without any fuss
*/
var script = "Amslib_Flot.js";

if(Amslib == undefined || window.exports == undefined) throw(script+": requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	//	FIXME: we still need to resolve the missing IE excanvas.js functionality
	//	NOTE:	if in the head we output a set of conditional comments which set a value 
	//			which we could recognise, we could trigger the loading of the scripts based 
	//			on whether it's present or not?
	//if(Amslib.versionIE && Amslib.versionIE < 9) $.getScript(src[1]+"/util/jqplot/excanvas.js");
	Amslib.loadJS("flot",amslib+"/util/flot/jquery.flot.js",function(){
		var p = Amslib.getQuery($("script[src*='"+script+"']").attr("src"));
		
		if(p["plugin[]"] && (p=p["plugin[]"])) $(p).each(function(k,v){
			Amslib.loadJS("flot."+v,amslib+"/util/flot/jquery.flot."+v+".js");
		});
	});
};