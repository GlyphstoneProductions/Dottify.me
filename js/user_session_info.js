function UserSessionInfo( data ) {
	this.passedUuid = null ;
	this.data = data  ;
	this.users = null ;
}

UserSessionInfo.prototype.setData = function( data ) {
	
	this.data = data ;
}

UserSessionInfo.prototype.load = function( uuid, refid, users, reload) {
	console.log( 'loading user info: ' + uuid) ;
	this.users = users ;
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
		
		if( data.user.refuser == null && (refid != null && refid != 0 )) {
			console.log( "setting refuser: " + refid ) ;
			// load the passed-in refid is the user was not loaded 
			// so that we can use it when creating a new user.
			data.user.refuser = refid ;
		}
		info.data = data ;

		user.setIsMe(true) ;
	
		console.log( 'user uuid: ' + user.data.uuid ) ;
		console.log( 'user refid: ' + user.data.refid) ;
		console.log( 'user refuser: ' + user.data.refuser ) ;
		
		if( users != null ) {
			users.add(user, true ) ; // do overwrite an existing user in collection a
		}
		
		data.reload = reload ;
		info.trigger("sessionload", data);	

	});
	
}

UserSessionInfo.prototype.logout = function() {
	var url = 'api/usersession/logout' ;
	console.log( "calling logout");
	var usinfo = this;
	$.getJSON( url ).done(function(data){
		console.log("User Logged out: " + data) ;
		var data = usinfo.load(null, null, usinfo.users) ;
		usinfo.trigger("sessionlogout", data ) ;
		return data ;
	} ) ;

}

UserSessionInfo.prototype.trigger = function(eventName, eventData) {
	$(this).trigger(eventName, eventData);
}

UserSessionInfo.prototype.on = function(eventName, callback) {
	$(this).on(eventName, callback)
}

