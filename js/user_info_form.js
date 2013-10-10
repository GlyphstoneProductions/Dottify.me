function UserInfoForm( app) {
	this.user = null ;
	this.title = "User Form" ;
	this.app = app ;
	var _this = this ;
	// call me back when the session info is returned.
	this.app.me.on("sessionload", function( event, data ) {
		_this.onSessionLoad( event, data, _this) ;
	} ) ;

	this.initForm(null) ;
	
}


UserInfoForm.prototype.expand = function() {
 	$("#userinfo-body").slideDown("fast");	
}

UserInfoForm.prototype.collapse = function() {
	
 	$("#userinfo-body").slideUp("fast");	
}

UserInfoForm.prototype.showForm = function( userform ) {
	
	userform.app.loginForm.collapse() ;
	userform.expand() ;
}

/**
 * Set up user signup form.
 */
UserInfoForm.prototype.initForm = function(user) {
	
	var userform = this ;	
	$("#usergreeting").click( function() {
		userform.showForm(userform);
	} );
	$("#expandform").click( function() {
		userform.showForm(userform);
	} );
	
	
	$("#userinfo-form").empty();
	
	var userclassid = null ;
	var userclass = null ;
	var userMecon = "" ;
	var userZipcode = "" ;

	if( user != null ) {
		var greeting = 'Welcome back' ;
		if( user.data.username != null ) greeting += ', ' + user.data.username ;
		$('#usergreeting').html( greeting ) ;
		$('#mecon').html('<img class="meconthumb" src="images/' + user.data.mecon + '">') ;
		$("#mecon").click( function() {
			userform.zoomToMe(userform) ;
		}) ;
		$('#logout').show();
		$('#login').hide();
		$('#logout').click( function(){
			console.log("logout clicked");
			userform.logout( userform ) ;
		}) ;

		userclassid = user.data.userclass ;
		userclass = user.getUserClass() ;
		userMecon = user.data.mecon ;
		userZipcode = user.data.zipcode ;
	} else {
		var greeting = 'Click Here to Dottify Yourself!' ;
		$('#usergreeting').html( greeting ) ;	
		$('#logout').hide();
		$('#login').show();
		$('#mecon').empty() ;
		
	}
	
	var html = '<h3>Identity Information</h3> \
				<div class="accordpanel" > \
				<img id="userclass_err" class="erricon" src="images/error_triangle.png" > I am:<br/> \
		    	<input id="type_trans" type="radio" name="userclass" value="0" ' + ((userclassid == "0")? "checked" : "" ) + '>Transgender/Transsexual/Gender non-conforming<br/> \
		    	<input id="type-quest" type="radio" name="userclass" value="1" ' + ((userclassid == "1")? "checked" : "" ) + '>Curious about or Questioning my gender<br/> \
		    	<input id="type-ally" type="radio" name="userclass" value="2" ' + ((userclassid == "2")? "checked" : "" ) + '>An ally or supporter of Trans* People<br/> \
		    	<img id="zipcode_err" class="erricon" src="images/error_triangle.png" > Your 5-digit zipcode: <input id="zipcode" name="zipcode" type="text" placeholder="Enter your 5-digit zip code" maxlength="5" value="' + userZipcode + '" /><br/>  \
		    	<img id="mecon_err" class="erricon" src="images/error_triangle.png" >Pick a custom "mecon" from the list below<br/> \
	    			<div id="imagepicker_scroll" > \
	    			   <div id="imagepicker_inner" >\
	    			     <select id="mecon_select" class="image-picker" >' ;
	
	for( var n = 1; n < 31; n++ ) {
		var num = ((n < 10)? '00' : '0' ) + n ;
		var pinName = 'pin' + num + '.png' ;
		var selected = '' ;
		if( pinName == userMecon ) selected = " selected" ;
		html += '<option data-img-src="images/' + pinName + '" value="' + pinName + '" ' + selected + '>pin</option>' ;		
	}
	
	html += '	          </select> \
						</div> \
	    			</div> \
				</div>' ;

	
	if( user != null ) {
	
		html += '	<h3>Account Information</h3> \
			        <div class="accordpanel" > ' + this.getAccountHtml(user)+ '</div> \
					<h3>Survey</h3> \
					<div class="accordpanel" > ' + this.getSurveyHtml(user) + '</div> \
					<button id="updateuser" >Update Profile</button>&nbsp;&nbsp;<button id="cancel" >Close</button> \
					<div id="usererror"></div> ' ;
	} else {
		html += '<button id="adduser" >Put Me On the Map!</button>&nbsp;&nbsp;<button id="cancel" >Not Now thanks</button> \
		    <div id="usererror" title="User Form Errors" ></div> ' ;
	}
	
	$("#userinfo-form").append(html) ;

	$("select.image-picker").imagepicker() ;
	$("button#cancel").click( this.collapse ) ;
	
	$("#usererror").dialog({ 
		autoOpen: false, 
		show: "slow", 
		appendTo: "#userinfo-form", 
		minWidth: 500,
	    modal: true,
        buttons: {
          Ok: function() {
            $( this ).dialog( "close" );
          }
        }
	} ) ;
	
	if( user != null ) {
		
		// initialize and/or reinitialize sharing widgets
		var urltoshare = 'http://dottify.me?refid=' + user.data.refid ;
	    $('#sharenow').share({
	        networks: ['email','facebook','twitter','googleplus'],
	        urlToShare: urltoshare 
	    });
	    // reset on main page to ensure refid is set
	    this.app.initShare( urltoshare ) ;

		$("#accthelp_dialog").dialog({ 
			autoOpen: false, 
			show: "slow", 
			appendTo: "#userinfo-form", 
			minWidth: 500
		}) ;
		
		$("#age").spinner({ min: 5, max: 120, numberFormat: "n" }) ;
		
		// set up button event handlers ---------------------------------------------------
		$("button#accthelp").click( function() {
			$("#accthelp_dialog").dialog( "open") ;
		}) ;
		
		$("button#updateuser").click( function(){
			userform.updateUser( userform ) ;
		} ) ;
		
		$("#pwdconfirm").keyup( this.matchPasswords ) ;
		
		$("#userinfo-form").accordion({ header: "h3", heightstyle: "fill" }) ;	// set up accordion of form pages
		
		this.fillSurveyData( user ) ;
		
	} else {
		$("button#adduser").click( function(){
			userform.addUser( userform ) ;
		} ) ;	
		console.log( "not logged in. Activate accordion") ;
		// $("#userinfo-form").accordion({ header: "h3", heightstyle: "fill" }) ;	// set up accordion of form pages
	}

		
}

