Amslib_Column_Slider = new Class.create(Amslib,
{
	parent:			false,
	slider:			false,
	columnWidth:	false,
	buttonPrev:		false,
	buttonNext:		false,
	minSlide:		0,
	maxSlide:		false,
	
	initialize: function($super,parent){
		$super(parent,"Amslib_Column_Slider");
		
		this.slider			=	parent.down(".slider");
		this.buttonPrev		=	parent.down("img.minus");
		this.buttonNext		=	parent.down("img.plus");
		this.mutex			=	false;
		
		var layout			=	new Element.Layout(this.parent.down(".column"));
		this.columnWidth	=	layout.get("width") + layout.get("padding-right");
		
		this.parent.select("img.button").invoke("observe","click",this.animate.bind(this));
		
		//	Reset the slider position just in case it's not inside the slide boundaries
		this.correctPosition();
	},
	
	/**
	 * Calculate the new position based on which button was pressed and if the position is validated
	 * as ok, go ahead and animate to that new location
	 */
	animate: function(event)
	{
		var width		=	this.slider.getDimensions().width / this.parent.select(".column").length;
		var movement	=	width * (event.element() == this.buttonNext ? -1 : 1);
		var newPos		=	this.slider.positionedOffset().left + movement;

		if(this.correctPosition(newPos) === false){
			this.updateButtons(newPos);
			
			if(this.mutex) return;
			this.mutex = true;
			
			this.slider.visualEffect("Move",{
				x:	movement,
				y:	0,
				duration: 0.75,
				afterFinish:function(){ 
					this.mutex = false; 
				}.bind(this)
			});			
		}
		
		event.stop();
		return false;
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

		this.updateButtons(l.get("left"));
	},
	
	/**
	 * Whether or not to show or hide the buttons which are in control of the sliding interface
	 */
	updateButtons: function(left)
	{
		if(left == this.minSlide) this.buttonPrev.fade();
		else this.buttonPrev.appear();
		
		if(left == this.maxSlide) this.buttonNext.fade();
		else this.buttonNext.appear();
	}
});

Amslib_Column_Slider.autoload = function()
{
	$$(".amslib_column_slider").each(function(b){
		new Amslib_Column_Slider(b);
	});
}

Event.observe(window,"load",Amslib_Column_Slider.autoload);
