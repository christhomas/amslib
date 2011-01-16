Amslib_Computer_Number = Class.create(
{
	initialize: function(node)
	{
		node = $(node);
		
		node.observe("keyup",function(){
			switch(node.nodeName)
			{
				case "INPUT":{	this.value = this.value.replace(",",".");		}break;
				default:{		this.update(this.innerHTML.replace(",","."));	}break;
			}
		});
	}
});

Amslib_Computer_Number.autoload = function(){
	Amslib_Computer_Number.apply($(document.body));
}

Amslib_Computer_Number.apply = function(parent){
	parent.select(".amslib_computer_number").each(function(node){
		new Amslib_Computer_Number(node);
	});
}

Event.observe(window,"load",Amslib_Computer_Number.autoload);