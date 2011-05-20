Amslib_Column_Slider = new Class.create(Amslib,
{
	slider:			false,
	columnWidth:	false,
	minSlide:		0,
	maxSlide:		false,
	columnNumber:	0,
	
	initialize: function($super,parent){
		$super(parent,"Amslib_Column_Slider");
		
		this.slider			=	this.parent.down(".slider");
		this.mutex			=	false;
		
		var column			=	this.parent.down(".column");
		
		if(!column) return;
		
		var layout			=	new Element.Layout(column);
		this.columnWidth	=	layout.get("margin-box-width");
		
		this.setColumnNumber();
		
		//	Reset the slider position just in case it's not inside the slide boundaries
		this.correctPosition();
	},

	slidePrev: function()
	{
		var position = this.slider.positionedOffset().left + this.columnWidth;
		
		this.slideToPx(position,this.columnWidth);
	},
	
	slideNext: function()
	{
		var position = this.slider.positionedOffset().left - this.columnWidth;
		
		this.slideToPx(position,-this.columnWidth);
	},
	
	slideToColumn: function(column)
	{
		var wanted = this.slider.select(".column:nth-child("+(column+1)+")").first();
		
		var current = this.getColumnNumber()-1;
		
		var left = -(wanted.positionedOffset().left);
		var offset = (current-column) * this.columnWidth;
		
		this.slideToPx(left,offset);
	},
	
	/*	FIXME:	The parameters here left,offset made sense when I wrote this code, but now I'm looking
	 * 			at it, I can't figure out why I called them left,offset, so I need to clean this up
	 * 			to give them better names so when I'm looking 2 weeks later, I know what I've done and why
	*/
	
	//	NOTE: I think left means: the position you want, finally
	//	NOTE: I think offset means, the offset from your current position to the position you want
	slideToPx: function(left,offset)
	{	
		var tl = this.correctPosition(left);
		if(tl !== false) left = tl;

		this.callObserver("move",left,this.minSlide,this.maxSlide);
		
		if(this.mutex) return;
		this.mutex = true;
		
		this.slider.visualEffect("Move",{
			x:	offset,
			y:	0,
			duration: 0.75,
			afterFinish:function(){ 
				this.mutex = false;
				this.setColumnNumber();
			}.bind(this)
		});	
	},
	
	getColumnNumber: function()
	{
		return this.slider.select(".column")
							.invoke("positionedOffset")
							.pluck("left")
							.indexOf(-this.slider.positionedOffset().left) + 1;
	},
	
	setColumnNumber: function()
	{
		this.columnNumber = this.getColumnNumber();

		this.callObserver("get-column-number",this.columnNumber);
	},
	
	/**
	 * Correct any mistakes in the position of the slider in relation to it's parent
	 */
	correctPosition: function(newLeft)
	{
		var left		=	newLeft || this.slider.positionedOffset().left;
		this.maxSlide	=	-(this.slider.getDimensions().width - this.parent.getDimensions().width);
		
		var corrected = false;
		
		if(this.maxSlide > 0) this.maxSlide = 0;
		
		if(left > this.minSlide)	corrected = this.minSlide;
		if(left < this.maxSlide)	corrected = this.maxSlide;
		
		//	NOTE:	Special case if the maxSlide value is positive, it means the slider is 
		//			less wide than the parent which means we have to just reset it's position to left:0
		//	NOTE:	I think this is now impossible to happen
		if(corrected !== false && this.maxSlide > 0) corrected = 0;
		
		return corrected;
	},
	
	/**
	 * Obtain a corrected position and implement it, this is used when the contents of the slider
	 * may have changed and needs to have it's positioned verified and updated
	 */
	updatePosition: function()
	{
		var left = this.correctPosition();
		
		if(left !== false) this.slider.setStyle({left: left+"px"});
		
		var l = new Element.Layout(this.slider);
		
		this.callObserver("move",l.get("left"),this.minSlide,this.maxSlide);
	},
	
	//	DEPRECATED METHODS:
	prevSlide: function(){ this.slidePrev(); },
	nextSlide: function(){ this.slideNext(); }
});

Amslib_Column_Slider.autoload = function()
{
	$$(".amslib_column_slider.amslib_autoload").each(function(b){
		new Amslib_Column_Slider(b);
	});
}

/**
 * A specialised method to bind a column slider to a multi column object
 * 
 * It basically reduces all this code to a one-liner and connects two objects which
 * are normally associated with each other, but I dont want to keep duplicating this code
 * everywhere.
 */
Amslib_Column_Slider.bindMultiColumn = function(columnObject,multiColumnClassTag,multiColumnName)
{
	if(!columnObject) return false;
	
	//	If the multi column object changes the number of columns, tell the column slider object
	var changeColumns = function(src,dst)
	{
		src.object.observe("change-columns",dst.object.updatePosition.bind(dst.object));
	}
	
	multiColumnClassTag	=	multiColumnClassTag || ".amslib_multi_column";
	multiColumnName		=	multiColumnName || "Amslib_Multi_Column";
	
	var dstNode = columnObject.getNode();
	var srcNode = dstNode.down(multiColumnClassTag);

	Amslib.bindObjects(	{name: multiColumnName,					node: srcNode},
						{name: columnObject.getInstanceName(),	node: dstNode},
						changeColumns);
	
	return true;
}

Event.observe(window,"load",Amslib_Column_Slider.autoload);
