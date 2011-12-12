var Amslib = my.Amslib = my.Class(
{
	__amslibDefaultName:	"Amslib_Default_Controller",
	__amslibName:			false,
	
	STATIC: {
		firebug: function(string)
		{
			if(console && console.log) console.log.apply(null,arguments);
		},
		
		getPath: function(file)
		{
			//	Copied from how scriptaculous does it's "query string" thing
			var re 		=	new RegExp("^(.*?)"+file.replace(/[-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")+"$","g");
			var path	=	false;
			
			$("head script[src]").each(function(){
				var matches = re.exec(this.src);
				
				if(matches){
					path = matches[1]; 
					return false;
				}
			});
			
			if(!path) Amslib.firebug("requested path["+file+"] using regexp["+re+"] was not found");
				
			return path;
		},
		
		loadCSS: function(file)
		{
			$("head").append($("<link/>").attr({rel:"stylesheet",type:"text/css",href: file}));
		},
		
		loader: {}
	},
	
	constructor: function(parent,name)
	{
		this.parent = $(parent) || false;
		if(!this.parent) return false;
		
		this.__amslibName = name || this.__amslibDefaultName;
		
		//	Setup the amslib_controller to make this object available on the node it was associated with
		this.parent.data(this.__amslibName,this);
		
		return this;
	},
	
	getAmslibName: function()
	{
		return __amslibName;
	},
	
	getParentNode: function()
	{
		return this.parent;
	}
});