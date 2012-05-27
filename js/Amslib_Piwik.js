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