UserInfoForm.prototype.fillSurveyData = function( user ) {

	console.log("Getting survey") ;
	User.getSurvey(user.data.id, user.data.thisver ).done( function( survey) {
		user.survey = survey ;
		$("#age").val( survey.age ) ;
		$("#income").val( survey.income ) ;
		if( survey.race != null ) {
			var races = [survey.race] ;
			if( survey.race2 != null ) {
				races.push( survey.race2 ) ;
			}
			if( survey.race3 != null ) {
				races.push( survey.race3 ) ;
			}
			$("#race").val( races ) ;			
		} 
		$("#assignedgender").val(survey.assignedgender);
		$("#primgenderid").val(survey.primgenderid) ;
		$("#genderdesc").val(survey.genderdesc);
	
		UserInfoForm.setRadioGroupValue('gid10',survey.gid10) ;
		UserInfoForm.setRadioGroupValue('gid20',survey.gid20) ;
		UserInfoForm.setRadioGroupValue('gid30',survey.gid30) ;
		UserInfoForm.setRadioGroupValue('gid40',survey.gid40) ;
		UserInfoForm.setRadioGroupValue('gid50',survey.gid50) ;
		UserInfoForm.setRadioGroupValue('gid60',survey.gid60) ;
		UserInfoForm.setRadioGroupValue('gid70',survey.gid70) ;
		UserInfoForm.setRadioGroupValue('gid80',survey.gid80) ;
		UserInfoForm.setRadioGroupValue('gid90',survey.gid90) ;
		UserInfoForm.setRadioGroupValue('gid100',survey.gid100) ;
		UserInfoForm.setRadioGroupValue('gid110',survey.gid110) ;
		UserInfoForm.setRadioGroupValue('gid120',survey.gid120) ;
		UserInfoForm.setRadioGroupValue('gid130',survey.gid130) ;
		UserInfoForm.setRadioGroupValue('gid140',survey.gid140) ;
		UserInfoForm.setRadioGroupValue('gid150',survey.gid150) ;
		UserInfoForm.setRadioGroupValue('gid160',survey.gid160) ;
	
		
	}).fail( function() {
		console.log( "Error getting survey") ;
	}) ;

}

