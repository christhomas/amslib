CKEDITOR.config.toolbar_CKeditor_Config_1 =
[
	['Styles','Format'],
	['Bold','Italic','Strike'],
	['NumberedList','BulletedList'],
	['Cut','Copy','Paste','PasteFromWord'],
	['Link','Unlink','JustifyLeft','JustifyBlock']
];

CKEDITOR.config.toolbar_CKeditor_Config_2 =
[
	['Styles','Format','Font','FontSize'],
	['Bold','Italic','Strike'],
	['Cut','Copy','Paste','PasteFromWord'],
	['Link','Unlink','JustifyLeft','JustifyBlock']
];

CKEDITOR.config.toolbar_CKeditor_Config_3 =
[
	['Styles','Format','Font','FontSize'],
	['Bold','Italic','Strike'],
	['NumberedList','BulletedList'],
	['Cut','Copy','Paste','PasteFromWord'],
	['Link','Unlink','JustifyLeft','JustifyBlock']
];

$(document).ready(function(){
	$(".ckeditor_noconfig").ckeditor();
	//	These are still here for compatibility with old code until they can be updated
	$(".ckeditor_simple_1").ckeditor(function(){},{toolbar:"CKeditor_Config_1"});
	$(".ckeditor_simple_2").ckeditor(function(){},{toolbar:"CKeditor_Config_2"});
	//	These are the new way to apply a configuration
	$(".ckeditor_config_1").ckeditor(function(){},{toolbar:"CKeditor_Config_1"});
	$(".ckeditor_config_2").ckeditor(function(){},{toolbar:"CKeditor_Config_2"});
	$(".ckeditor_config_3").ckeditor(function(){},{toolbar:"CKeditor_Config_3"});
});