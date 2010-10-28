if(Amslib == "undefined")
	throw "Amslib.UI requires Amslib to be loaded.";

Amslib.UI = Class.create(Amslib,{
	initialize: function($super){
		$super();
	}
})