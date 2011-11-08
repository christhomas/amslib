/**
This script will automatically include all the other resources without any fuss
*/

if(Amslib == undefined || window.exports == undefined) throw("Amslib_CKEditor.js: requires amslib/my.common to be loaded first");

var path = Amslib.getPath("/util/Amslib_CKEditor.js");

if(path){
	Amslib.loader.ckeditor			=	require(path+"/util/ckeditor/ckeditor.js");
	Amslib.loader.ckeditor_jquery	=	require(path+"/util/ckeditor/adapters/jquery.js");
	
	scope.ready(function(){
		CKEDITOR.config.toolbar_CKeditor_Config_1 = Amslib_CKEditor[0];
		CKEDITOR.config.toolbar_CKeditor_Config_2 = Amslib_CKEditor[1];
		CKEDITOR.config.toolbar_CKeditor_Config_3 = Amslib_CKEditor[2];
		
		$(".ckeditor_noconfig").ckeditor();
		//	These are still here for compatibility with old code until they can be updated
		$(".ckeditor_simple_1").ckeditor(function(){},{toolbar:"CKeditor_Config_1"});
		$(".ckeditor_simple_2").ckeditor(function(){},{toolbar:"CKeditor_Config_2"});
		//	These are the new way to apply a configuration
		$(".ckeditor_config_1").ckeditor(function(){},{toolbar:"CKeditor_Config_1"});
		$(".ckeditor_config_2").ckeditor(function(){},{toolbar:"CKeditor_Config_2"});
		$(".ckeditor_config_3").ckeditor(function(){},{toolbar:"CKeditor_Config_3"});
	},Amslib.loader.ckeditor,Amslib.loader.ckeditor_jquery);
}

Amslib_CKEditor = [
	[
		['Styles','Format'],
		['Bold','Italic','Strike'],
		['NumberedList','BulletedList'],
		['Cut','Copy','Paste','PasteFromWord'],
		['Link','Unlink','JustifyLeft','JustifyCenter','JustifyBlock']
	],
	
	[
		['Styles','Format','Font','FontSize'],
		['Bold','Italic','Strike'],
		['Cut','Copy','Paste','PasteFromWord'],
		['Link','Unlink','JustifyLeft','JustifyCenter','JustifyBlock']
	],
	
	[
		['Styles','Format','Font','FontSize'],
		['Bold','Italic','Strike'],
		['NumberedList','BulletedList'],
		['Cut','Copy','Paste','PasteFromWord'],
		['Link','Unlink','JustifyLeft','JustifyCenter','JustifyBlock']
	]                 
];