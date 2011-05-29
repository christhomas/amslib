/**
 * Usage instructions:
 * 
 * 1)	Make a copy of this file in your website structure, /js/ or /javascript/ wherever you store your javascripts
 * 2)	Replace TRACKING_CODE_HERE with your tracking code from google
 * 3)	Replace DOMAIN_NAME_HERE with your domain name (remember, the starting dot is important!!!)
 * 			Examples, use these for a common example:
 * 						www.mywebsite.com		=> 	.mywebsite.com
 * 						www.crazywebsite.com	=> 	.crazywebsite.com
 * 4)	You're done.
 */
function getTrackerInfo()
{
	var info = false;
	
	//	Copied from how scriptaculous does it's "query string" thing
	var js = /Amslib_Google_Analytics\.js(\?.*)?$/;
    $$('head script[src]').findAll(function(s) {
      return s.src.match(js);
    }).each(function(s) {
    	info = s.src.match(/\?.*tracker_id=([a-zA-Z0-9\-]*),domain_name=([a-zA-Z0-9\.\-]*)/);
    });
    
    return info;
}

info = getTrackerInfo();

var _gaq = _gaq || [];
_gaq.push(['_setAccount',		info[1]		]);
_gaq.push(['_setDomainName',	"."+info[2]	]);
_gaq.push(['_setAllowLinker',	true		]);
_gaq.push(['_setAllowHash',		false		]);
_gaq.push(['_trackPageview']);

var protocol	=	'https:' == document.location.protocol ? 'https://ssl' : 'http://www';
var url			=	protocol+".google-analytics.com/ga.js";

if(typeof jQuery != "undefined")
{

	//	Load the jquery way of inserting the script
	$.ready(function(){
		$.getScript(url);
	});
	
}else if(typeof Prototype != "undefined"){

	//	Load the prototype way of inserting the script
	Event.observe(window,"load",function()
	{	
		var script = new Element("script",{
			type:	"text/javascript",
			async:	true,
			src:	url
		});
		
		$$("head").invoke("insert",script);
	});
	
};
