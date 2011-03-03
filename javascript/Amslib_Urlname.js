Amslib_Urlname = Class.create(
{
	src:	false,
	dest:	false,
	
	initialize: function(src,dest)
	{
		this.src	=	src;
		this.dest	=	dest;
		
		this.src.observe("keyup",this.updateFromSrc.bind(this));
		this.src.observe("change",this.updateFromSrc.bind(this));
		this.dest.observe("keyup",this.updateFromDest.bind(this));
		
		//	Initialise any empty values if there is
		//	a) nothing already set
		//	b) there is something to update from
		if(src.value.length > 0 && dest.value.length == 0){
			this.updateFromSrc();
		}
	},
	
	updateFromSrc: function()
	{
		this.dest.value = this.slugify(this.src.value);
	},
	
	updateFromDest: function()
	{
		this.dest.value = this.slugify(this.dest.value);
	},

	slugify: function(text){
		return text.replace(/[^-a-zA-Z0-9]+/ig, '-').toLowerCase();
	}
});

Amslib_Urlname.autoload = function()
{
	$$(".amslib_urlname_parent.autoload").each(function(urlname){
		var src = urlname.down(".amslib_urlname_src");
		var dst = urlname.down(".amslib_urlname_dst");
		
		if(src && dst){
			src.store("amslib_urlname",new Amslib_Urlname(src,dst));
		}
	});
}

Event.observe(window,"load",Amslib_Urlname.autoload);