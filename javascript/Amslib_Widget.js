var Amslib_Widget = Class.create(
{
	mainWidget: false,
	params:		false,
	services:	false,
	
	initialize: function(selector)
	{
		this.params		=	new Hash();
		this.services	=	new Hash();
		
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
		
		var po = this;
		this.mainWidget.select(".widget_parameters input[type='hidden']").each(function(p){
			if(p.name.indexOf("service:") >= 0){
				po.services.set(p.name.replace("service:",""),p.value);
			}else{
				po.value.set(p.name,p.value);
			}
		});
	},
	
	setValue: function(name)
	{
		this.value.set(name);
	},
	
	getValue: function(name)
	{
		return this.value.get(name);
	},
		
	getService: function(name)
	{
		return this.services.get(name);
	}
});