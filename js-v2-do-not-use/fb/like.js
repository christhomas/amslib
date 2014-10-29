function getAppId(callback)
{
	var js = /amslib\/fb\/like\.js(\?.*)?$/;
	
	$("head script[src]").each(function(){
		var src = this.src.match(js);
		
		if(src) callback(this.src.match(/\?.*appId=([a-zA-Z0-9\-]*)/));
	});
}

$(document).ready(function(){
	$(document.body).append("<div id='fb-root'></div>");
	
	getAppId(function(params){
		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) {return;}
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId="+params[1];
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
	});
});