function Dottifyme() {

	this.initialize(true);

}

Dottifyme.prototype.initialize = function(first) {
	
	this.users = new UsersCollection();
	var app = this ;
	this.me = this.loadUserSessionInfo(this.users) ;
	this.me.on("sessionload", function( event, data ) {
		app.onSessionLoad( event, data, app) ;
	} ) ;

	this.map = new Map('map', this.users);
	this.users.loadAll();
	this.userForm = new UserInfoForm( this ) ;
	this.loginForm = new LoginForm( this ) ;
	this.initWordCloud();
}

Dottifyme.prototype.onSessionLoad = function( event, data, app ) {
	/*
	var url = window.location.href ;
	if (url.indexOf("?")>-1){
		url = url.substr(0,url.indexOf("?"));
	} */
	var url = "http://dottify.me" ;
	
	var user = data.user ;
	if( user.data != null ) {
		if( user.data.refid != null ) {
			url += "?refid=" + user.data.refid ;
		}
	}
	app.initShare( url ) ;
}

Dottifyme.prototype.initShare = function ( shareUrl) {
	var divId = "share" ;
	$('#' + divId).empty() ;	// in case this a reload
    $('#' + divId).share({
        networks: ['facebook','twitter','email','pinterest','googleplus','linkedin'],
        urlToShare: shareUrl
    });
}

Dottifyme.prototype.createUserFromZip = function(zipCode) {
	var dottify = this;
	return User.createFromZipCode(zipCode).done(function(user) {
		dottify.users.add(user)
		dottify.map.zoomTo(user.coordinate(), dottify.users);
	}).fail(function() {
		Dottify.alert("Something went wrong with our servers :(. " +
		 							"Carrier pigeons have been dispatched to the " +
		 							"developers. Try again soon! email:bugs@dottify.org");
	});
}

Dottifyme.prototype.createUser = function(userin) {
	var dottify = this;
	return User.create(userin).done(function(user) {
		dottify.users.add(user)
		dottify.map.zoomTo(user.coordinate(), dottify.users);
	}).fail(function() {
		Dottify.alert("Something went wrong with our servers :(. " +
		 							"Carrier pigeons have been dispatched to the " +
		 							"developers. Try again soon! email:bugs@dottify.org");
	});
}

/*
 * deprecated - moved to user form logout.
 * and we redirect through setcookie.php to clear cookies
Dottifyme.prototype.logout = function() {
	this.me = this.me.logout() ;
	this.map.removeMyMarker();
	this.map.defaultZoom() ;

}
*/


Dottifyme.prototype.loadUserSessionInfo = function( users ) {

	var uuid = this.getUrlParam( "uuid" ) ;
	var refid = this.getUrlParam("refid") ;
	var uinfo = new UserSessionInfo() ;
	uinfo.load( uuid, refid, users ) ;
	return uinfo ;

} 

Dottifyme.prototype.getUrlParam = function( paramName ) {
	var results = new RegExp('[\\?&]' + paramName + '=([^&#]*)').exec(window.location.href);
	if( results != null ) {
		return results[1] ;
	}
	return null ;
}

Dottifyme.prototype.initWordCloud = function() {

	
	Stats.getIdentityTags().done( function( data){
		//console.log( JSON.stringify(data));
		var termspans = "";
		for( var tag in data )  {
			//console.log( tag + ":" + data[tag] );
			termspans += '<span data-weight="' + data[tag] + '">' + tag + '</span>' ;
		}
		console.log( termspans);
		$("#wordcloud").html( termspans ) ;
		
		$("#wordcloud").awesomeCloud({
			"size" : {
				"grid" : 12,
				"factor" : 15,
				"normalize": false
			},
			"options" : {
				"color" : "random-dark",
				"rotationRatio" : 0.35
				
			},
			"font" : "Verdana, Geneva, sans-serif",
			"shape" : "square"
		});
		
	});
	

	
}


Dottifyme.alert = function(text) {
	// We're wrapping `alert` so if we want to use a modal
	// or something later it will be easier to do so.
	window.alert(text);
}

$(document).ready(function () {
	var dottifyme = new Dottifyme('#page');
});
