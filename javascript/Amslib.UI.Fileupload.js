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
if(Amslib.UI == "undefined")
	throw "Amslib.UI.Fileupload requires Amslib.UI to be loaded.";

Amslib.UI.Fileupload = Class.create(Amslib.UI,
{
	parent:		false,
	input:		false,
	fake:		false,
	callback:	function(){},
	
	initialize: function($super,node,button)
	{
		$super();
		
		this.parent	=	node;
		this.input	=	node.down("input.file");
		
		var field = new Element("span",{className:"fakefile"});
		field.insert({bottom:new Element("input")});
		field.insert({bottom:button});
		
		this.parent.insert({bottom:field});
		this.fake = this.parent.down(".fakefile input");
		
		this.input.observe("change",this.click.bindAsEventListener(this));
		
		return this;
	},
	
	setCallback: function(cb)
	{
		this.callback = cb;
	},
	
	click: function(event){ 
		this.fake.value = this.input.value;
		this.callback();
		
		event.stop();
		return false;
	}
})