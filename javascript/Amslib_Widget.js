var Amslib_Widget = Class.create(
{
	mainWidget: 	false,
	value:			false,
	services:		false,
	translation:	false,
	callback:		false,
	
	initialize: function(selector)
	{
		this.value			=	new Hash();
		this.services		=	new Hash();
		this.callback		=	new Hash();
		this.translation	=	new Hash();
		
		//	Try first to just pass it through prototype, then if fails, use as a selector
		this.mainWidget = $(selector);
		if(!this.mainWidget) this.mainWidget = $(document.body).down(selector);
		if(!this.mainWidget) return false;
		
		this.readParameters();
		
		return this;
	},
	
	getNode: function()
	{
		return this.mainWidget;
	},
	
	identify: function()
	{
		return this.mainWidget.identify();
	},
	
	setCallback: function(type,callback)
	{
		this.callback.set(type,callback);
	},
	
	defaultCallback: function(){
		if(console && console.log) console.log("DEFAULT CALLBACK CALLED");
	},
	
	getCallback: function(type)
	{
		var cb = this.callback.get(type);
				
		return (cb) ? cb : this.defaultCallback;
	},

	readParameters: function()
	{
		if(!this.mainWidget) return false;
		
		this.mainWidget.select(".widget_parameters input[type='hidden']").each(function(p){
			if(p.name.indexOf("service:") >= 0){
				this.setService(p.name.replace("service:",""),p.value);
			}else if(p.name.indexOf("translation:") >= 0){
				this.setTranslation(p.name.replace("translation:",""),p.value);
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
	
	setTranslation: function(name,value)
	{
		this.translation.set(name,value);
	},
	
	getTranslation: function(name)
	{
		return this.translation.get(name);
	}
});