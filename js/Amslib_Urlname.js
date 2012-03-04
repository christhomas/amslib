var Amslib_Urlname = my.Amslib_Urlname = my.Class(
{
	src:	false,
	dest:	false,
	trimcb: false,
	
	STATIC: {
		autoload: function()
		{
			$(".amslib_urlname_parent.amslib_autoload").each(function(){
				var src = $(this).find(".amslib_urlname_src");
				var dst = $(this).find(".amslib_urlname_dst");
				
				if(src && dst) $(this).data("amslib_urlname",new Amslib_Urlname(src,dst));
			});
		}
	},
	
	constructor: function(src,dest)
	{
		this.src	=	src;
		this.dest	=	dest;
		
		this.src.keyup($.proxy(this,"updateFromSrc"));
		this.src.change($.proxy(this,"updateFromSrc"));
		this.dest.keyup($.proxy(this,"updateFromDest"));
		
		//	Initialise any empty values if there is
		//	a) nothing already set
		//	b) there is something to update from
		if(src.val().length > 0 && dest.val().length == 0){
			this.updateFromSrc();
		}
	},
	
	updateFromSrc: function()
	{
		this.dest.val(this.slugify(this.src.val()));
	},
	
	updateFromDest: function()
	{
		this.dest.val(this.slugify(this.dest.val()));
	},
	
	slugify: function()
	{
		var po = this;

		if(this.trimcb) clearTimeout(this.trimcb);
		
		this.trimcb = setTimeout(function(){
			po.dest.val(Amslib_String.trim(po.dest.val(),' -_.'));
			po.trimcb = false;
		},2000);
		
		return Amslib_String.slugify(this.src.val());
	}
});

Amslib.loadJS("amslib.string",Amslib.locate()+"/js/Amslib_String.js",Amslib_Urlname.autoload);