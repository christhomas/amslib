var Amslib_Accordion = my.Amslib_Accordion = my.Class(
{
	STATIC: {
		autoload: function(){
			$(Amslib_Accordion.options.src).each(function(){
				new Amslib_Accordion($(this));
			})
		},
		
		options: {
			src: ".amslib_accordion_trigger",
			dst: ".amslib_accordion_target",
			open: "open",
			time: 1000
		}
	},
	
	constructor: function(parent)
	{
		var p = $(parent);
		var d = Amslib_Accordion.options.dst;
		var t = Amslib_Accordion.options.time;
		var o = Amslib_Accordion.options.open;
		
		p.bind("click",function(){
			l = p.next(d);
			p.removeClass(o);
			
			if(l.hasClass(o)){
				l.slideUp().removeClass(o);
			}else{
				p.siblings(d+"."+o).slideUp().removeClass(o);
				p.addClass(o);
				l.slideDown(t).addClass(o);
			}
		});
	}
});

$(document).ready(Amslib_Accordion.autoload);
 