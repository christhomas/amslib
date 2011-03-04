/*****************************************************************
 * File input styling method I stole, err, I mean 
 * copied from the following url:
 * 
 * http://www.quirksmode.org/dom/inputfile.html
 * 
 * thanks guys, you saved me a lot of time!
 * 
 * update: rewritten to take advantage of prototype library
 */
if(typeof(Amslib) == "undefined" || typeof(Amslib.UI) == "undefined")
	throw "Amslib.UI.Fileupload requires Amslib.UI to be loaded.";

Amslib.UI.Fileupload = Class.create(Amslib.UI,
{
	parent:		false,
	input:		false,
	fake:		false,
	
	initialize: function($super,node)
	{
		$super();
		
		this.parent		=	node;
		this.input		=	node.down("input[type='file']");
		this.display	=	this.parent.down("."+Amslib.UI.Fileupload.config.fileDisplay);
		
		this.input.observe("change",this.click.bindAsEventListener(this));
		
		this.parent.store(Amslib.UI.Fileupload.config.nodeStorage,this);
		
		return this;
	},
	
	reset: function()
	{
		this.input.remove();
		this.input = new Element("INPUT",{type:"file",name:"filename"});
		this.parent.insert(this.input);
	},
	
	click: function(event){ 
		this.display.update(this.input.value);
		
		this.callObserver("click",{
			event:		event,
			value:		this.input.value,
			onFailure:	this.reset.bind(this)
		});
		
		event.stop();
		return false;
	}
});

Amslib.UI.Fileupload.config = {
	auto:			"amslib_ui_fileupload_auto",
	nodeStorage:	"amslib_ui_fileupload",
	fileDisplay:	"filename_display"
}

Amslib.UI.Fileupload.autoload = function(){
	$$("."+Amslib.UI.Fileupload.config.auto).each(function(a){
		if(!a.retrieve(Amslib.UI.Fileupload.config.nodeStorage)){
			var fileupload = new Amslib.UI.Fileupload(a);
		}
	});
}

Event.observe(window,"load",Amslib.UI.Fileupload.autoload);