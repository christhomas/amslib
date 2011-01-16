if(Amslib.UI == "undefined")
	throw "Amslib.UI.Slider requires Amslib.UI to be loaded.";

Amslib.UI.Slider = Class.create(Amslib.UI,
{	
	track:			false,
	handle:			false,
	position:		false,
	axis:			false,
	updateMethod:	false,
	
	initialize: function($super,track,handle,options){
		$super();
		
		this.track	=	track;
		this.handle	=	handle;
		Object.extend(this,options || {});
		
		this.updateMethod = (this.axis == "veritcal") ? this.updateVertical : this.updateHorizontal;
	},
	
	startDrag: function(event)
	{
		$(document.body).observe("mouseup",this.stopDrag.bindAsEventListener(this));
		$(document.body).observe("mousemove",this.updateMethod.bindAsEventListener(this));
		
		event.stop();
		return false;
	},
	
	stopDrag: function(event)
	{
		$(document.body).stopObserving("mouseup");
		$(document.body).stopObserving("mousemove");
		
		event.stop();
		return false;
	},
	
	updateHorizontal: function(event)
	{
		//	TODO: Write functionality
	},
	
	updateVertical: function(event)
	{
		//	Obtain the mouse vertical position
		var y = Event.pointerY(event);
		
		//	Calculate the position of the handle, restricted to the track area
		var p = this.parent.cumulativeOffset();
		var h = this.handle.getDimensions().height;
		
		// FIXME: the -2 calculating 'm' is hardcoded from the margin
		var m = this.track.getDimensions().height - h;
		
		p.top = (y-p.top) - h/2;
		if(p.top < 0) p.top = 0;
		if(p.top > m) p.top = m;
		this.handle.setStyle({top: p.top+"px"});
		
		this.position = this.handle.positionedOffset().top / m;
		
		this.callObserver("change",this.position, p.top);
		
		event.stop();
		return false;
	},
	
	setPosition: function(pos)
	{
		//	we should require the units here also so we dont have to guess
	}
});

Amslib.UI.Slider.config = {
	css: {
		parent:		"amslib_ui_scrollbar_parent", 
		track:		"amslib_ui_scrollbar_track",
		handle:		"amslib_ui_scrollbar_handle"
	}
}