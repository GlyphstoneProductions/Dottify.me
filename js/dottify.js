
	var MAP_DIV_NAME = 'map';
	var ZIP_DIV_NAME = 'zip';

	var US_GEO_CTR_LAT = 39.83;
	var US_GEO_CTR_LNG = -98.58;
	var NA_ZOOM = 3;
	var map;


	$(document).ready(function () {
		map = new Map();
		$('#zip').mapZipCodeField({
			createUser: createUser
		});
	});

	var Map = function() {
		this.attribText = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://cloudmade.com">CloudMade</a>';
		this.tileUrl = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';

		this.leafletMap = L.map(MAP_DIV_NAME).setView([US_GEO_CTR_LAT, US_GEO_CTR_LNG], NA_ZOOM);
		L.tileLayer(this.tileUrl, {
			attribution: this.attribText
		}).addTo(this.leafletMap);
	};

	Map.prototype.zoomTo = function(coordinates) {
		this.leafletMap.setView([coordinate.lat, coordinate.lng], 4);
	}
