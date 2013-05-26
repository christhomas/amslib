var Amslib_Google_Map = my.Amslib_Google_Map = my.Class(my.Amslib,
{
	zoom:				9,
	mapObject:			false,
	marker:				false,
	config:				"",
	currentPosition:	false,
	
	STATIC: {
		autoload: function(){
			Amslib_Google_Map.instances = $(Amslib_Google_Map.options.autoload);
			
			Amslib_Google_Map.instances.each(function(){
				new Amslib_Google_Map(this);
			});
		},
		
		options: {
			amslibName:	"Amslib_Google_Map",
			autoload:	"[data-enable-google-map]",
		},
		
		instances: false
	},
	
	constructor: function(parent){
		Amslib_Google_Map.Super.call(this,parent,Amslib_Google_Map.options.amslibName);

		this.parent = $(parent);
		this.config = (this.parent.data("enable-google-map") || "").split(",");
		
		this.clearMarkers();
		this.createMap();
		
		if(this.isEnabled("geolocation")){
			this.getCurrentPosition();
		}
		
		this.resizeMap();
		
		Amslib.waitResolve("Amslib_Google_Map");
	},
	
	isEnabled: function(key)
	{
		return this.config.indexOf(key) >= 0;
	},
	
	getCurrentPosition: function()
	{
		var $this = this;
		
		navigator.geolocation.getCurrentPosition(
			$.proxy($this,"updateGeolocationPosition"),
			function(){
				$this.trigger("error","failed to obtain geolocation data");
			}
		);
	},
	
	disableGeolocation: function()
	{
		//	NOTE: I am not sure whether I want to, or am able to, disable this
	},
	
	updateGeolocationPosition: function(position)
	{
		var marker = this.createMarker(position.coords.latitude,position.coords.longitude);
		
		if(!this.currentPosition){
			this.currentPosition = marker;
			this.setZoom(15);
			this.centerMap(marker);
		}
		
		this.trigger("onUpdateGeolocation",{
			lat: position.coords.latitude,
			lng: position.coords.longitude
		});
	},	
	
	createMap: function()
	{
		this.mapObject = new google.maps.Map(this.parent[0],{
	   		zoom:		this.zoom,
	   		mapTypeId:	google.maps.MapTypeId.ROADMAP
	   	});
		
		this.resizeMap();
	},
	
	centerMap: function(marker)
	{
		if(!marker) return false;
		
		var p = marker.getPosition();
		
		this.mapObject.setCenter(p);
		
		//	NOTE: what does this mean???
		//	TODO: UPDATE => latlng.val(p.toUrlValue(48)).blur();
		
		this.resizeMap();	
	},
	
	resizeMap: function()
	{
		google.maps.event.trigger(this.mapObject, "resize");
	},
	
	setZoom: function(level)
	{
		this.mapObject.setZoom(level);
		
		this.resizeMap();
	},
	
	createMarker: function(lat,lng)
	{
		var position = new google.maps.LatLng(lat,lng);
		
		var m = new google.maps.Marker({
			map:		this.mapObject,
			draggable:	true,
			position:	position
		});
		
		var $this = this;
		
		google.maps.event.addListener(m,"dragend", function() {
			$this.centerMap(m);
			
			var p = marker.getPosition();
			$this.trigger("onDrag",p.toUrlValue(48))
	    });
		
		this.marker.push(m);	
		
		return m;
	},
	
	clearMarkers: function()
	{
		for(var a=0,len=this.marker.length;a<len;a++){
			if(typeof(this.marker[a]) == "undefined") continue;
			
			this.marker[a].setMap(null);
		}
		
		this.marker = new Array();
	},
	
	/*
function createMap(node){
	latlng = node.find("input[name='map']");
	map_node = $("<div/>").attr("class","map");
	
	node.append(map_node);
	
	googleMap = new google.maps.Map(map_node[0],{
   		zoom: 17,
   		mapTypeId: google.maps.MapTypeId.ROADMAP
   	});
	
	var valid	=	false;
	var pos		=	[0,0];
	
	if(latlng.val().length){
		var pos = latlng.val().replace(/\(|\)/g,'').split(",");
		pos[0] = parseFloat(pos[0]);
		pos[1] = parseFloat(pos[1]);
		
		if(!isNaN(pos[0]) && !isNaN(pos[1])) valid = true;
	}
	
	if(valid){
		centerMap(createMarker(new google.maps.LatLng(pos[0],pos[1])));
	}else{
		updateAddress();
	}
}

function updateAddress()
{
	var address = [
		$("textarea[name='address']").val(),
		$("input[name='city']").val(),
		$("input[name='postcode']").val()
	].join(",");
	
	if(address == ",,") address = "Barcelona";
	
	updateMap(address);
}

function updateMap(address)
{
	if(!googleMap) return false;
	
	new google.maps.Geocoder().geocode({"address":address},function(results, status) {
    	if (status == google.maps.GeocoderStatus.OK && results.length)
    	{
    		$.each(marker,function(k,v){
    			v.setMap(null);
    		});
    		marker.length = 0;
    		
    		$.each(results,function(k,v){
    			createMarker(v.geometry.location);
    		});
    		
    		if(marker.length) centerMap(marker[0]);
    	}
    });
}

function centerMap(marker)
{
	var p = marker.getPosition();
	googleMap.setCenter(p);
	latlng.val(p.toUrlValue(48)).blur();
	
	google.maps.event.trigger(googleMap, "resize");
}

function createMarker(position)
{
	var m = new google.maps.Marker({
		map: googleMap,
		draggable: true,
		position: position
	});
	
	google.maps.event.addListener(m,"dragend", function() {
		centerMap(m);
    });
	
	marker.push(m);	
	
	return m;
}
	 */
});

Amslib.loadJS("amslib.googlemap","http://maps.googleapis.com/maps/api/js?sensor=false&callback=Amslib_Google_Map.autoload");