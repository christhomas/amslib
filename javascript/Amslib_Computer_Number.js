Amslib_Computer_Number = Class.create(
{
	initialize: function(node)
	{
		var po = this;
		
		node = $(node);
		
		node.observe("keyup",function(){
			switch(node.nodeName)
			{
				case "INPUT":{	
					var string = this.value;
					var changed = po.removeDuplicates(string.replace(",","."));
					
					if(changed != string) this.value = changed;
				}break;
				
				default:{
					var string = this.innerHTML;
					var changed = po.removeDuplicates(string.replace(",","."));
					
					if(changed != string) this.update(changed);
				}break;
			}
		});
	},
	
	removeDuplicates: function(string)
	{
		var parts = string.split(".");
		if(parts.length > 2){
			var decimal = parts.pop();
			string = parts.join("")+"."+decimal;
		}

		return string;
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