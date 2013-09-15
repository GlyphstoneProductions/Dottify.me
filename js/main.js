// The root URL for the RESTful services
//var rootURL = "http://localhost/dottify/api";
var rootURL = "api" ;

var currentUser;

// Retrieve wine list when application starts
// findAll();

// Nothing to delete in initial application state
$('#btnDelete').hide();

// Register listeners
$('#btnSearch').click(function() {
	//search($('#searchKey').val());
	findById($('#searchKey').val());
	return false;
});

// Trigger search when pressing 'Return' on search key input field
$('#searchKey').keypress(function(e){
	if(e.which == 13) {
		search($('#searchKey').val());
		e.preventDefault();
		return false;
    }
});

$('#btnAdd').click(function() {
	newUser();
	return false;
});

$('#btnSave').click(function() {
	
	if ($('#userUUID').val() == '') {
		console.log('save create user') ;
		createUser();
	} else {
		console.log( 'userid exists, update user ' ) ;
		updateUser();
		
	}
   
	return false;
});

$('#btnDelete').click(function() {
	deleteUser();
	return false;
});

//$('#userList a').live('click', function() {
//	findById($(this).data('identity'));
//});

// Replace broken images with generic wine bottle
$("img").error(function(){
  $(this).attr("src", "pics/generic.jpg");

});

function search(searchKey) {
	if (searchKey == '')
		findAll();
	else
		findByName(searchKey);
}

function newUser() {
	$('#btnDelete').hide();
	currentUser = {};
	renderDetails(currentUser); // Display empty form
}

function findAll() {
	console.log('findAll');
	$.ajax({
		type: 'GET',
		url: rootURL,
		dataType: "json", // data type of response
		success: renderList
	});
}

function findByName(searchKey) {
	console.log('findByName: ' + searchKey);
	$.ajax({
		type: 'GET',
		url: rootURL + '/search/' + searchKey,
		dataType: "json",
		success: renderList
	});
}

function findById(uuid) {
	console.log('findById: ' + uuid);

	$.ajax({
		type: 'GET',
		url: rootURL + '/user/' + uuid,
		dataType: "json",
		success: function(data){
			$('#btnDelete').show();
			console.log('findById success: ' + data.uuid);
			currentUser = data;
			renderDetails(currentUser);
		} ,
		error: function(jqXHR, textStatus, errorThrown) {
			alert("Error getting user:" + uuid + " Err: " + errorThrown ) ;
		}
	});
}

function createUser() {
	console.log('createUser');
	
	$.ajax({
		type: 'POST',
		contentType: 'application/json',
		url: rootURL + '/user',
		dataType: "json",
		data: formToJSON(),
		success: function(data, textStatus, jqXHR){
			console.log('create user success: ' + data.uuid);
			$('#btnDelete').show();
			currentUser = data;
			renderDetails(currentUser);
		},
		error: function(jqXHR, textStatus, errorThrown){

			alert('create User error: ' + textStatus);
		}
	});
	console.log( 'done creating' ) ;
}

function updateUser() {
	console.log('updateUser');
	$.ajax({
		type: 'PUT',
		contentType: 'application/json',
		url: rootURL ,
		dataType: "json",
		data: formToJSON(),
		success: function(data, textStatus, jqXHR){
			alert('User updated successfully');
		},
		error: function(jqXHR, textStatus, errorThrown){
			alert('updateUser error: ' + textStatus);
		}
	});
}

function deleteUser() {
	console.log('deleteUser');
	$.ajax({
		type: 'DELETE',
		url: rootURL + '/' + $('#wineId').val(),
		success: function(data, textStatus, jqXHR){
			alert('Wine deleted successfully');
		},
		error: function(jqXHR, textStatus, errorThrown){
			alert('deleteWine error');
		}
	});
}

function renderList(data) {
	// JAX-RS serializes an empty list as null, and a 'collection of one' as an object (not an 'array of one')
	var list = data == null ? [] : (data.Users instanceof Array ? data.Users : [data.User]);

	$('#wineList li').remove();
	$.each(list, function(index, wine) {
		$('#wineList').append('<li><a href="#" data-identity="' + wine.id + '">'+wine.name+ ' (' + wine.year + ')</a></li>');
	});
}

function renderDetails(user) {
	$('#userUUID').val(user.uuid);
	$('#created').val(user.created);
	$('#modified').val(user.modified);
	$('#thisver').val( user.thisver) ;
	$('#refid').val( user.refid);
	$('#zipcode').val(user.zipcode);
	$('#username').val(user.username);
	$('#password').val(user.password);
	$('#email').val(user.email);
	$('#refuserid').val(user.refuserid);

}

//Helper function to serialize all the form fields into a JSON string
function formToJSON() {
	return JSON.stringify({
		"uuid": $('#userUUID').val(), 
		"refuserid": $('#refuserid').val(),
		"zipcode": $('#zipcode').val(),
		"username": $('#username').val(),
		"password": $('#password').val(),
		"email": $('#password').val()
	});
}

function slimFormToJSON() {
	return JSON.stringify({
		"refuserid": $('#refuserid').val(),
		"zipcode": $('#zipcode').val()

	});
}
