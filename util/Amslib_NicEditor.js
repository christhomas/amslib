/**
	This script will automatically load the nicEditor and set the GIF path for the image sprites
	then it'll run through all the classes and load all the preconfigured setup's
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_NicEditor.js: requires amslib/my.common to be loaded first");

var amslib = Amslib.locate();

if(amslib){
	Amslib.loadJS("nicedit",amslib+"/util/nicEdit/nicEdit.js",function(){
		nicEditorConfig.iconsPath = amslib+"/util/nicEdit/nicEditorIcons.gif";
		var list = $(".nicedit");
		
		list.each(function(){
			if($(this).hasClass("simple1")){
				//	An example of how we could configure with different options, does nothing for now
				var p = new nicEditor({fullPanel : true}).panelInstance('myArea1',{hasPanel : true})
			}else{
				var p = new nicEditor().panelInstance(this);
			}
			
			$(this).data("nicedit",p);
		});
	});
};