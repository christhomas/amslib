var Amslib = my.Amslib = my.Class(
{
	__amslibDefaultName:	"Amslib_Default_Controller",
	__amslibName:			false,
	__options:				{},
	
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
			
			$("script[src]").each(function(){
				var matches = re.exec(this.src);
				
				if(matches){
					path = matches[1]; 
					return false;
				}
			});
			
			if(!path) Amslib.firebug("requested path["+file+"] using regexp["+re+"] was not found");
				
			return path;
		},
		
		loadJS: function(name,file,onReady)
		{
			if(typeof require == 'function'){
				Amslib.loader[name] = require(file);
			
				if(typeof onReady == "function") scope(onReady,Amslib.loader[name]);
				
				scope(function(){
					Amslib.__waitJS(name);
				},Amslib.loader[name]);
			}
		},
		
		hasJS: function(name,callback)
		{
			if(typeof name == "string") name = new Array(name);
			
			var checkGroup = function(){
				var loaded = true;
				
				for(n=0;n<name.length;n++){
					if(!Amslib.__loaderReady[name[n]]) return false;
				}

				if(callback) callback();
				
				return true;
			};
			
			for(var n=0;n<name.length;n++){
				if(Amslib.__loaderReady[name[n]] && checkGroup()) return true;
				else Amslib.__loaderCallback[name[n]] = checkGroup;
			}
			
			return false;
		},
		
		loadCSS: function(file)
		{
			$("head").append($("<link/>").attr({rel:"stylesheet",type:"text/css",href: file}));
		},
		
		loader: {},
		
		//	Support asynchronous loading a provides a "notification callback" system
		__loaderReady: {},
		__loaderCallback: {},
		__waitJS: function(name)
		{
			Amslib.__loaderReady[name] = true;
			
			if(Amslib.__loaderCallback[name]) Amslib.__loaderCallback[name]();
		}
	},	
	
	constructor: function(parent,name)
	{
		this.parent = $(parent) || false;
		if(!this.parent) return false;
		
		this.__amslibName = name || this.__amslibDefaultName;
		
		//	Setup the amslib_controller to make this object available on the node it was associated with
		this.parent.data(this.__amslibName,this);
		
		//	Now merge all the specific options from the static parameter "options" into this
		//	NOTE: Hmm....I can't think how to obtain the name of the parent class.....shit....
		//	NOTE: perhaps this is why other plugins don't use a static object?
		//	NOTE: well shit, that means I have to convert all the javascript objects to not using them
		//	NOTE: yes, well, what do you want? a medal?
		//this.__options = this.__options.extend()
		
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