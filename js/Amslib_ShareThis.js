/*
//	I found this here: http://jquery-howto.blogspot.com/2009/09/get-url-parameters-values-with-jquery.html
//	I need to read through and make this a component so I can reuse it in this script AND the google analytics script

$.extend({
  getUrlVars: function(){
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
    }
    return vars;
  },
  getUrlVar: function(name){
    return $.getUrlVars()[name];
  }
});
*/
function getShareThis(callback)
{
	//	Copied from how scriptaculous does it's "query string" thing
	var js = /Amslib_ShareThis\.js(\?.*)?$/;
	$("head script[src]").each(function(){
		var src = this.src.match(js);
		
		if(src) callback(this.src.match(/\?.*publisher=([a-zA-Z0-9\-]*)/));
	});
}

var switchTo5x=true;

$(document).ready(function(){
	getShareThis(function(params){
		$.getScript("http://w.sharethis.com/button/buttons.js",function(){
			stLight.options({publisher:params[1]});
		});
	});
});