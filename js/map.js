// Initially, zoom to 'Murica.
var STARTING_LAT = 39.83;
var STARTING_LONG = -98.58;
var STARTING_ZOOM = 3;


var Map = function(mapDivId, users) {
	
	this.me = null ;
	this.myMarker = null ;
	this.users = users ;
	this.markers = {} ;
	this.mapDivId = mapDivId;
	this.attribText = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://cloudmade.com">CloudMade</a>';
	this.tileUrl = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';

	this.leafletMap = L.map(mapDivId).setView([STARTING_LAT, STARTING_LONG], STARTING_ZOOM);
	
	this.dottLayer = L.tileLayer(this.tileUrl, {
		attribution: this.attribText
	}) ;
	
	//this.tdorLayer = new L.LayerGroup();
	this.myRefLayer = new L.LayerGroup() ;
	
	var map = this;
	
	this.heatmapLayer  = new
    L.TileLayer.HeatCanvas({},
    		{
    		'step': 0.3,
    		'degree':HeatCanvas.LINEAR, 
    		'opacity':0.5}
    );
	
	this.dottLayer.addTo(this.leafletMap);
	//this.tdorLayer.addTo(this.leafletMap);
	this.myRefLayer.addTo(this.leafletMap);
	this.leafletMap.addLayer( this.heatmapLayer ) ;
	
	
	// add layer control pad
	var overlayMaps = {
			 'Heatmap': this.heatmapLayer
			 , 'My Refs': this.myRefLayer
			 // , 'TDOR': this.tdorLayer
			 //, 'Dotts' : this.dottLayer
			 };
	
	var controls = L.control.layers(null, overlayMaps, {collapsed: false});
	//var controls = L.control.layers(overlayMaps, null , {collapsed: false});
    controls.addTo(this.leafletMap);	

   	users.on('added', function(e, user) {
		map.addUser(user);
	});

};

Map.prototype.removeMyMarker = function() {
	this.leafletMap.removeLayer( this.myMarker) ;
	this.myMarker = null ;
}

Map.prototype.defaultZoom = function() {
	this.leafletMap = this.leafletMap.setView([STARTING_LAT, STARTING_LONG], STARTING_ZOOM);
}

