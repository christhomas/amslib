//	Well.......that was easy....erm...should I make this a class? 
$(document).ready(function(){
	var src=".amslib_accordion_trigger",dst=".amslib_accordion_target",o="open",time=1000;
	$(src).bind("click",function(){
		var t = $(this).next(dst);
		
		if(t.hasClass(o)) t.slideUp().removeClass(o);
		else{
			$(dst+"."+o).slideUp().removeClass(o);
			t.slideDown(time,function(){
				$('html, body').animate({ scrollTop: t.offset().top }, time);
			}).addClass(o);
		}
	});
});