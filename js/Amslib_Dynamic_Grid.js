var Amslib_Dynamic_Grid = my.Amslib_Dynamic_Grid = my.Class(
{
	list:	false,
	
	STATIC: {
		autoload: function(){
			new Amslib_Dynamic_Grid($(Amslib_Dynamic_Grid.options.parent+".amslib_autoload"));
		},
		
		options: {
			parent:	".amslib_dynamic_grid",
			child:	".amslib_dynamic_grid_child"
		}
	},
	
	constructor: function(parent)
	{
		this.list	=	$(Amslib_Dynamic_Grid.options.child,parent);
		
		this.resize();
		
		$(document.onresize ? document : window).bind("resize",$.proxy(this,"resize"));
	},
	
	resize: function()
	{
		if(this.list.length == 0) return;
		
		var element = this.list.first();
		
		while(element.length){
			var row = this.list.map(function(f){
				if($(this).position().top == element.position().top) return this;
			});
			
			if(row.length){
				var max = 0;
				row.each(function(){ 
					if((h=$(this).outerHeight()) > max) max = h;
					$(this).attr("data-calc-height",h);
				});
				
				row.each(function(){
					var h = $(this).outerHeight();
					var m = max - h;
					
					if(m > 0) $(this).css("marginBottom",m+"px");
				});
				
				element = row.last().next(Amslib_Dynamic_Grid.options.child);
			}else{
				element = element.next(Amslib_Dynamic_Grid.options.child);
			}
		}
	}
});

$(document).ready(Amslib_Dynamic_Grid.autoload);