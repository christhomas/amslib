var Amslib_Form = my.Amslib_Form = my.Class(Amslib,
{
	STATIC: {
		autoload: function()
		{
			var c = Amslib_Form,
				o = c.options;
			
			$(c.options.autoload).each(function(){
				new c(this,o);
			});
			
			Amslib.wait.resolve(o.controller);
		},
		
		options: {
			autoload:	"[data-role='amslib-form-controller']",
			form:		"[data-role='amslib-form']",
			action:		"[data-role='amslib-form-action']",
			controller:	"Amslib_Form"
		},
		
		//	This method passes the on call to the controller object who then applies it to the DOM node
		//	node, event, [child-selector, callback]
		on: function(va_args)
		{
			//	Minimum 2 arguments, otherwise we have no event or callback
			if(arguments.length < 2) return false;
			
			var c = Amslib.controller.get(arguments[0],Amslib_Form.options.controller);
			
			c.on.apply(c,arguments);
			
			return Amslib_Form;
		}
	},
	
	constructor: function(parent,options)
	{
		var c = Amslib_Form,
			e = $.proxy(this,"triggerNative");
		
		c.Super.call(this,parent,options.controller);
		
		$(document)
			//	If it's a HTML form and attempts to submit itself
			.on("submit",options.form,e)
			//	When any action button is clicked
			.on("click",options.form+" "+options.action,e);
	},
	
	getData: function(parent)
	{
		//	Acquire the form, or wrap the parent in a form to serialise the elements into an array of data
		var form = parent.is("form") ? false : parent.wrap("<form/>").parent();
		var data = (form || parent).serializeArray();
		
		//	Add the success and failure urls to the data array
		data.push({	name:"url_success",	value: this.getURL(parent,"success") });
		data.push({	name:"url_failure",	value: this.getURL(parent,"failure") });
		
		//	if you did find a form, unwrap it afterwards
		if(form) parent.unwrap();

		return data;
	},
	
	getURL: function(parent,name)
	{
		var url = parent.data("url-"+name);
		
		var not_found = name == "success" || name == "failure" 
			? this.getURL(parent,"return") 
			: false;
		
		return url || not_found;
	},
	
	//	event, [child-selector, callback]
	on: function(va_args)
	{
		var args = Array.prototype.slice.call(arguments), node = $(args.shift());
		
		//	Minimum 2 arguments, otherwise we have no event or callback
		if(args.length < 2) return false;
		
		//	make the final event name
		args[0] = "power-panel-form:"+args[0];
		
		node.on.apply(node,args);
				
		return this;
	},
	
	triggerNative: function(event)
	{
		var node = $(event.currentTarget);
		var action = node.data("event") || event.type;
		
		console.log(new Date().getTime(),"form event["+action+"], native event["+event.type+"]");
		
		this.parent.trigger("amslib-form:submit");
		
		return false;
	},
	
	trigger: function(event)
	{
		
	},
	
	onSubmit: function(event,form,data)
	{
		var data = data || this.getData(form);
		
		new Amslib_Webservice()
			.post(this.getURL(form,"post"),data,true)
			.success($.proxy(this,"handleSuccess"))
			.failure($.proxy(this,"handleFailure"))
			.set("form",form);
		
		return false;
	},
});

$(document).ready(Amslib_Form.autoload);
$(document).ready(function(){
	$(".manual_submit").on("click",function(){
		$(".form1").submit();
	});
});