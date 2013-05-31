/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *     
 *******************************************************************************/

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
	
	var p = Amslib.getQuery($("script[src*='Amslib_JQPlot.js']").attr("src"));
	if(p["plugin[]"] && (p=p["plugin[]"])) Amslib.hasJS("jqplot",function(){
		$(p).each(function(k,v){
			Amslib.loadJS("jqplot."+v,amslib+"/util/jqplot/plugins/jqplot."+v+".min.js");
		});
	});
};