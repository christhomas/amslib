Amslib_Auto_Column = Class.create(
{
	parent:		false,
	source:		false,
	columns:	false,
	
	initialize: function(parent)
	{
		this.parent		=	parent;
		this.source		=	this.parent.down(Amslib_Auto_Column.config.source);
		this.columns	=	this.parent.select(Amslib_Auto_Column.config.column);
		
		Event.observe(window,"resize",this.process.bind(this));
		
		this.process();
	},
	
	process: function()
	{
		var p = this;
		
		this.columns.each(function(c){
			var maxHeight	=	c.up().offsetHeight;
			var nextColumn	=	c.next(Amslib_Auto_Column.config.column)
			
			c.childElements.reverse().each(function(node){
				this.split(node,maxHeight);
			});
		}.bind(this));
	},
	
	split: function(node,maxHeight)
	{
		if(node.nodeType == 3) return;
		
		if(node.cumulativeOffset().top > maxHeight){
			if(nextColumn){
				nextColumn.insert({top:node.remove()});
			}else{
				var overflow = p.parent.retrieve("overflow_cache") || new Array();
				overflow.splice(0,0,node);
				p.parent.store("overflow_cache",overflow);
			}
		}
	}
});

Amslib_Auto_Column.config = {
	source:	".source",
	column: ".column"
}

Amslib_Auto_Column.autoload = function()
{
	$$(".amslib_auto_column.amslib_autoload").each(function(c){
		var column = new Amslib_Auto_Column(c);
	});
}

Event.observe(window,"load",Amslib_Auto_Column.autoload);