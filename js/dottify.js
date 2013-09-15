function Dottify() {
	this.map = new Map('map');
	$('#zip').zipCodeForm(this);
}

Dottify.prototype.createUser = function(zipCode) {

}

$(document).ready(function () {
	var dottify = new Dottify('#page');
});
