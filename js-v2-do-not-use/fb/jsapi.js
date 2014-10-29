function getAppId(callback)
{
	var js = /amslib\/js\/fb\/jsapi\.js(\?.*)?$/;
	
	$("head script[src]").each(function(){
		var src = this.src.match(js);
		
		if(src) callback(this.src.match(/\?.*appId=([a-zA-Z0-9\-]*)/));
	});
}

$(document).ready(function(){
	$(document.body).append("<div id='fb-root'></div>");
	
	getAppId(function(params){
		(function(id) {
			if($("facebook-jssdk").length == 0){
				var js = document.createElement("script"); 
				js.id = 'facebook-jssdk';
				js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId="+params[1];
				var fjs = document.getElementsByTagName("script")[0];
				fjs.parentNode.insertBefore(js, fjs);
			}
		}());
	});
});