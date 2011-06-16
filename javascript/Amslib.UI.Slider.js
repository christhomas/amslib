if(typeof(Amslib) == "undefined" || typeof(Amslib.UI) == "undefined")
	throw "Amslib.UI.Slider requires Amslib.UI to be loaded.";

//	NOTE: Why is there Amslib.UI.Slider and Amslib.UI.ScrollBar ???
Amslib.UI.Slider = Class.create(Amslib.UI,
{	
	track:			false,
	handle:			false,
	position:		false,
	axis:			false,
	axisKey:		false,
	updateMethod:	false,
	
	initialize: function($super,track,handle,options){
		$super(track);
		
		this.track	=	track;
		this.handle	=	handle;
		Object.extend(this,options || {});
		
		this.updateMethod	=	(this.axis == "vertical") ? this.updateVertical : this.updateHorizontal;
		this.axisKey		=	(this.axis == "vertical") ? "top" : "left";
		
		this.handle.observe("mousedown",this.startDrag.bindAsEventListener(this));
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
		//		Obtain the mouse vertical position
		var x = Event.pointerX(event);
		
		//	Calculate the position of the handle, restricted to the track area
		var p = this.parent.cumulativeOffset();
		var w = this.handle.getDimensions().width;
		
		var m = this.track.getDimensions().width;
		
		p.left = (x-p.left) - w/2;
		if(p.left < 0) p.left = 0;
		if(p.left > m) p.left = m;
		this.handle.setStyle({left: p.left+"px"});
		
		this.position = this.handle.positionedOffset().left / m;
		
		this.callObserver("change",this.position, p.left);
		
		event.stop();
		return false;
	},
	
	updateVertical: function(event)
	{
		//	Obtain the mouse vertical position
		var y = Event.pointerY(event);
		
		//	Calculate the position of the handle, restricted to the track area
		var p = this.parent.cumulativeOffset();
		var h = this.handle.getDimensions().height;
		
		var m = this.track.getDimensions().height;
		
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
	},
	
	setPercent: function(percent)
	{
		//	NOTE: Internet Explorer didnt like that sometimes this number was NaN
		if(!isNaN(percent)){
			s = {};
			s[this.axisKey] = percent+"%";
			
			this.handle.setStyle(s);	
		}
	}
});

Amslib.UI.Slider.config = {
	//	NOTE: These are just copied from the Scrollbar object, we need to customise them
	css: {
		parent:		"amslib_ui_scrollbar_parent", 
		track:		"amslib_ui_scrollbar_track",
		handle:		"amslib_ui_scrollbar_handle"
	}
};