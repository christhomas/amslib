if(typeof(Amslib) == "undefined")
	throw "Amslib_ScrollBar requires Amslib to be loaded.";

Amslib_ScrollBar = Class.create(Amslib,
{
	container:	false,
	content:	false,
	handle:		false,
	track:		false,
	
	initialize: function($super,content,handle){
		$super(content);
		
		this.attach(handle);
	},
	
	attach: function(handle)
	{	
		this.parent.addClassName(Amslib_ScrollBar.config.css.content);
		
		if(handle == undefined) this.createHandle();

		this.handle.observe("mousedown",this.startDrag.bindAsEventListener(this));
		Event.observe(document.onresize ? document : window, "resize", this.reset.bindAsEventListener(this));
		
		this.reset();
	},
	
	show: function()
	{
		this.track.show("block");
	},
	
	hide: function()
	{
		if(!this.handle) return;
		
		//	Reset the view to the top
		this.handle.setStyle({top: "0px"});
		this.position = 0;
		this.updateContent();
		//	Now hide the track
		this.track.hide();
	},
	
	reset: function(event)
	{
		if(this.parent.getDimensions().height > this.container.getDimensions().height){
			this.show();
		}else{
			this.hide();
		}
		
		if(event) event.stop();
		return false;
	},
	
	createHandle: function()
	{
		this.container = this.parent.up();
		this.container.addClassName(Amslib_ScrollBar.config.css.parent);
		
		this.track	=	new Element("div",{className:Amslib_ScrollBar.config.css.track});
		this.handle	=	new Element("div",{className:Amslib_ScrollBar.config.css.handle});
		
		this.track.insert(this.handle);
		this.container.insert(this.track);
	},
	
	startDrag: function(event)
	{
		$(document.body).observe("mouseup",this.stopDrag.bindAsEventListener(this));
		$(document.body).observe("mousemove",this.updateHandle.bindAsEventListener(this));
		
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
	
	updateHandle: function(event)
	{
		//	Obtain the mouse position
		var x = Event.pointerX(event);
		var y = Event.pointerY(event);
		//	Calculate the position of the handle, restricted to the track area
		var p = this.container.cumulativeOffset();
		var h = this.handle.getDimensions().height;
		// FIXME: the -2 calculating 'm' is hardcoded from the margin
		var m = this.track.getDimensions().height - h - 2; 
		p.top = (y-p.top) - h/2;
		if(p.top < 0) p.top = 0;
		if(p.top > m) p.top = m;
		this.handle.setStyle({top: p.top+"px"});
		
		//	Calculate the normalised offset of the handle
		this.position = this.handle.positionedOffset().top / m;
		
		this.updateContent();
		
		event.stop();
		return false;
	},
	
	updateContent: function()
	{
		var scrollHeight = this.parent.getDimensions().height - this.container.getDimensions().height;
		
		this.parent.setStyle({top:-(scrollHeight*this.position)+"px"});
	},
	
	/**
	 * method: scrollTo
	 * 
	 * Animate a scroll of the content area, to the position requested
	 * 
	 * parameters:
	 * 	position	-	A number denoting the location to animate a scroll to
	 */
	scrollTo: function(position)
	{
		if(position >= 0 && position <= 1){
			//	Normalised coordinates
		}else{
			//	Pixel coordinates
		}
	},
	
	scrollPosition: function(position)
	{
		
	}
});

Amslib_ScrollBar.config = {
	css: {
		parent:		"amslib_scrollbar_parent", 
		content:	"amslib_scrollbar_content",
		track:		"amslib_scrollbar_track",
		handle:		"amslib_scrollbar_handle"
	}
}