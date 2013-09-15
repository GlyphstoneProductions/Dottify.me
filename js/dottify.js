function Dottify() {
	this.users = new UsersCollection();
	this.map = new Map('map', this.users);
	this.users.loadAll();
	$('#zip').zipCodeForm(this);
}

Dottify.prototype.createUser = function(zipCode) {
	var dottify = this;
	return User.createFromZipCode(zipCode).done(function(user) {
		dottify.users.add(user)
		dottify.map.zoomTo(user.coordinate(), dottify.users);
	}).fail(function() {
		Dottify.alert("Something went wrong with our servers :(. " +
		 							"Carrier pigeons have been dispatched to the " +
		 							"developers. Try again soon!");
	});
}

Dottify.alert = function(text) {
	// We're wrapping `alert` so if we want to use a modal
	// or something later it will be easier to do so.
	window.alert(text);
}

$(document).ready(function () {
	var dottify = new Dottify('#page');
});
