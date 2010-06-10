var Amslib_Widget = Class.create(
{
	mainWidget: false,
	params:		new Hash(),
	services:	new Hash(),
	
	initialize: function(selector)
	{
		this.mainWidget = $(document.body).down(selector);
		this.readParameters();
	},

	readParameters: function()
	{
		if(this.mainWidget == false) return false;
		
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