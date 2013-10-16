// Initially, zoom to 'Murica.
var STARTING_LAT = 39.83;
var STARTING_LONG = -98.58;
var STARTING_ZOOM = 3;


var Map = function(mapDivId, users) {
	
	this.mapDivId = mapDivId;
	this.attribText = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://cloudmade.com">CloudMade</a>';
	this.tileUrl = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';

	this.leafletMap = L.map(mapDivId).setView([STARTING_LAT, STARTING_LONG], STARTING_ZOOM);
	L.tileLayer(this.tileUrl, {
		attribution: this.attribText
	}).addTo(this.leafletMap);
	var map = this;
	users.on('added', function(e, user) {
		map.addUser(user);
	});
	
	this.myMarker = null ;
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
	
	var popuptext = 'Hello ' + user.data.username + '<br/> ' ;

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
	
	var theMap = this ;
	if( user.isMe ) {
		if (user.hasCoordinate()) {
			this.myMarker = new L.Marker( user.coordinate(), {icon: customIcon, draggable: true }) ; 
			this.myMarker.on('dragend', function(e) {
				// alert( "drag done! " + e.target.getLatLng() ) ;
				// TODO: trigger an event to save new lat/long
			} );
			
			this.myMarker.addTo(this.leafletMap).bindPopup( popuptext ) ;
			this.zoomTo( user.coordinate() ) ;
		} else {
			// TODO: Alert user that they do not have a coordinate and should input zip or choose alternative zip - or report zip missmatch 
		}
	} else {
		if (user.hasCoordinate()) {
			new L.Marker( user.coordinate()).addTo(this.leafletMap);
		}
	}

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