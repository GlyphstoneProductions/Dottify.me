function User(data) {
	this.data = data;
	this.isMe = false ;
}

User.prototype.coordinate = function() {
	return {
		lat: this.data.latitude,
		lng: this.data.longitude
	}
}

User.prototype.hasCoordinate = function() {
	return this.data.latitude && this.data.longitude;
}

User.prototype.isMe = function() {
	return this.isMe ;
}

User.prototype.setIsMe = function( isMe ) {
	this.isMe = isMe ;
	
}

User.createFromZipCode = function(zipCode) {
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'POST',
		data: JSON.stringify({ zipcode: zipCode }),
		dataType: 'json',
		url: 'api/user'
	}).done(function(data){
		// We're using our own promise so we can co-erce
		// the JSON blob we get back into a User object
		// This may be completely unnecessary.
		apiPromise.resolve(new User(data));
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}
