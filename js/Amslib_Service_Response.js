var Amslib_Service_Response = my.Amslib_Service_Response = my.Class(
{
	json: false,

	constructor: function(json){
		this.setJSON(json);											
	},
	
	setJSON: function(json){
		this.json = json;											
	},
	
	hasSuccess: function(){
		return this.json.success ? true : false;
	},

	hasPlugin: function(plugin){
		return this.readData(plugin) != undefined ? true : false;
	},

	readData: function(plugin,group,name,handlerindex)
	{
		if(handlerindex === undefined) handlerindex = 0;
		
		try{
			var data = this.json["handlers"][handlerindex][plugin];
			if(group != undefined)	data = data[group];
			if(name != undefined)	data = data[name];
			return data;
		}catch(e){}

		return undefined;
	},

	getServiceData: function(plugin,name,handlerindex){	
		return this.readData(plugin,"service/data",name,handlerindex);		
	},
	
	getServiceErrors: function(plugin,name,handlerindex){	
		return this.readData(plugin,"service/errors",name,handlerindex);		
	},
	
	getValidationData: function(plugin,name,handlerindex){	
		return this.readData(plugin,"validation/errors",name,handlerindex);	
	},
	
	getValidationErrors: function(plugin,name,handlerindex){	
		return this.readData(plugin,"validation/data",name,handlerindex);	
	}
	//	TODO: getDatabaseErrors
});