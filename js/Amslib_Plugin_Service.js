var
	_Amslib_Plugin_Service={
		prefix:	' < Amslib:Plugin:Service > '
	}

var Amslib_Plugin_Service = my.Amslib_Plugin_Service = my.Class({
    
    constructor: function(req){	// the constructor sends the request to the service
    	
    	if(typeof req!='object')
		return {error:'Request must be an object'}

		if(typeof req.target=='undefined')
		return {error:'Missing API target'}

		console.log(_Amslib_Plugin_Service.prefix+' < Sending request to "'+req.target+'" with data shown below ↴ >') 
		console.log(req.data)
		
		var request={
			url:		req.target,
			data:		req.data,
			type:		'POST',
			request:	req,
			scope:		this,
			complete:	function(data)	
						{	
							console.log(_Amslib_Plugin_Service.prefix+' < API responded with raw data shown below ↴ >')
							console.log({'rawResponse':data.responseText})
							// try to parse text to JSON
							try
							{
								var json=$.parseJSON(data.responseText)
							}
							catch(e)
							{
								console.log(_Amslib_Plugin_Service.prefix+' < API response not in JSON format >')
								
								if(typeof this.request.fail=='function')
								this.request.fail({error:'API response not in JSON format'})
							}
						
							this.scope.read($.parseJSON(data.responseText),this.request)
						}
		}
		
		if(typeof req.dataType!='undefined')
		request.dataType=req.dataType

		$.ajax(request)
	
		return {status:'sent'}
    },
	
	read:function(res,req)	// read reads service response
	
	{
		console.log(_Amslib_Plugin_Service.prefix+' < Got response from API with data shown below ↴ >')
		console.log(res)

		if(!typeof res=='object'||res===null)
		return {error:'API response must be an object'}

		if(!typeof res.success=='boolean')
		return {error:'API response is missing success indicator'}

		if(!res.success && typeof req=='object' && typeof req.fail=='function')
		
		{
			console.log(_Amslib_Plugin_Service.prefix+' < API responded with a failure >')
			return req.fail(res)
		}
		
		if(typeof req=='object' && typeof req.success=='function')
		
		{
			console.log(_Amslib_Plugin_Service.prefix+' < API responded successfully >')
			return req.success([res,req])
		}
	}
});
