if(typeof(Amslib) == "undefined")
	throw "Amslib.UI requires Amslib to be loaded.";

//	TODO: perhaps this class should be removed because it's ultimately useless
Amslib.UI = Class.create(Amslib,
{
	initialize: function($super,parent){
		return $super(parent);
	}
});