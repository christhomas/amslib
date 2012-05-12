var Amslib_Urlname = my.Amslib_Urlname = my.Class(
{
	src:	false,
	dest:	false,
	trimcb: false,
	base:	false,
	
	STATIC: {
		autoload: function()
		{
			$(".amslib_urlname_parent.amslib_autoload").each(function(){
				var src = $(this).find(".amslib_urlname_src");
				var dst = $(this).find(".amslib_urlname_dst");
				
				if(src.length == 0 || dst.length ==0) return;
				
				for(k in src){
					$(this).data("amslib_urlname",new Amslib_Urlname(this,src[k],dst[k]));
				}
			});
		}
	},
	
	constructor: function(parent,src,dest)
	{
		this.parent	=	$(parent);
		this.src	=	$(src);
		this.dest	=	$(dest);
		
		//	find the attribute on either the src node or parent node, or fail
		this.base = this.src.hasAttr("amslib-urlname-basestring")
			? this.src : (this.parent.hasAttr("amslib-urlname-basestring") ? this.parent : false);
		
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
		this.update(this.src.val());
	},
	
	updateFromDest: function()
	{
		this.update(this.dest.val());
	},
	
	update: function(string)
	{
		//	get the string from the data attribute, or return an empty string or an empty string if attribute never existed
		var baseString = this.base ? (this.base.data("amslib-urlname-basestring") || "") : "";
		
		this.dest.val(this.slugify(baseString+string));
	},
	
	slugify: function(string)
	{
		var po = this;

		if(this.trimcb) clearTimeout(this.trimcb);
		
		this.trimcb = setTimeout(function(){
			po.dest.val(Amslib_String.trim(po.dest.val(),' -_.'));
			po.dest.blur();
			po.trimcb = false;
		},2000);
		
		return Amslib_String.slugify(string);
	}
});

Amslib.loadJS("amslib.string",Amslib.locate()+"/js/Amslib_String.js",Amslib_Urlname.autoload);