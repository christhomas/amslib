/**
 * Usage instructions:
 * 
 * Include in your webpage, Amslib_Google_Analytics.js?tracker_id=<your id>,domain_name=<your domain>
 * 
 * Example: Amslib_Google_Analytics.js?id=U-12312-1&domain=antimatter-studios.com
 */
var script	=	"Amslib_Google_Analytics.js";
var _gaq	=	_gaq || [];

if(Amslib == undefined || window.exports == undefined) throw(script+": requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	var p = Amslib.getQuery($("script[src*='"+script+"']").attr("src"));
	
	if(p && p["id"]){
		(function(){ var u="//"+("https:"==location.protocol?"ssl":"www")+".google-analytics.com/ga.js";
		_gaq.push(['_setAccount',		p["id"]					]);
		_gaq.push(['_setDomainName',	"."+location.hostname	]);
		_gaq.push(['_setAllowLinker',	true					]);
		_gaq.push(['_setAllowHash',		false					]);
		_gaq.push(['_trackPageview'								]);
		var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript'; g.defer=true; g.async=true; g.src=u;
		s.parentNode.insertBefore(g,s); })();		
	}else{
		throw("You must specify a tracker id for google analytics to function correctly");
	}
}