if(typeof(Amslib) == "undefined" || typeof(Amslib.UI) == "undefined")
	throw "Amslib.UI.Accordion requires Amslib.UI to be loaded.";

Amslib.UI.Accordion = Class.create(Amslib.UI,
{
	animating:	false,
	accordion:	false,
	openBlock:	false,
	callback:	false,
	
	initialize: function($super,accordion)
	{
		$super();
		
		this.accordion = accordion;
		this.accordion.select("."+Amslib.UI.Accordion.config.title).each(function(title){
			title.observe("click",this.click.bind(this));
		}.bind(this));
		
		this.openBlock = this.accordion.down("."+Amslib.UI.Accordion.config.css.open);
		this.animating = false;
		//	Set the default callback
		this.callback = new Hash();
		this.observe("click",function(){});
	},
	
	click: function(event)
	{
		if(!this.animating){
			var element = event.element();
			
			if(element.hasClassName(Amslib.UI.Accordion.config.css.open)){
				if(this.openBlock) this.close(element);
				this.openBlock = false;
			}else{
				this.callback.get("click")(this.openBlock,element);
				
				if(this.openBlock) this.close(this.openBlock);
				this.openBlock = element;
				
				this.open(element);
			}
		}
		
		event.stop();
		return false;
	},
	
	open: function(element)
	{
		this.animating = true;
		
		element.removeClassName(Amslib.UI.Accordion.config.css.close);
		element.addClassName(Amslib.UI.Accordion.config.css.open);
		
		var content = element.next("."+Amslib.UI.Accordion.config.content);
		
		if(content) content.blindDown({
			duration: Amslib.UI.Accordion.config.duration,
			afterFinish: function(){
				this.animating = false;
			}.bind(this)
		});
	},
	
	close: function(element)
	{
		this.animating = true;
		
		element.addClassName(Amslib.UI.Accordion.config.css.close);
		element.removeClassName(Amslib.UI.Accordion.config.css.open);
		
		var content = element.next("."+Amslib.UI.Accordion.config.content);
		
		if(content) content.blindUp({
			duration: Amslib.UI.Accordion.config.duration,
			afterFinish: function(){
				this.animating = false;
			}.bind(this)
		});
	},
	
	observe: function(handle,callback)
	{
		this.callback.set(handle,callback);
	}
});

Amslib.UI.Accordion.config = {
	title:		"amslib_ui_accordion_title",
	content:	"amslib_ui_accordion_content",
	css:{
		open:	"amslib_ui_accordion_open",
		close:	"amslib_ui_accordion_close"
	},
	duration:	0.5
}