UserInfoForm.setRadioGroupValue = function( groupName, value ) {
	
	var $radios = $("input:radio[name='" + groupName + "']");
	$radios.filter('[value=' + value +']').attr('checked', true);
}

UserInfoForm.prototype.matchPasswords = function() {
	
	var pwd = $("#password").val();
	var match = $("#pwdconfirm").val();
	if( pwd.length > 0 ) {
		if( match != pwd ) {
			$("#pwdconfirm_err").show();
			$("#pwdconfirm_ok").hide();
			return false ;
		} else {
			$("#pwdconfirm_err").hide();
			$("#pwdconfirm_ok").show();	
			return true ;
		}
	}
	return true ;
	
}


UserInfoForm.prototype.getAccountHtml = function( user ) {
	
	var email = (user.data.email != null )? user.data.email : "" ;
	var username = (user.data.username != null)? user.data.username : "";
	var rememberme = (user.data.staylogged == "1")? "checked" : "" ;
	
	var text = '<div id="accthelp_dialog" title="About Dottify Accounts">' + this.getAccountHelp() + '</div>\
	Save/bookmark your personal dottify.me link: <br/><b>http://dottify.me?uuid=' + user.data.uuid + '</b>&nbsp;&nbsp;<br/> \
	<input type="checkbox" id="staylogged" ' + rememberme + '>Remember me on this computer (Uncheck if on a public or shared computer).<br/> \
	<button id="accthelp">About Accounts</button><br/><br/> \
	Share this with your friends now: <div id="sharenow"></div><br/> \
	<div class="formrow"> \
	    <img id="email_err" class="erricon" src="images/error_triangle.png" ><div class="formlabel">Email: (optional)</div><div class="formctl"><input type="text" id="email" value="' + email + '" ></div> \
	</div> \
	<div class="formrow"> \
	    <img id="username_err" class="erricon" src="images/error_triangle.png" ><div class="formlabel">Username: (optional) </div><div class="formctl"><input type="text" id="username" value="' + username + '" ></div> \
	</div> \
	<div class="formrow"> \
	    <img id="password_err" class="erricon" src="images/error_triangle.png" ><div class="formlabel">Password: (optional) </div><div class="formctl"><input type="password" id="password" ></div> \
	</div> \
	<div class="formrow"> \
	    <div class="formlabel">Password confirm: </div><div class="formctl"><input type="password" id="pwdconfirm" ></div> \
	    <img id="pwdconfirm_err" class="erricon" src="images/error_triangle.png" title="passwords do not match" > \
	    <img id="pwdconfirm_ok" class="goodicon" src="images/checkmark.png" > \
	</div> ' ;
	return text ;
}

/*
 * Generate the survey form html
 */
