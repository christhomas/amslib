Amslib_Dynamic_Grid = Class.create(
{
	child:	false,
	list:	false,
	
	initialize: function(parent,child)
	{
		this.child	=	child;
		this.list	=	parent.select(this.child);
		
		this.resize();
		
		Event.observe(document.onresize ? document : window, "resize",this.resize.bind(this));
	},
	
	resize: function()
	{
		var element = this.list.first();
		
		do{
			var row = this.list.findAll(function(f){
				return (new Element.Layout(f).get("top")) == (new Element.Layout(element).get("top"));
			});
			
			if(row.length){
				var max = row.max(function(e){
					return (new Element.Layout(e).get("height"));
				});
				
				row.each(function(e){
					var h = new Element.Layout(e).get("height");
					var m = (max+1) - h;
					
					if(m > 0) e.setStyle({marginBottom:m+"px"});
				});
				
				element = row.last().next(this.child);
			}
		}while(element);
	}
});