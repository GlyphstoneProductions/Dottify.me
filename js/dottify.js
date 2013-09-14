
	var MAP_DIV_NAME = 'map';
	var map;

	$(document).ready(function () {
		map = new Map();
		$('button').button();
	});

	var Map = function() {
		this.attribText = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://cloudmade.com">CloudMade</a>';
		this.tileUrl = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';

		this.leafletMap = L.map(MAP_DIV_NAME).setView([47.61232, -122.32658], 10);
		L.tileLayer(this.tileUrl, {
			maxZoom: 18,
			attribution: this.attribText
		}).addTo(this.leafletMap);
	};

	var handleEnterZipCode = function (event) {
		var zipCode = $('#zipCode').val();
		if (zipCode && !isNan(zipCode)) {
			createUser();
		}
	};