var Amslib_Dynamic_Grid = my.Amslib_Dynamic_Grid = my.Class(
{
	child:	false,
	list:	false,
	
	STATIC: {
		autoload: function(){
			new Amslib_Dynamic_Grid(
				$(Amslib_Dynamic_Grid.options.parent+".amslib_autoload"),
				$(Amslib_Dynamic_Grid.options.child)
			);
		},
		
		options: {
			parent:	".amslib_dynamic_grid",
			child:	".amslib_dynamic_grid_child"
		}
	},
	
	constructor: function(parent,child)
	{
		this.child	=	child;
		this.list	=	parent.find(this.child);
		
		this.resize();
		
		$(document.onresize ? document : window).bind("resize",$.proxy(this,"resize"));
	},
	
	resize: function()
	{
		var element = this.list.first();
		
		do{
			var row = this.list.find(function(f){
				return f.position().top == element.position().top
			});
			
			if(row.length){
				var max = 0;
				row.each(function(){ if((h=$(this).height()) > max) max = h;});
				
				row.each(function(){
					var h = $(this).height();
					var m = (max+1) - h;
					
					if(m > 0) e.css("marginBottom",m+"px");
				});
				
				element = row.last().next(this.child);
			}
		}while(element);
	}
});

$(document).ready(Amslib_Dynamic_Grid.autoload);