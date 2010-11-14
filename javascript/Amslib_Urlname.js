Amslib_Urlname = Class.create(
{
	initialize: function(src,dest)
	{
		src.observe("keyup",function(event){
			dest.value = this.slugify(src.value);
		}.bind(this));
		
		src.observe("change",function(event){
			dest.value = this.slugify(src.value);
		}.bind(this));
		
		dest.observe("keyup",function(event){
			dest.value = this.slugify(dest.value);
		}.bind(this));
	},

	slugify: function(text){
		return text.replace(/[^-a-zA-Z0-9]+/ig, '-').toLowerCase();
	}
});