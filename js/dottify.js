

	var US_GEO_CTR_LAT = 39.83;
	var US_GEO_CTR_LNG = -98.58;
	var NA_ZOOM = 3;


	var Map = function(mapDivId) {
		this.attribText = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://cloudmade.com">CloudMade</a>';
		this.tileUrl = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';

		this.leafletMap = L.map(mapDivId).setView([US_GEO_CTR_LAT, US_GEO_CTR_LNG], NA_ZOOM);
		L.tileLayer(this.tileUrl, {
			attribution: this.attribText
		}).addTo(this.leafletMap);
	};

	Map.prototype.zoomTo = function(coordinates) {
		this.leafletMap.setView([coordinate.lat, coordinate.lng], 4);
	}

function Dottify() {
	this.map = new Map('map');
	$('#zip').zipCodeForm(this);
}

Dottify.prototype.createUser = function(zipCode) {

}

$(document).ready(function () {
	var dottify = new Dottify('#page');
});
