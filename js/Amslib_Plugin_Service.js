var Amslib_Plugin_Service = my.Amslib_Plugin_Service = my.Class({
    
    prefix:'>>> ',
    
    constructor: function(req){
    	
    	if(typeof req!='object')
		return {error:'Request must be an object'}

		if(typeof req.target=='undefined')
		return {error:'Missing API target'}

		console.log(this.prefix+'Sending request to '+req.target+' with data shown below') 
		console.log(req.data)

		$.ajax({
			url:		req.target,
			data:		req.data,
			type:		'POST',
			request:	req,
			scope:		this,
			complete:	function(data)	
						{	
							console.log(this.prefix+'API responded with raw data shown below')
							console.log(data.responseText)
							// try to parse text to JSON
							try
							{
								var json=$.parseJSON(data.responseText)
							}
							catch(e)
							{
								console.log(this.prefix+'API response not in JSON format')
								
								if(typeof this.request.fail=='function')
								this.request.fail({error:'API response not in JSON format'})
							}
						
							this.scope.read($.parseJSON(data.responseText),this.request)
						}
		})
	
		return {status:'sent'}
    },
	
	/*
	 *	example of expected data for req:
	 *
	 *	{
	 		target:		<URL>
	 		data:		<POST_DATA>
	 		service:	<SERVICE_NAME>
	 		success:	<CLOSURE>
	 		fail:		<CLOSURE>
	 	}
	 *
	 *
	 */
	
	read:function(res,req)
	
	{
		console.log(this.prefix+'Got response from API with data (see next line)')
		console.log(res)

		if(!typeof res=='object'||res===null)
		return {error:'API response must be an object'}

		if(!typeof res.success=='boolean')
		return {error:'API response is missing success indicator'}

		if(!res.success && typeof req=='object' && typeof req.fail=='function')
		
		{
			console.log('API responded with a failure')
			return req.fail(res)
		}
		
		if(typeof req=='object' && typeof req.success=='function')
		
		{
			console.log('API responded successfully')
			return req.success([res,req])
		}
	}
});
