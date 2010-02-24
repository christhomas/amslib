if(Prototype && Class)
{
	var Fade = Class.create({
		__step:			false,
		__resolution:	false,
		__cache:		false,
		
		__findNode: function(selector)
		{
			//	Look in the node cache to see if you have a reference for this selector already
			var node = this.__cache.get(selector);
			if(!node){
				//	Look for node, or attempt to get it from a css selector
				var node = $(selector);
				
				if(!node){
					node = $$(selector);
					node = (node.length) ? node.first() : false;
				}
				
				this.__cache.set(selector,node);
			}
			
			return node;
		},
		
		initialize: function()
		{
			this.__step			=	0.05;
			this.__resolution	=	10;
			this.__cache		=	new Hash();
			
			return this;
		},
		
		setSpeed: function(step)
		{
			this.__step = step;
			
			return this;
		},
		
		setResolution: function(resolution)
		{
			this.__resolution = resolution;
			
			return this;
		},
		
		show: function(selector,callback,maxOpacity)
		{
			var parentObject = this;
			
			var execute = function(selector,callback,maxOpacity)
			{
				var node = parentObject.__findNode(selector);
				if(node){
					var step = parentObject.__step;
					var max = maxOpacity || 1.0;
					
					//	FIXME: This is an assumption, what if your default display state is NOT block? 
					node.style.display = "block";
					
					var opacity = node.getOpacity();
					
					var showInterval = setInterval(function(){
						opacity += step;
						
						if(opacity > max){
							clearInterval(showInterval);
							node.setOpacity(max,true);
							if(typeof(callback) == "function") callback();
						}else{
							node.setOpacity(opacity);
						}
					},10);
				}
			}
			
			execute(selector,callback,maxOpacity);
			
			return this;
		},
		
		hide: function(selector,callback,minOpacity)
		{
			var parentObject = this;
			
			var execute = function(selector,callback,minOpacity)
			{
				var node = parentObject.__findNode(selector);
				
				if(node){
					var step = 0.10;
					var min = minOpacity || 0;
			
					var opacity = node.getOpacity();
					
					var showInterval = setInterval(function(){
						opacity -= step;
						
						if(opacity < min){
							clearInterval(showInterval);
							node.setOpacity(min);
							//	FIXME: Assumption, what if the opacity=0 display mode is NOT none?
							if(min == 0) node.style.display = "none";
							//	custom callback on completion
							if(typeof(callback) == "function") callback();
						}else{
							node.setOpacity(opacity);
						}
					},10);
				}
			}
			
			execute(selector,callback,minOpacity);
			
			return this;
		}
	});
}else{
	alert("Prototype not available, cannot use Fade class");
}