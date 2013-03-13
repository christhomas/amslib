var Amslib_Google_Map = my.Amslib_Google_Map = my.Class(my.Amslib,
{
	zoom:		9,
	mapObject:	false,
	marker:		false,
	events:		$("<div/>"),
	
	STATIC: {
		autoload: function(){
			console.log("autoloading");
			$(Amslib_Google_Map.options.autoload).each(function(){
				console.log("create new object");
				new Amslib_Google_Map(this);
			});
		},
		
		options: {
			amslibName:	"Amslib_Google_Map",
			autoload:	"[data-enable-google-map]"
		}
	},
	
	constructor: function(image,container){
		console.log("constructed");
		Amslib_Google_Map.Super.call(this,image,Amslib_Google_Map.options.amslibName);
		
		this.marker = new Array();
		
		var latlng = new google.maps.LatLng(-34.397, 150.644);
		
		this.createMap();
		this.centerMap(this.createMarker(latlng));
	},
	
	createMap: function()
	{
		console.log("creating map");
		this.mapObject = new google.maps.Map(this.parent[0],{
	   		zoom:		this.zoom,
	   		mapTypeId:	google.maps.MapTypeId.ROADMAP
	   	});
		
		this.resizeMap();
	},
	
	centerMap: function(marker)
	{
		console.log("centering map");
		var p = marker.getPosition();
		
		this.mapObject.setCenter(p);
		
		//TODO: UPDATE => latlng.val(p.toUrlValue(48)).blur();
		
		this.resizeMap();	
	},
	
	resizeMap: function()
	{
		google.maps.event.trigger(this.mapObject, "resize");
	},
	
	createMarker: function(position)
	{
		var m = new google.maps.Marker({
			map:		this.mapObject,
			draggable:	true,
			position:	position
		});
		
		var $this = this;
		
		google.maps.event.addListener(m,"dragend", function() {
			$this.centerMap(m);
			
			var p = marker.getPosition();
			$this.events.trigger("onDrag",p.toUrlValue(48))
	    });
		
		this.marker.push(m);	
		
		return m;
	},
	
	on: function(event,callback)
	{
		this.events.on(event,callback);
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