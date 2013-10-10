function LoginForm( app) {
	this.app = app ;
	var _this = this ;
	this.initForm(null) ;
	
}


LoginForm.prototype.expand = function() {
 	$("#loginpanel").slideDown("fast");	
}

LoginForm.prototype.collapse = function() {
	
 	$("#loginpanel").slideUp("fast");	
}

/**
 * Set up user signup form.
 */
LoginForm.prototype.initForm = function(user) {
	

	var loginform = this ;	
	$("button#login").click( function(){
		loginform.showLogin( loginform) ;
	} ) ;
	
	var html = '<div class="centertext"> If you have a username and password<br/>login here<br/><br/></div> \
		<div class="formrow"> \
	        <div class="formlabel">Username: </div><div class="formctl"><input type="text" id="login-username" ></div> \
	    </div> \
		<div class="formrow"> \
            <div class="formlabel">Password: </div><div class="formctl"><input type="password" id="login-password" ></div> \
        </div> \
		<div class="formrow"> \
		    <button id="dologin">Login</button>&nbsp;&nbsp;<button id="cancellogin">Cancel Login</button> \
        </div> \
		<div class="centertext">= OR =<br/> \
		If you only have registered your email address<br/> \
		Email your private link to yourself here. </div>\
		<div class="formrow"> \
        	<div class="formlabel">Your Email: </div><div class="formctl"><input type="text" id="sendlink-email" ></div> \
		</div> \
		<div class="formrow"> \
	    	<button id="sendlink">Email Link</button> &nbsp;&nbsp;<button id="cancelsend">Close</button> \
		</div> \
		<div class="centertext">= OR =<br/>\
		If none of the above applies and you have not saved <br/> \
		your private link...<br/> \
		Add yourself again and be sure to save your link and/or<br/> \
		register a username/password and email.<br/> \
		email <a href="mailto:admin@dottifyme.org">admin@dottifyme.org</a> for support.</div>' ;
	
	$("#loginpanel").append(html) ;
	
	$("button#dologin").click( function() {
		var username = $("#login-username").val();
	    var password = $("#login-password").val();
		User.login(username,password).done( function(uuid ) {
			console.log( "logged in:" + uuid ) ;
			loginform.app.userForm.reloadWindow(uuid);
		} );
		
		
	}) ;
	
	$("button#sendlink").click( function() {
	    var email = $("#sendlink-email").val();
		User.sendlink( email ).done( function(result ) {
			console.log( "link: " + result.link + " success: " + result.success) ;
			
		} );
	});
	
	$("button#cancellogin").click( this.collapse) ;

	$("button#cancelsend").click( this.collapse) ;
}

LoginForm.prototype.showLogin = function(loginform) {
	loginform.app.userForm.collapse();
	loginform.expand() ;
}
