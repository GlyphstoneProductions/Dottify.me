function Dottifyme() {

	this.users = new UsersCollection();

	this.me = this.loadUserSessionInfo(this.users) ;
	this.map = new Map('map', this.users);
	this.users.loadAll();
	this.userForm = new UserInfoForm( this ) ;
	
	initShare('share');

}

Dottifyme.prototype.createUser = function(zipCode) {
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


Dottifyme.prototype.loadUserSessionInfo = function( users ) {

	var uuid = this.getUrlParam( "uuid" ) ;
	var uinfo = new UserSessionInfo() ;
	uinfo.load( uuid, users ) ;
	return uinfo ;

} 

Dottifyme.prototype.getUrlParam = function( paramName ) {
	var results = new RegExp('[\\?&]' + paramName + '=([^&#]*)').exec(window.location.href);
	if( results != null ) {
		return results[1] ;
	}
	return null ;
}


Dottifyme.alert = function(text) {
	// We're wrapping `alert` so if we want to use a modal
	// or something later it will be easier to do so.
	window.alert(text);
}

$(document).ready(function () {
	var dottifyme = new Dottifyme('#page');
});
