var
	_Amslib_Plugin_Service={
		prefix:	' < Amslib:Plugin:Service > '
	}

var Amslib_Plugin_Service = my.Amslib_Plugin_Service = my.Class({
    
    constructor: function(req){	// the constructor sends the request to the service
    	
    	if(typeof req!='object') {
    		return {error:'Request must be an object'};
    	}

		if(typeof req.target=='undefined')	{
			return {error:'Missing API target'};
		}
		
		var request={
			url:		req.target,
			data:		req.data,
			type:		'POST',
			request:	req,
			scope:		this,
			complete:	function(data)	
						{	
							// try to parse text to JSON
							try
							{
								var json=$.parseJSON(data.responseText);
							}
							catch(e)
							{
								if(typeof this.request.fail=='function')
								{
									this.request.fail({error:'API response not in JSON format'});
								}
							}
						
							this.scope.read($.parseJSON(data.responseText),this.request);
						}
		}
		
		if(typeof req.dataType!='undefined'){
			request.dataType=req.dataType;
		}

		$.ajax(request);
	
		return {status:'sent'};
    },
	
	read:function(res,req)	// read reads service response
	
	{
		if(!typeof res=='object'||res===null){
			return {error:'API response must be an object'};
		}

		if(!typeof res.success=='boolean'){
			return {error:'API response is missing success indicator'};
		}

		if(!res.success && typeof req=='object' && typeof req.fail=='function'){
			return req.fail(res);
		}
		
		if(typeof req=='object' && typeof req.success=='function'){
			return req.success([res,req]);
		}
	}
});
