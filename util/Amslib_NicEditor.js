/**
	This script will automatically load the nicEditor and set the GIF path for the image sprites
	then it'll run through all the classes and load all the preconfigured setup's
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_NicEditor.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	var defaultNicEditorGIFPath = amslib+"/util/nicEditorIcons.gif";
	
	Amslib.loadJS("nicedit",amslib+"/util/nicEdit.js",function(){
		var list = $(".nicedit");
		
		list.each(function(){
			if($(this).hasClass("simple1")){
				//	An example of how we could configure with different options, does nothing for now
			}else{
				new nicEditor().panelInstance(this);
			}
		});
	});
};