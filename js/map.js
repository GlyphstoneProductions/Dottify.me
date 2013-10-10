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
		popuptext += 'You have directly refered: ' + user.data.refcount + ' users!<br/>' ;
		popuptext += 'Your referral rank is #' + user.data.refrank + "<br/>" ;
	} else {
		popuptext += "You don't have any referrals yet<br/>Share a link on your favorite<br/>social media site and bump up your rank!<br/>" ;
	}
	
	if( user.isMe ) {
		if (user.hasCoordinate()) {
			this.myMarker = new L.Marker( user.coordinate(), {icon: customIcon}) ; 
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

Map.prototype.zoomTo = function(coordinate) {
	this.leafletMap.setView([coordinate.lat, coordinate.lng], 9);
}