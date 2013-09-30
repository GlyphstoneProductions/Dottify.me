function UserInfoForm( app) {
	this.user = null ;
	this.title = "User Form" ;
	this.app = app ;
	var _this = this ;
	this.app.me.on("sessionload", function( event, data ) {
		_this.onSessionLoad( event, data, _this) ;
	} ) ;
	this.initialize() ;
	this.initVisitor() ;
	
}

UserInfoForm.prototype.initialize = function() {
	
	$("#userinfo-ribbon").click(function(){
		 if( $("#userinfo-body").css('display') == "none") {
			 $("#userinfo-body").slideDown("fast");
		 } else {
		 	// $("#userinfo-body").slideUp("slow");
		 }

	});
}

UserInfoForm.prototype.expand = function() {
 	$("#userinfo-body").slideDown("fast");	
}

UserInfoForm.prototype.collapse = function() {
	
 	$("#userinfo-body").slideUp("fast");	
}

/**
 * Set up user signup form.
 */
UserInfoForm.prototype.initRegistered = function(user) {
	
	var userform = this ;	
	$("#userinfo-form").empty();
	
	var greeting = "Welcome back" ;
	if( user.data.username != null ) greeting += ', ' + user.data.username ;
	$('#usergreeting').html( greeting ) ;
	$('#mecon').html('<img class="meconthumb" src="images/' + user.data.mecon + '">') ;
	$('#logout').show();
	$('#logout').click( function(){
		userform.logout( userform ) ;
	}) ;
	
    var html = '<input id="zipcode" name="zipcode" type="text" placeholder="Enter your 5-digit zip code" maxlength="5" value="' + user.data.zipcode + '" /><br/> ';
	$("#userinfo-form").append(html) ;
	
	html = '<div id="imagepicker_scroll" > \
		<div id="imagepicker_inner" >\
	    <select class="image-picker" > '
		for( var n = 1; n < 31; n++ ) {
			var num = ((n < 10)? '00' : '0' ) + n ;
			var pinName = 'pin' + num + '.png' ;
			var selected = '' ;
			if( pinName == user.data.mecon ) selected = " selected" ;
			html += '<option data-img-src="images/' + pinName + '" value="' + pinName + '" ' + selected + '>pin</option>' ;		
		}
		html += '</select>\
		</div> \
		</div> \
		<br/> \
		<button id="updateuser" >Update Profile</button>&nbsp;&nbsp;<button id="cancel" >Close</button> \
		<div id="usererror"></div>' ;
	
	$("#userinfo-form").append(html) ;
		
	$("select.image-picker").imagepicker() ;
	

	$("button#updateuser").click( function(){
		userform.updateUser( userform ) ;
	} ) ;
	
	$("button#cancel").click( this.collapse ) ;
	
}

UserInfoForm.prototype.initVisitor = function() {
	var userform = this ;	
	$("#userinfo-form").empty();
	
	$('#logout').hide();
	
    var html = 'I am:<br/> \
    	<input id="type_trans" type="radio" name="usertype" value="0">Transgender/Transsexual/Gender non-conforming<br/> \
    	<input id="type-quest" type="radio" name="usertype" value="1">Curious about or Questioning my gender<br/> \
    	<input id="type-ally" type="radio" name="usertype" value="2">An ally or supporter of Trans* People<br/> \
		<input id="zipcode" name="zipcode" type="text" placeholder="Enter your 5-digit zip code" maxlength="5" /><br/> ';
	$("#userinfo-form").append(html) ;
	
	html = '<div id="imagepicker_scroll" > \
		<div id="imagepicker_inner" >\
	    <select class="image-picker" > '
		for( var n = 1; n < 31; n++ ) {
			var num = ((n < 10)? '00' : '0' ) + n ;
			html += '<option data-img-src="images/pin' + num +'.png" value="pin' + num + '.png" >pin</option>' ;		
		}
		html += '</select>\
		</div> \
		</div> \
		<br/> \
		<button id="adduser" >Put me on the Map!</button>&nbsp;&nbsp;<button id="cancel" >Not now, thanks</button> \
		<div id="usererror"></div>' ;
	
	$("#userinfo-form").append(html) ;
		
	$("select.image-picker").imagepicker() ;

	$("button#adduser").click( function(){
		userform.addUser( userform ) ;
	} ) ;
	
	$("button#cancel").click( this.collapse ) ;
	
}




UserInfoForm.prototype.addUser = function( userform ) {
	// validate data first
	//call the application to adopt or create a user

	console.log( 'Add User: ' + userform.title ) ;
	userform.collapse() ;
}

UserInfoForm.prototype.updateUser = function( userform ) {
	// validate data first
	//call the application to adopt or create a user

	console.log( 'Update User: ' + userform.title ) ;
	userform.collapse() ;
}

UserInfoForm.prototype.onSessionLoad = function(event, data, _this) {
	console.log( "called from userform:" + _this.title ) ;
	var last = data.lastVisit ;
	console.log( 'async info: ' + data.user.data.zipcode + ' on ' + last ) ;
	_this.user = data.user ;

	if( data.user.data.uuid != null && data.user.data.userstatus == data.user.Status["CLAIMED"].id ) {
		// user is logged in.
		// display user info, welcome and fill form for account info update
		console.log("registered user" ) ;
		_this.initRegistered( data.user ) ;

	} else {
		console.log( "unregistered user: " + data.user.data.userstatus ) ;
		// user form should be already set up for user registration
	}
	
	// this.app.me = data ;
	
}