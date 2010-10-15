var Amslib_Widget = Class.create(
{
	mainWidget: false,
	value:		false,
	services:	false,
	images:		false,
	
	initialize: function(selector)
	{
		this.value		=	new Hash();
		this.services	=	new Hash();
		this.images		=	new Hash();
		
		//	Try first to just pass it through prototype, then if fails, use as a selector
		this.mainWidget = $(selector);
		if(!this.mainWidget) this.mainWidget = $(document.body).down(selector);
		if(!this.mainWidget) return false;
		
		this.readParameters();
		
		return this;
	},

	readParameters: function()
	{
		if(!this.mainWidget) return false;
		
		this.mainWidget.select(".widget_parameters input[type='hidden']").each(function(p){
			if(p.name.indexOf("service:") >= 0){
				this.setService(p.name.replace("service:",""),p.value);
			}else if(p.name.indexOf("image:") >= 0){
				this.setImage(p.name.replace("image:",""),p.value);
			}else{
				this.setValue(p.name,p.value);
			}
		}.bind(this));
	},
	
	setValue: function(name,value)
	{
		this.value.set(name,value);
	},
	
	getValue: function(name)
	{
		return this.value.get(name);
	},
	
	setService: function(name,value)
	{
		this.services.set(name,value);
	},
		
	getService: function(name)
	{
		return this.services.get(name);
	},
	
	setImage: function(name,value)
	{
		this.images.set(name,value);
	},
	
	getImage: function(name)
	{
		return this.images.get(name);
	}
});