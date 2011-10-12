/**
	This script will automatically load the nicEditor and set the GIF path for the image sprites
	then it'll run through all the classes and load all the preconfigured setup's
*/
var defaultNicEditorGIFPath = false;

(function(){
	//	Copied from how scriptaculous does it's "query string" thing
	var js = /^(.*?)Amslib_NicEditor\.js$/;
	$("head script[src]").each(function(){
		var src = this.src.match(js);
		
		if(src){
			defaultNicEditorGIFPath = src[1]+"nicEditorIcons.gif";
			var nicEdit = src[1]+"nicEdit.js";
			$.getScript(nicEdit,function(){
				configNicEditor();
			});
		}
	});
})()

var configNicEditor = function(){
	var list = $(".nicedit");
	
	list.each(function(){
		if($(this).hasClass("simple1")){
			//	An example of how we could configure with different options, does nothing for now
		}else{
			new nicEditor().panelInstance(this);
		}
	});
};