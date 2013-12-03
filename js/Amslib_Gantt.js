var Amslib_Gantt = my.Amslib_Gantt = my.Class(Amslib,
{
	STATIC: {
		autoload: function()
		{
			var c = Amslib_Gantt;
			
			c.instances = $(c.options.autoload);
			
			c.instances.each(function(){
				new c($(this),{});
			});
		},
		
		options: {
			amslibName:	"Amslib_Gantt",
			
			autoload: 	"[data-amslib-gantt='true']",
			
			gantt:{
				navigate: "scroll",
				maxScale: "hours",
				itemsPerPage: 1000,
				attachJSON: false,
				showDescription: true,
				events: $("<div/>")
			}
		},
		
		datakey: {
			source_url:			"amslib-gantt-source-url",
			source_string:		"amslib-gantt-source-string",
			source_selector:	"amslib-gantt-source-selector",
			show_description:	"amslib-gantt-show-description",
			attach_json:		"amslib-gantt-attach-json",
			event_click:		"amslib-gantt-event-click",
			event_add:			"amslib-gantt-event-add",
			event_render:		"amslib-gantt-event-render"
		}
	},
	
	constructor: function(parent,options)
	{
		var c = Amslib_Gantt,
			o = c.options,
			d = c.datakey;
		
		c.Super.call(this,parent,c.amslibName);
		
		this.options = $.extend(true,{},o,options);
		
		var source = this.parent.data(d.source_url);
		if(source) this.options.gantt.source = source;
		
		//	TODO: implement amslib-gantt-source-string
		//	TODO: implement amslib-gantt-source-selector
		
		var attach_json = this.parent.data(d.attach_json);
		this.options.gantt.attachJSON = !!attach_json;
		
		var show_desc = this.parent.data(d.show_description);
		this.options.gantt.showDescription = !!show_desc;
		
		this.readHandler(d.event_click,"click");
		this.readHandler(d.event_add,"add");
		this.readHandler(d.event_render,"render");
		
		$(".gantt_chart").gantt(this.options.gantt);
		
		//	TODO: investigate ways to make this configurable
		$(".gantt").popover({
			selector: ".bar",
			title: "I'm a popover",
			content: "And I'm the content of said popover.",
			trigger: "hover"
		});
	},
	
	readHandler: function(key,event)
	{
		var callback = this.parent.data(key);
		
		if(!callback) return;
		
		var f = window, s = callback.split(".");
		
		for(i in s) f = f[s[i]];
		
		if(typeof(f) != "undefined"){
			this.options.gantt.events.on(event,f);
		}
	}
});

$(document).ready(Amslib_Gantt.autoload);