wait.until("Amslib","Amslib_Webservice",function(Amslib,Amslib_Webservice)
{
	var Amslib_Form = my.Amslib_Form = my.Class(Amslib,
	{
		STATIC: {
			options: {
				autoload:	"[data-role='amslib-form']",
				action:		"[data-role='amslib-form-action']",
				controller:	"Amslib_Form"
			}
		},
		
		constructor: function(parent,options)
		{			
			Amslib_Form.Super.call(this,parent,options.controller);
			
			var e = $.proxy(this,"triggerNative");
			
			$(document)
				//	If it's a HTML form and attempts to submit itself
				.on("submit",options.autoload,e)
				//	When any action button is clicked
				.on("click",options.autoload+" "+options.action,e);
			
			this.parent.on("amslib-form:submit",$.proxy(this,"onSubmit"));
		},
		
		getData: function(parent)
		{
			parent = $(parent);

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
			parent = $(parent);

			var url = parent.data("url-"+name);
			
			var not_found = name == "success" || name == "failure" 
				? this.getURL(parent,"return") 
				: false;
			
			return url || not_found;
		},
		
		triggerNative: function(event)
		{
			var node	=	$(event.currentTarget),
				action	=	"amslib-form:"+(node.data("event") || event.type);
			
			console.log(new Date().getTime(),"form event["+action+"], native event["+event.type+"]");
			
			this.parent.trigger(action);
			
			return false;
		},
		
		onSubmit: function(event)
		{
			console.log("onSubmit(): ",event.type);

			var data = this.getData(this.parent);
			
			new Amslib_Webservice()
				.post(this.getURL(this.parent,"post"),data,true)
				.success($.proxy(this,"onSuccess"))
				.failure($.proxy(this,"onFailure"));
			
			return false;
		},
		
		onSuccess: function(ws,json)
		{
			if(this.parent.data("prevent-redirect")){
				return false;
			}
			
			this.parent.trigger("amslib-form:success");
			
			var url = this.getURL(this.parent,"success");
			
			if(url) window.location = url;
		},
		
		onFailure: function()
		{
			if(this.parent.data("prevent-redirect")){
				return false;
			}
			
			this.parent.trigger("amslib-form:failure");
			
			var url = this.getURL(this.parent,"failure");
			
			if(url) window.location = url;
		}
	});
	
	var c = Amslib_Form, o = c.options;
	
	$(o.autoload).each(function(){
		new c(this,o);
	});
	
	wait.resolve(o.controller,c);
	
	//	These are testing methods I think?
	$(document).ready(function(){
		$(".manual_submit").on("click",function(){
			$(".form1").submit();
		});
	});
});