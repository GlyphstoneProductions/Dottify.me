function Dottify() {

	this.users = new UsersCollection();
	$('#userform').zipCodeForm(this);
	this.me = this.loadUserSessionInfo(this.users) ;
	this.map = new Map('map', this.users);
	this.users.loadAll();

	initShare('share');
	this.me.on("sessionload", this.displaySessionInfo ) ;
	// set up listener for now
	$("#userinfo-ribbon").click(function(){
		 if( $("#userinfo-body").css('display') == "none") {
			 $("#userinfo-body").slideDown("slow");
		 } else {
		 	$("#userinfo-body").slideUp("slow");
		 }
	
	});
	$("select").imagepicker() ;
}

Dottify.prototype.createUser = function(zipCode) {
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


Dottify.prototype.loadUserSessionInfo = function( users ) {

	var uuid = this.getUrlParam( "uuid" ) ;
	var uinfo = new UserSessionInfo() ;
	uinfo.load( uuid, users ) ;
	return uinfo ;

} 

Dottify.prototype.displaySessionInfo = function(event, data) {
	var last = data.lastVisit ;
	console.log( 'async info: ' + data.user.data.zipcode + ' on ' + last ) ;
	if( data.user.data.uuid != null ) {
		$('#userform').hide() ;
	}
	this.me = data ;
	
}


Dottify.prototype.getUrlParam = function( paramName ) {
	var results = new RegExp('[\\?&]' + paramName + '=([^&#]*)').exec(window.location.href);
	if( results != null ) {
		return results[1] ;
	}
	return null ;
}


Dottify.alert = function(text) {
	// We're wrapping `alert` so if we want to use a modal
	// or something later it will be easier to do so.
	window.alert(text);
}

$(document).ready(function () {
	var dottify = new Dottify('#page');
});
