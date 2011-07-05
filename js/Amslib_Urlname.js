var Amslib_Urlname = my.Amslib_Urlname = my.Class(
{
	src:	false,
	dest:	false,
	
	STATIC: {
		autoload: function()
		{
			$(".amslib_urlname_parent.amslib_autoload").each(function(){
				var src = $(this).find(".amslib_urlname_src");
				var dst = $(this).find(".amslib_urlname_dst");
				
				if(src && dst) src.data("amslib_urlname",new Amslib_Urlname(src,dst));
			});
		}
	}
	
	constructor: function(src,dest)
	{
		this.src	=	src;
		this.dest	=	dest;
		
		this.src.bind("keyup",$.proxy(this,"updateFromSrc"));
		this.src.bind("change",$.proxy(this,"updateFromSrc"));
		this.dest.bind("keyup",$.proxy(this,"updateFromDest"));
		
		//	Initialise any empty values if there is
		//	a) nothing already set
		//	b) there is something to update from
		if(src.val().length > 0 && dest.val().length == 0){
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

$(document).ready(Amslib_Urlname.autoload);