Map.prototype.addUser = function(user) {
	
	var customIcon = L.icon({
	    iconUrl: 'images/' + user.data.mecon ,
	    shadowUrl: 'images/pinshadow.png',

	    iconSize:     [50, 115], // size of the icon
	    shadowSize:   [54, 38], // size of the shadow
	    iconAnchor:   [22, 115], // point of the icon which will correspond to marker's location
	    shadowAnchor: [-5, 42],  // the same for the shadow
	    popupAnchor:  [5, -108] // point from which the popup should open relative to the iconAnchor
	});
	
	/*
	var tdorIcon = L.icon({
	    iconUrl: 'images/pintdor.png' ,
	    shadowUrl: 'images/pinshadow.png',

	    iconSize:     [50, 115], // size of the icon
	    shadowSize:   [54, 38], // size of the shadow
	    iconAnchor:   [22, 115], // point of the icon which will correspond to marker's location
	    shadowAnchor: [-5, 42],  // the same for the shadow
	    popupAnchor:  [5, -108] // point from which the popup should open relative to the iconAnchor
	});
	*/
	
	var popuptext = "" ;
	var showPopup = false ;
	
	var now = new Date().getTime() / 1000;
		
	if( user.data.usersetloc == 0 ) {
		showPopup = true ;
		popuptext = "Drag your icon to where<br/>you would like it to appear.<br/>Please protect your privacy<br/> by <b>Not</b> placing it on your actual residence!";
		
	} else {
		popuptext = 'Hello ' + user.data.username + '<br/> ' ;
	
		if( user.data.refcount > 0 ) {
			popuptext += 'You have directly referred: ' + user.data.refcount + ' users!<br/>' ;
			popuptext += 'Your referral rank is #' + user.data.refrank + "<br/>" ;
		} else {
			popuptext += "You don't have any referrals yet<br/>Share a link on your favorite<br/>social media site and bump up your rank!<br/>" ;
		}
		if( user.data.numOpenQuestions == 0 ) {
			popuptext += "Good work.  You've answered all survey questions." ;
		} else {
			popuptext += "You have " + user.data.numOpenQuestions + " unanswered survey questions <br/>";
		}
	}
	
	var theMap = this ;
	if( user.isMe ) {
		console.log( 'User is me!: ' + user.data.uuid ) ;
		this.me = user ;
		if (user.hasCoordinate()) {
			var userMarker = this.markers[user.data.uuid] ;
			if( userMarker != null ) {
				// remove placeholder marker.
				console.log("Removing placeholder marker in favor of myMarker" ) ;
				
				this.leafletMap.removeLayer( userMarker ) ;
			}
			
			this.myMarker = new L.Marker( user.coordinate(), {icon: customIcon, draggable: true }) ; 
			this.markers[user.data.uuid] = this.myMarker ;
			this.myMarker.on('dragend', function(e) {
				// alert( "drag done! " + e.target.getLatLng() ) ;
				var newPos = e.target.getLatLng() ;
				var tmpUser = user.data ;
				tmpUser.latitude = newPos.lat ;
				tmpUser.longitude = newPos.lng ;
				User.updatePosition( tmpUser ) ;
				alert("User position updated") ;
				
			} );
			
			this.myMarker.addTo(this.leafletMap).bindPopup( popuptext ) ;
			//this.myMarker.addTo( this.dottLayer).bindPopup(popupText);
			
			this.zoomTo( user.coordinate() ) ;
			if( showPopup ) {
				this.myMarker.openPopup();
			}
			
			this.loadMyRefUsers( user.data.id, this.users, this.myRefLayer )
		} else {
			// TODO: Alert user that they do not have a coordinate and should input zip or choose alternative zip - or report zip missmatch 
		}
	} else {
		if (user.hasCoordinate()) {
			
			if( user.data.usertype == 3 ) {
				/*
				// tdor marker
				var userMarker = this.markers[user.data.uuid] ;
				if( userMarker == null ) {
					console.log( "add tdor marker: ");
					userMarker = new L.Marker( user.coordinate(), {icon: tdorIcon }) ; 
					this.markers[user.data.uuid] = userMarker ;
					//userMarker.addTo(this.leafletMap).bindPopup( user.data.notes ) ;
					var popuptext = user.data.notes.replace(/--/g, "<br/>")
					userMarker.addTo(this.tdorLayer).bindPopup( popuptext, { maxHeight: 350 , minWidth: 400 } ) ;
				}
				*/
			} else {
				//	console.log( "add user type: " + user.data.type)
				var userMarker = this.markers[user.data.uuid] ;
				if( userMarker == null ) {
					userMarker = new L.Marker( user.coordinate()).addTo(this.leafletMap);
					//userMarker = new L.Marker( user.coordinate()).addTo( this.dottLayer) ;
					
					this.markers[user.data.uuid] = userMarker ;
				}
				
				// add user to heatmap with value of 20 just for fun.

			}
		    this.heatmapLayer.pushData( user.coordinate().lat, user.coordinate().lng, 20);
		    
		    // if this user was referred by me, add it to the refs layer with their personal pin.
		    if( this.me != null && user.data.refuser == this.me.data.id) {
		    	this.createRefUserPin( user, this.myRefLayer ) ;
		    }
		}
	}

}

Map.prototype.loadMyRefUsers = function( refid, users, myRefsLayer ) {
	
	for( var n = 0; n < users.length; n++ ) {
		var user = users[n] ;
		if( user.data.refuser == refid) {
			
			this.createRefUserPin( user, myRefsLayer ) ;

		}
	}
}

Map.prototype.createRefUserPin = function( user, myRefsLayer ) {
	var customIcon = L.icon({
	    iconUrl: 'images/' + user.data.mecon ,
	    shadowUrl: 'images/pinshadow.png',

	    iconSize:     [50, 115], // size of the icon
	    shadowSize:   [54, 38], // size of the shadow
	    iconAnchor:   [22, 115], // point of the icon which will correspond to marker's location
	    shadowAnchor: [-5, 42],  // the same for the shadow
	    popupAnchor:  [5, -108] // point from which the popup should open relative to the iconAnchor
	});
	
	var refmarker = new L.Marker( user.coordinate(), {icon: customIcon }) ; 
	
	refmarker.addTo(myRefsLayer) ;
}

/*
Map.prototype.show = function( obj, map ) {
	
	
	var out = map.dump(obj, "", 0, map ) ;
	alert( out ) ;
}

Map.prototype.dump = function( obj, nest, level, map ) {
	if( level > 2 ) {
		return "<<OVERFLOW>>" ;
	}
	
	var out = "";
	$.each( obj, function( i,n ) {
		var nval = n ;
		if( typeof n === "object")  {
			nval = map.dump( n, nest + "  ", level + 1, map) ;
		}
		out += nest + i + "=[" + nval + "]\n" ;
	} ) ;
	
	return out ;
}
*/


Map.prototype.zoomTo = function(coordinate) {
	this.leafletMap.setView([coordinate.lat, coordinate.lng], 9);
}