UserInfoForm.prototype.getSurveyHtml = function( user ) {
	
	var text = '<div class="formrow"> \
		<img id="age_err" class="erricon" src="images/error_triangle.png" ><div class="formlabel">What is your age:</div><div class="formctl"><input id="age" name="age" /></div> \
</div> \
<div class="formrow"> \
		<img id="income_err" class="erricon" src="images/error_triangle.png" ><div class="formlabel">What is your household annual before-tax income:</div>\
	<div class="formctl"><select id="income"> \
		<option value="-1">Make a selection</option> \
		<option value="0">$0-$10,000</option> \
		<option value="1">$10,001-$20,000</option> \
		<option value="2">$20,001-$30,000</option> \
		<option value="3">$30,001-$40,000</option> \
		<option value="4">$40,001-$50,000</option> \
		<option value="5">$50,001-$60,000</option> \
		<option value="6">$60,001-$70,000</option> \
		<option value="7">$70,001-$80,000</option> \
		<option value="8">$80,001-$90,000</option> \
		<option value="9">$90,001-$100,000</option> \
		<option value="10">$100,001-$150,000</option> \
		<option value="11">$150,001-$200,000</option> \
		<option value="12">$200,001-$250,000</option> \
		<option value="13">More than $250,000</option> \
		</select>\
	</div> \
</div> \
<div class="formrow"> \
		<img id="race_err" class="erricon" src="images/error_triangle.png" ><div class="formlabel">What is your race/ethnicity: (select all that apply)<br/>Ctl+Click to multi-select.</div>\
	<div class="formctl"><select id="race" multiple> \
		<option value="0">White</option> \
		<option value="1">Black or African American</option> \
		<option value="2">American Indian or Alaska Native</option> \
		<option value="3">Latino or Hispanic</option> \
		<option value="4">Asian or Pacific Islander</option> \
		<option value="5">Arab or Middle Eastern</option> \
		<option value="6">Multiracial or mixed race</option> \
		</select>\
	</div> \
</div> \
<div class="formrow"> \
		<img id="assignedgender_err" class="erricon" src="images/error_triangle.png" ><div class="formlabel">What was your gender assigned at birth:<br/> (on your birth certificate)</div>\
	<div class="formctl"><select id="assignedgender"  > \
		<option value="-1">Make a selection</option> \
		<option value="0">Female</option> \
		<option value="1">Male</option> \
		</select>\
	</div> \
</div> \
<div class="formrow"> \
		<img id="primgenderid_err" class="erricon" src="images/error_triangle.png" ><div class="formlabel">What is your primary, internal gender <b>identity</b> today?</div>\
	<div class="formctl"><select id="primgenderid"  > \
		<option value="-1">Make a selection</option> \
		<option value="0">Masculine/Man</option> \
		<option value="1">Feminine/Woman</option> \
		<option value="2">Fluid/blended/non-polar gender identity</option> \
		<option value="3">A gender not listed here</option> \
	</select>\
	</div> \
</div> \
<div class="formrow"> \
		<img id="genderdesc_err" class="erricon" src="images/error_triangle.png" ><div class="formlabel">Words/terms you use to describe your gender & sexual identity (up to 50 characters)</div>\
	<div class="formctl"><textarea id="genderdesc" cols=30 rows=3 maxlength=80 ></textarea></div> \
</div> \
<div class="formrow"> \
		<div class="formheader">For each term listed, please select to what degree it applies to you</div> \
			<div class="formlabel">&nbsp;</div> \
				<div class="formcol">not at all</div> \
				<div class="formcol">somewhat</div> \
				<div class="formcol">strongly</div> ' ;

	var gidn = 10 ;
	var gids = this.getGids();
	for( var i in gids ) {
		var gid = gids[i];
		text += '<div class="tablerow"> \
			<div class="formlabel">' + gid + '</div> \
			<div class="formcol"><input type="radio" name="gid' + gidn + '" value="0" ></div> \
			<div class="formcol"><input type="radio" name="gid' + gidn + '" value="1" ></div> \
			<div class="formcol"><input type="radio" name="gid' + gidn + '" value="2" ></div> \
			</div>' ;
		gidn += 10 ;
	}
	
    text += '</div>' ;	
	return text ;
	
}

