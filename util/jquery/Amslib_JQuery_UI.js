/**
This script will automatically include all the other resources without any fuss
*/

(function(){
	//	Copied from how scriptaculous does it's "query string" thing
	var js = /^(.*?)\/util\/jquery\/Amslib_JQuery_UI\.js$/;
	$("head script[src]").each(function(){
		var src = this.src.match(js);
	
		if(src){
			$.getScript(src[1]+"/js/jquery-ui-1.8.14.custom.min.js",function(){
				$("head").append($("<link/>").attr({
					rel: "stylesheet",
					type: "text/css",
					//	TODO: make the 'theme' optional as part of this files "url"
					href: src[1]+"/css/jqueryui/smoothness/jquery-ui-1.8.14.custom.css"
				}));
			});
		}
	});
})();