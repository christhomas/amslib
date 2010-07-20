var Amslib_Widget = Class.create(
{
	mainWidget: false,
	params:		new Hash(),
	services:	new Hash(),
	
	initialize: function(selector)
	{
		//	Try first to just pass it through prototype, then if fails, use as a selector
		this.mainWidget = $(selector);
		if(!this.mainWidget) this.mainWidget = $(document.body).down(selector);
		
		this.readParameters();
	},

	readParameters: function()
	{
		if(!this.mainWidget) return false;
		
		var po = this;
		this.mainWidget.select(".widget_parameters input[type='hidden']").each(function(p){
			if(p.name.indexOf("service:") >= 0){
				po.services.set(p.name.replace("service:",""),p.value);
			}else{
				po.params.set(p.name,p.value);
			}
		});
	},
	
	getParam: function(name)
	{
		return this.params.get(name);
	},
	
	getService: function(name)
	{
		return this.services.get(name);
	}
});