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
	
	prevSlide: function()
	{
		var position = this.slider.positionedOffset().left + this.columnWidth;
		
		this.slideToPx(position,this.columnWidth);
	},
	
	nextSlide: function()
	{
		var position = this.slider.positionedOffset().left - this.columnWidth;
		
		this.slideToPx(position,-this.columnWidth);
	},
	
	slideToPx: function(left,offset)
	{
		if(this.correctPosition(left) === false){
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
		}
	},
	
	setColumnNumber: function()
	{
		this.columnNumber = this.slider
								.select(".column")
								.invoke("positionedOffset")
								.pluck("left")
								.indexOf(-this.slider.positionedOffset().left) + 1;

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
		
		if(left > this.minSlide)	corrected = this.minSlide;
		if(left < this.maxSlide)	corrected = this.maxSlide;
		
		//	NOTE:	Special case if the maxSlide value is positive, it means the slider is 
		//			less wide than the parent which means we have to just reset it's position to left:0
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
	}
});

Amslib_Column_Slider.autoload = function()
{
	$$(".amslib_column_slider.amslib_autoload").each(function(b){
		new Amslib_Column_Slider(b);
	});
}

Event.observe(window,"load",Amslib_Column_Slider.autoload);