UserInfoForm.prototype.getGids = function() {
	var gids = new Array(
			"transgender",
			"transsexual" ,
			"FTM (female to male)" ,
			"MTF (male to female)" ,
			"Intersex",
			"Gender non-conforming or gender variant",
			"Genderqueer",
			"Androgynous",
			"Feminine male",
			"Mascule female or Butch",
			"A.G. or Aggressive",
			"Third Gender",
			"Cross dresser",
			"Drag Performer (King/Queen)",
			"Two-Spirit",
			"Other"
	) ;
	return gids;
}

/**
 * expand the specified panel (by number)
 */
UserInfoForm.prototype.setPanel = function( panel ) {
	$("#userinfo-form").accordion( "option", "active", panel) ;	
}

/*
 * text for the account help dialog popup.
 */
UserInfoForm.prototype.getAccountHelp = function() {
	var text = '<h2>Basic Accounts</h2> \
		By simply providing your zipcode, a dot is created for you on the map and you are assigned your own private link.<br/>\
		<b>You should save this link either as a bookmark or email it to yourself</b> if you are not using a personal computer.\
		Do not give this link to anyone as this link opens your personal account. This allows you to come back to your dottify.me\
        Account without creating a conventional login and is the most private and simple way of using dottify.me.\
		<h2>Validated Accounts</h2> \
		If you wish to you may create a "validated" account by adding an email and  username and password.\
        This is the conventional way of creating accounts on social media applications. Your email is used only used for dottify.me to \
        contact you in case of important matters regarding your account <b>or to allow you to recover your account</b>. It is kept strictly confidential.\
        Additional controls on how your email may be used by dottify.me will be added in the future.\
		<h2>Sharing With Your Friends</h2>\
        Use the sharing buttons to recommend dottify.me to friends. If you use the sharing buttons provided, dottify.me sends specially coded \
		links that allow you, and only you to see what friends or friends of friends joined the site because of your share or recommendation. \
        The "share" link cannot be tracked back to you and cannot be used to access your account. ' ;
	return text ;
}



UserInfoForm.prototype.addUser = function( userform ) {
	
	var refid = userform.app.getUrlParam("refid") ;
	var uinfo = userform.app.me ;
	
    var user = userform.getNewUser() ;
    
    user.data.refuserid = refid ;	// forward the refid so the user is lined to there referrer
    user.data.staylogged = 1 ;	// force them to stay logged for now so if they refresh, the do not loose their context.
    
	console.log( 'Add User: ' + user.data.zipcode ) ;
	user.validate().done(function(result) {
		//console.log( JSON.stringify(result) ) ;
		if( result.allvalid ) {
			console.log("validation success. Now add") ;
			User.create(user).done( function( newuser ) {
				var uuid = newuser.data.uuid ;
				userform.user = newuser ;
				console.log("User Created successfully") ;
				// redirect to load with new uuid
				
				userform.reloadWindow( uuid ) ;
				/*
				var url = window.location.href ;
				if (url.indexOf("?")>-1){
					url = url.substr(0,url.indexOf("?"));
				}
				var setcookie = "http://localhost/dottify/setcookie.php" ;
				window.location.replace( setcookie + "?uuid=" + uuid + "&redir=" + url ) ;
				*/
				//uinfo.load(uuid, refid, userform.app.users, true ) ;
			}).fail( function() {
				Dottifyme.alert("Errors occured creating a new user record for you!") ;				
			} );
		} else {
			//Dottifyme.alert("There were errors!") ;
			userform.displayErrors( result ) ;
		}
	}).fail(function() {
		Dottifyme.alert("Something went wrong with our servers :(. " +
		 							"Carrier pigeons have been dispatched to the " +
		 							"developers. Try again soon! email:bugs@dottify.org");
	});
	
	//userform.collapse() ;
}

UserInfoForm.prototype.reloadWindow = function( uuid ) {
	var url = window.location.href ;
	if (url.indexOf("?")>-1){
		url = url.substr(0,url.indexOf("?"));
	}
	// fix below before deployment or 
	// TODO: create logic that works on both localhost and dottify.me
	var setcookie = "http://localhost/dottify/setcookie.php" ;
	
	window.location.replace( setcookie + "?uuid=" + uuid + "&redir=" + url ) ;
	
}

