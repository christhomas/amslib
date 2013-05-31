/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *     
 *******************************************************************************/

/**
 * 	class:	Amslib_Urlname
 * 
 *	group:	javascript
 * 
 *	file:	Amslib_Urlname.js
 * 
 *	title:	todo, give title
 * 
 *	description:
 *		todo, write description 
 *
 * 	todo:
 * 		write documentation
 */
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
				
				if(src.length == 0 || dst.length == 0) return;
				
				for(var a=0;a<src.length;a++){
					s = src.eq(a);
					d = dst.eq(a);
					
					if(s && d) $(this).data("amslib_urlname",new Amslib_Urlname(this,s,d));
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
		//	NOTE: what does the base string do anyway?
		this.base = this.src.is("[amslib-urlname-basestring]")
			? this.src : (this.parent.is("[amslib-urlname-basestring]") ? this.parent : false);
		
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
			po.dest.val(Amslib_String.trim(po.dest.val(),"\\s\\-\\_\\."));
			po.dest.blur();
			po.trimcb = false;
		},2000);
		
		return Amslib_String.slugify(string);
	}
});

Amslib.loadJS("amslib.string",Amslib.locate()+"/js/Amslib_String.js",Amslib_Urlname.autoload);