/**
 * Usage instructions:
 * 
 * Include in your webpage, Amslib_Google_Analytics.js?tracker_id=<your id>,domain_name=<your domain>
 * 
 * Example: Amslib_Google_Analytics.js?tracker_id=U-12312-1,domain_name=antimatter-studios.com
 */
function getTrackerInfo(callback)
{
	//	Copied from how scriptaculous does it's "query string" thing
	var js = /Amslib_Google_Analytics\.js(\?.*)?$/;
	$("head script[src]").each(function(){
		var src = this.src.match(js);
		
		if(src) callback(this.src.match(/\?.*tracker_id=([a-zA-Z0-9\-]*),domain_name=([a-zA-Z0-9\.\-]*)/));
	});
}

var _gaq = _gaq || [];

$(document).ready(function(){
	getTrackerInfo(function(params){
		_gaq.push(['_setAccount',		params[1]		]);
		_gaq.push(['_setDomainName',	"."+params[2]	]);
		_gaq.push(['_setAllowLinker',	true			]);
		_gaq.push(['_setAllowHash',		false			]);
		_gaq.push(['_trackPageview']);
		
		var protocol	=	'https:' == document.location.protocol ? 'https://ssl' : 'http://www';
		var url			=	protocol+".google-analytics.com/ga.js";
		
		$.getScript(url);
	});
});