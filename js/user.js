function User(data) {
	this.data = data;
	this.isMe = false ;
	this.Status = {
		"UNCLAIMED" : { id: 0, name: "unclaimed", desc: "user has not claimed dott" },
		"CLAIMED" : { id: 1, name: "claimed", desc: "is a claimed dott" },
		"SUSPENDED" : { id: 2, name: "suspended", desc: "has been suspended for cause" }
	} ;
	
	registered, validate, researcher, adminstrator
	this.Type = {
		"REGISTERED" : { id: 0, name: "registered", desc: "unvalidated user"} ,
		"VALIDATED" :{ id: 1, name: "validated", desc: "validated user"} ,
		"RESEARCHER" :{ id: 2, name: "researcher", desc: "research user"} ,
		"ADMIN" :{ id: 10, name: "administrator", desc: "administrative user"} 
	} ;
	this.Class = {
		"TRANS" : { id: 0, name: "transgender", desc: "trans* or gender variant user"} ,
		"QUESTIONING" :{ id: 1, name: "questioning", desc: "gender questioning user"} ,
		"ALLY" :{ id: 2, name: "ally", desc: "ally user"} 
	} ;
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