UserInfoForm.prototype.updateUser = function( userform ) {
	console.log( 'Update User: ' + userform.title ) ;
	// validate data first
	if( !userform.matchPasswords()) {
		Dottifyme.alert("Passwords do not match") ;
		return ;
	}
	
	var uinfo = userform.app.me ;
    var user = userform.getUpdateUser() ;
    var survey = userform.getSurvey() ;
 
	user.validate().done(function(result) {
		//console.log( JSON.stringify(result) ) ;
		if( result.allvalid ) {
			//console.log("validation success. Now update") ;
			console.log(JSON.stringify(user));
			User.update(user).done( function( newuser ) {
				userform.user = newuser ;
				var uuid = newuser.data.uuid ;
				survey.id = newuser.data.id ;
				survey.ver = newuser.data.thisver ;
			    console.log(JSON.stringify( survey)) ;
			    console.log("adding survey") ;
				User.addSurvey(survey).done( function( newsurvey) {
					Dottifyme.alert("User updated successfully");
				}).fail( function() {
					Dottifyme.alert("Error saving survey") ;
				}) ;

			}).fail( function() {
				Dottifyme.alert("Errors occured creating a new user record for you!") ;				
			} );
			userform.hideAllErrorIcons()
			userform.hidePassword();
		} else {
			//Dottifyme.alert("There were errors!") ;
			userform.displayErrors( result ) ;
		}
	}).fail(function() {
		Dottifyme.alert("Something went wrong with our servers :(. " +
		 							"Carrier pigeons have been dispatched to the " +
		 							"developers. Try again soon! email:bugs@dottify.org");
	});
	
}

UserInfoForm.prototype.hidePassword = function() {
	$("#password").val("");
	$("#pwdconfirm").val("");
}

UserInfoForm.prototype.displayErrors = function( errors ) {
	//console.log( JSON.stringify( errors )) ;
	this.hideAllErrorIcons()
	var messages = "" ;
	$.each(errors.element, function(attname, err) {
		if( err['isvalid'] == false ) {
			messages += '<b>' + attname + ':</b> ' + err['message'] + '<br/>' ;
			$('#' + attname + '_err').show();
		}
	});

	var html = '<img src="images/error_triangle.png" width="50", height="50" ><br/>' + messages ;
	html += '<br><b>Check all fields noted above for the <img src="images/error_triangle.png" width="20", height="20" > icon.</b>'
	
	$("#usererror").html(html)
	$("#usererror").dialog( "open") ;
}

UserInfoForm.prototype.hideAllErrorIcons = function() {
	$(".erricon").hide();
}

/*
 * Create a new user object and populated it with data from the new user form
 */
UserInfoForm.prototype.getNewUser = function() {
	var user = new User(new Object()) ;
	var userclass = $("input:radio[name='userclass']:checked").val() ;
	var zipcode = $("#zipcode").val() ;
	var mecon = $("#mecon_select").val();
	console.log("userclass: " + userclass + " zipcode: " + zipcode + " mecon: " + mecon ) ;
	user.data.userclass = userclass ;
	user.data.zipcode = zipcode ;
	user.data.mecon = mecon ;
	user.isMe = true ;
	return user ;
	
}

UserInfoForm.prototype.getUpdateUser = function() {
	var user = this.user ;
	var userclass = $("input:radio[name='userclass']:checked").val() ;
	var zipcode = $("#zipcode").val() ;
	var mecon = $("#mecon_select").val();
	var staylogged = ($("#staylogged").is(":checked"))? 1 : 0;
	var email = $("#email").val();
	var username = $("#username").val();
	var password = $("#password").val();
	console.log("userclass: " + userclass + " zipcode: " + zipcode + " mecon: " + mecon + " staylogged: " + staylogged + " email: " + email + " username: " + username + " password: " + password ) ;
	
	user.data.userclass = userclass ;
	user.data.zipcode = zipcode ;
	user.data.mecon = mecon ;
	user.data.staylogged = staylogged ;
	user.data.email = email ;
	user.data.username = username ;
	user.data.password = password ;
	return user ;
	
}

