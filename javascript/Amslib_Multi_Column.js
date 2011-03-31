Amslib_Multi_Column = Class.create(Amslib,
{
	container:		false,
	resizeNode:		false,
	resizeCallback:	false,
	columns:		false,
	
	initialize: function($super,parent)
	{
		$super(parent,"Amslib_Multi_Column");
		
		this.resizeNode		=	document.onresize ? document : window;
		this.resizeCallback	=	this.resize.bind(this);
		this.container		=	this.parent.up();
		
		this.findColumns();
		this.resize();
	},
	
	enableResizeEvent: function()
	{
		Event.observe(this.resizeNode,"resize",this.resizeCallback);
	},
	
	disableResizeEvent: function()
	{
		Event.stopObserving(this.resizeNode,"resize",this.resizeCallback);
	},
	
	findColumns: function()
	{
		this.columns = new Array();
		this.columns.push(this.parent);
		this.columns.concat(this.container.select(".amslib_multi_column_child"));
		
		this.linkColumns();
	},
	
	linkColumns: function()
	{
		for(a=0;a<this.columns.length;a++){
			var next = (a<this.columns.length-1) ? this.columns[a+1] : false;

			this.columns[a].store("link_column",next);
		}
	},
	
	resize: function()
	{
		this.disableResizeEvent();
		
		var maxLayout	=	this.container.getLayout();
		var maxHeight	=	maxLayout.get("height");
		
		this.container.cleanWhitespace();
		
		var column = this.columns.first();
		
		do{
			this.resizeColumn(column,maxHeight);
		}while(column = column.retrieve("link_column"));
		
		this.deleteEmptyColumn();
		
		this.enableResizeEvent();
	},
	
	getElementBottom: function(element)
	{
		if(element)
		{
			var layout = new Element.Layout(element);
			
			if(layout){
				return layout.get("top")+layout.get("height");
			}
		}
		
		return 0;
	},
	
	resizeColumn: function(column,maxHeight)
	{
		//	Find the bottom of the last element inside this column
		var bottom = this.getElementBottom(column.childElements().last());

		//	first determine which direction elements will go, TO this column, or FROM this column
		if(bottom > maxHeight){
			//	push elements into the next column
			var nextColumn = this.getNextColumn(column);
			
			if(nextColumn){
				var list = column.childElements().partition(function(element){
					var l = new Element.Layout(element);
					var t = l.get("top");
					var h = l.get("height");
					
					return (t+h >= maxHeight) ? true : false;
				}.bind(this)).first();
				
				list.reverse().each(function(element){
					nextColumn.insert({top:element.remove()});
				}.bind(this));	
			}else{
				//	TODO:	I think the idea here was to store content which doesnt fit
				//			into the last column in a variable which is kept in case we need it
			}
		}else{
			nextColumn = column;
			//	pull elements from the next column into this column
			while(nextColumn = nextColumn.retrieve("link_column")){
				//	loop through all the elements in the next column, seeing whether you can pull them or not
				nextColumn.childElements().each(function(c){
					var l = new Element.Layout(c);
					var t = l.get("top");
					var h = l.get("height");
					
					if((bottom+t+h) < maxHeight){
						column.insert(c.remove());
						bottom = this.getElementBottom(c);
					}
				}.bind(this));
			}
		}
	},
	
	deleteEmptyColumn: function()
	{
		var groups = this.columns.partition(function(c){
			return c.childElements().length ? true : false;
		});
		
		if(groups.last().length){
			this.columns = groups.first();
			groups.last().invoke("remove");
			this.linkColumns();
			
			this.callObserver("change-columns");
		}
	},
	
	getNextColumn: function(prev)
	{
		var next = prev.retrieve("link_column");
		
		if(!next){
			next = prev.cloneNode(false);
			//	We need to clear "id" attributes so they don't clash
			next.id = "";
			next.store("link_column",false);
			prev.store("link_column",next);
			
			this.columns.push(next);
			this.container.insert(next);
			
			this.callObserver("change-columns");
		}
		
		return next;
	}
});

Amslib_Multi_Column.autoload = function()
{
	$$(".amslib_multi_column").each(function(m){
		new Amslib_Multi_Column(m);
	});
}

Event.observe(window,"load",Amslib_Multi_Column.autoload);