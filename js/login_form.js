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
        </div> \ ' ;
	/*
    html += '<div class="centertext">= OR =<br/> \
		If you only have registered your email address<br/> \
		Email your private link to yourself here. </div>\
		<div class="formrow"> \
        	<div class="formlabel">Your Email: </div><div class="formctl"><input type="text" id="sendlink-email" ></div> \
		</div> \
		<div class="formrow"> \
	    	<button id="sendlink">Email Link</button> &nbsp;&nbsp;<button id="cancelsend">Close</button> \
		</div> ' ;
		*/
    html += '<div class="centertext">= OR =<br/>\
		If you have not created an account or you have not saved <br/> \
		your private link then <a id="addlink" href="#">add yourself again</a> <br/>\
    	and be sure to save your link and/or<br/> \
		register a username/password and email.<br/> \
		email <a href="mailto:admin@dottifyme.org">admin@dottifyme.org</a> for support.</div>' ;
	
	$("#loginpanel").append(html) ;
	
	$("button#dologin").click( function() {
		var username = $("#login-username").val();
	    var password = $("#login-password").val();
		User.login(username,password).done( function(uuid ) {
			console.log( "logged in:" + uuid ) ;
			if( uuid ) {
				loginform.app.userForm.reloadWindow(uuid);
			} else {
				Dottifyme.alert("Invalid username and/or password") ;
				
			}
		} );
		
		
	}) ;
	
	
	$("button#sendlink").click( function() {
	    var email = $("#sendlink-email").val();
		User.sendlink( email ).done( function(result ) {
			console.log( "link: " + result.link + " success: " + result.success) ;
			
		} );
	});
	
	$("#loginpanel").keyup( function(event){
		if( event.which == 13 ) {
			$("button#dologin").trigger("click") ;
		}
	}) ;
	
	$("a#addlink").click( function(){
		loginform.collapse();
		loginform.app.userForm.expand();
	}) ;
	
	$("button#cancellogin").click( this.collapse) ;

	$("button#cancelsend").click( this.collapse) ;
}

LoginForm.prototype.showLogin = function(loginform) {
	loginform.app.userForm.collapse();
	loginform.expand() ;
}
