function User(data) {
	this.data = data;
	this.isMe = false ;
	this.errors = null ;
	this.survey = null ;
	this.Status = {
		"UNCLAIMED" : { id: 0, name: "unclaimed", desc: "user has not claimed dott" },
		"CLAIMED" : { id: 1, name: "claimed", desc: "is a claimed dott" },
		"SUSPENDED" : { id: 2, name: "suspended", desc: "has been suspended for cause" }
	} ;
	
	this.Type = {
		"REGISTERED" : { id: 0, name: "registered", desc: "unvalidated user"} ,
		"VALIDATED" :{ id: 1, name: "validated", desc: "validated user"} ,
		"RESEARCHER" :{ id: 2, name: "researcher", desc: "research user"} ,
		"ADMIN" :{ id: 10, name: "administrator", desc: "administrative user"} 
	} ;
	this.Class = {
		"TRANS" : { id: 0, name: "a Trans* person", desc: "trans* or gender variant user"} ,
		"QUESTIONING" :{ id: 1, name: "a gender-questioning person", desc: "gender questioning user"} ,
		"ALLY" :{ id: 2, name: "an ally", desc: "ally user"} 
	} ;
}

User.prototype.coordinate = function() {
	return {
		lat: this.data.latitude,
		lng: this.data.longitude
	}
}

User.prototype.getConstById = function( constlist, id ) {
	for( var key in constlist ) {
		var item = constlist[key] ;
		if( item.id == id ) return item ;
	}
	return null ;
}

User.prototype.getUserClass = function() {
	return this.getConstById( this.Class, this.data.userclass) ;
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

User.prototype.toJsonString = function() {
	var str = JSON.stringify( { 
		id: this.data.id, 
		uuid: this.data.uuid,
		refid: this.data.refid,
		ver: this.data.ver,
		thisver: this.data.thisver,
		username: this.data.username,
		refuser: this.data.refuser,
		password: this.data.password,
		email: this.data.email,
		zipcode: this.data.zipcode,
		countrycode: this.data.countrycode,
		usertype: this.data.usertype,
		userstatus: this.data.userstatus,
		userclass: this.data.userclass,
		mecon: this.data.mecon,
		refuserid: this.data.refuserid,
		staylogged: this.data.staylogged
	}) ;
	return str ;
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

User.prototype.validate = function() {
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'POST',
		data: this.toJsonString() ,
		dataType: 'json',
		url: 'api/user/validate'
	}).done(function(data){
		apiPromise.resolve(data);
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}

User.create = function( user ) {
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'POST',
		data: user.toJsonString() ,
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

User.update = function( user, norev ) {
	var apiPromise = $.Deferred();
	var updateurl = 'api/user' ;
	if( norev ) {
		updateurl += '?norev=1' ;
	}
	$.ajax({
		method: 'PUT',
		data: user.toJsonString() ,
		dataType: 'json',
		url: updateurl
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

User.addSurvey = function( survey) {
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'POST',
		data: JSON.stringify( survey ),
		dataType: 'json',
		url: 'api/user/basesurvey'
	}).done(function(data){
		apiPromise.resolve( data );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}

User.updatePosition = function( user ) {
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'POST',
		data: JSON.stringify( user ),
		dataType: 'json',
		url: 'api/user/position'
	}).done(function(data){
		apiPromise.resolve( data );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}

User.getSurvey = function( id, version) {
	var geturl = 'api/user/basesurvey/' + id + '/' + version ;
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'GET',
		dataType: 'json',
		url: geturl,
	}).done(function(data){
		console.log( "get survey returned") ;
		apiPromise.resolve( data );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}

User.byEmail = function( email ) {
	var geturl = 'api/user/byemail?magic=__MAGICCOOKIE__&email=' + email ;
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'GET',
		dataType: 'json',
		url: geturl,
	}).done(function(data){
		console.log( "by email:" + data ) ;
		apiPromise.resolve( data );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}

User.login = function( username, password ) {
	var geturl = 'api/user/login/' + username + '/' + password ;
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'GET',
		dataType: 'json',
		url: geturl,
	}).done(function(data){
		console.log( "login:" + data ) ;
		apiPromise.resolve( data );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}

User.sendlink = function( email ) {
	var geturl = 'api/user/sendlink?email=' + email ;
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'GET',
		dataType: 'json',
		url: geturl,
	}).done(function(data){
		console.log( "sentlink:" + data.success ) ;
		apiPromise.resolve( data );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;	
}
