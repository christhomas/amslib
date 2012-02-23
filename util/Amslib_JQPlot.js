/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_JQPlot.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	//	FIXME: we still need to resolve the missing IE excanvas.js functionality
	//	NOTE:	if in the head we output a set of conditional comments which set a value 
	//			which we could recognise, we could trigger the loading of the scripts based 
	//			on whether it's present or not?
	//if(Amslib.versionIE && Amslib.versionIE < 9) $.getScript(src[1]+"/util/jqplot/excanvas.js");
	Amslib.loadCSS(amslib+"/util/jqplot/jquery.jqplot.css");
	Amslib.loadJS("jqplot",amslib+"/util/jqplot/jquery.jqplot.min.js");
};