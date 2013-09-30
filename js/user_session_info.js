function UserSessionInfo( data ) {
	this.passedUuid = null ;
	this.data = data  ;
	
}

UserSessionInfo.prototype.setData = function( data ) {
	
	this.data = data ;
}

UserSessionInfo.prototype.load = function( uuid, users) {
	console.log( 'loading user info: ' + uuid) ;
	if( uuid == null ) {
		console.log( 'empty uuid' ) ;
	}
	this.passedUuid = uuid ;
	var info = this ;
	
	var url = 'api/usersession/' ;
	if( uuid != null ) {
		url += uuid ;
	} else {
		url += '*' ;
	}
	$.getJSON( url ).done(function(data){
		// TODO: best way to detect successful get of valid user
		
		var user = new User(data.user) ;
		data.user = user ;
		info.data = data ;

		user.setIsMe(true) ;
		console.log( 'user refid: ' + user.data.refid) ;		
		console.log( 'user uuid: ' + user.data.uuid ) ;

		users.add(user, true ) ; // do overwrite an existing user in collection a
		info.trigger("sessionload", data);	

	});
	
}

UserSessionInfo.prototype.trigger = function(eventName, eventData) {
	$(this).trigger(eventName, eventData);
}
UserSessionInfo.prototype.on = function(eventName, callback) {
	$(this).on(eventName, callback)
}

