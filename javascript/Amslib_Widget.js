if(typeof(Amslib) == "undefined")
	throw "Amslib_Widget requires Amslib to be loaded.";

//	This is now just a dummy class to make sure all the other code works whilst 
//	it's still in use, this class should actually be removed and not used anymore
var Amslib_Widget = Class.create(Amslib,
{
	initialize: function($super,selector)
	{
		return $super(selector);
	}
});