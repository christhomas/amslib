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
var script	=	"Amslib_Piwik.js";
var _paq	=	_paq || [];

if(Amslib == undefined || window.exports == undefined) throw(script+": requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	var p = Amslib.getQuery($("script[src*='"+script+"']").attr("src"));
	
	if(p && p["id"]){
		(function(){ var u="//analytics.antimatter-studios.com/";
		_paq.push(['setSiteId', 	p["id"]			]);
		_paq.push(['setTrackerUrl', u+'piwik.php'	]);
		_paq.push(['trackPageView'					]);
		_paq.push(['enableLinkTracking'				]);
		var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript'; g.defer=true; g.async=true; g.src=u+'piwik.js';
		s.parentNode.insertBefore(g,s); })();		
	}else{
		throw("You must specify a site id for piwik to function correctly");
	}
};