UserInfoForm.prototype.getSurvey = function() {
	var survey = new Object() ;
	survey.age = $("#age").val();
	survey.income = $("#income").val();
	var race = $("#race").val();
	console.log(JSON.stringify(race));
	if( race != null && $.isArray(race)) {
		for( var n = 0; n < 3; n++ ) {
			
			switch( n ) {
			case 0 :
				survey.race = race[n] ;
				break ;
			case 1 :
				survey.race2 = race[n] ;
				break ;
			case 2 : 
				survey.race3 = race[n]
				break ;
			}
			if( n + 1 == race.length ) break ;
		}
		
	}
	survey.assignedgender = $("#assignedgender").val();
	survey.primgenderid = $("#primgenderid").val() ;
	survey.genderdesc = $("#genderdesc").val();
	survey.gid10 = $("input:radio[name='gid10']:checked").val() ;
	survey.gid20 = $("input:radio[name='gid20']:checked").val() ;
	survey.gid30 = $("input:radio[name='gid30']:checked").val() ;
	survey.gid40 = $("input:radio[name='gid40']:checked").val() ;
	survey.gid50 = $("input:radio[name='gid50']:checked").val() ;
	survey.gid60 = $("input:radio[name='gid60']:checked").val() ;
	survey.gid70 = $("input:radio[name='gid70']:checked").val() ;
	survey.gid80 = $("input:radio[name='gid80']:checked").val() ;
	survey.gid90 = $("input:radio[name='gid90']:checked").val() ;
	survey.gid100 = $("input:radio[name='gid100']:checked").val() ;
	survey.gid110 = $("input:radio[name='gid110']:checked").val() ;
	survey.gid120 = $("input:radio[name='gid120']:checked").val() ;
	survey.gid130 = $("input:radio[name='gid130']:checked").val() ;
	survey.gid140 = $("input:radio[name='gid140']:checked").val() ;
	survey.gid150 = $("input:radio[name='gid150']:checked").val() ;
	survey.gid160 = $("input:radio[name='gid160']:checked").val() ;
	
	return survey ;
}

UserInfoForm.prototype.logout = function( userform ) {

	console.log("start logging out") ;

	userform.app.me.on("sessionlogout", function( event, data ) {
		userform.reloadWindow( "" ) ;
	} ) ;

	userform.app.me.logout() ;
	/*
	userform.collapse() ;
	userform.app.logout() ;
	userform.user = null ;
	userform.initForm(null) ;
	*/
}

UserInfoForm.prototype.zoomToMe = function( userform ) {

	console.log( 'zoom to me: '  ) ;
	userform.app.map.zoomTo( userform.user.coordinate()) ;

}

UserInfoForm.prototype.onSessionLoad = function(event, data, _this) {

	_this.user = data.user ;
	
	if( _this.user.data.uuid != null && _this.user.data.userstatus == _this.user.Status["CLAIMED"].id ) {
		// get survey info and fold into user.

		// display user info, welcome and fill form for account info update		
		console.log("registered user" ) ;

		_this.initForm( _this.user ) ;
		
		// determine if we want to bug the user to fill out more info
		var now = new Date().getTime() / 1000;
		console.log( "created:" + data.createdms + "  now: " + now ) ;
		if( now - data.createdms < 86400000){
			console.log("new user") ;
			// for a day..
			if( _this.user.data.email == null || _this.user.data.username == null ) {
				// if the email or username is not set...
				_this.expand();
				_this.setPanel( 1 );
			}
		}

	} else {
		console.log( "unregistered user: " + data.user.data.userstatus ) ;
		// user form should be already set up for user registration
	}
	
	// this.app.me = data ;
	
}