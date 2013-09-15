// Initially, zoom to 'Murica.

var STARTING_LAT = 39.83;
var STARTING_LONG = -98.58;
var STARTING_ZOOM = 3;


var Map = function(mapDivId) {
	this.attribText = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://cloudmade.com">CloudMade</a>';
	this.tileUrl = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';

	this.leafletMap = L.map(mapDivId).setView([STARTING_LAT, STARTING_LONG], STARTING_ZOOM);
	L.tileLayer(this.tileUrl, {
		attribution: this.attribText
	}).addTo(this.leafletMap);
};

Map.prototype.zoomTo = function(coordinates) {
	this.leafletMap.setView([coordinate.lat, coordinate.lng], 4);
}