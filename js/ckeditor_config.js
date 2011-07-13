CKEDITOR.config.toolbar_Simple_1 =
[
	['Styles','Format'],
	['Bold','Italic','Strike'],
	['NumberedList','BulletedList'],
	['Cut','Copy','Paste','PasteFromWord'],
	['Link','Unlink','JustifyLeft','JustifyBlock']
];

CKEDITOR.config.toolbar_Simple_2 =
[
	['Styles','Format','Font','FontSize'],
	['Bold','Italic','Strike'],
	['Cut','Copy','Paste','PasteFromWord'],
	['Link','Unlink','JustifyLeft','JustifyBlock']
];

$(document).ready(function(){
	$(".ckeditor_simple_1").ckeditor(function(){},{toolbar:"Simple_1"});
	$(".ckeditor_simple_2").ckeditor(function(){},{toolbar:"Simple_2"});
});