function Countries() {
	
}

Countries.getAll = function() {
	var geturl = 'api/country' ;
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'GET',
		dataType: 'json',
		url: geturl,
	}).done(function(data){
		console.log( "get countries returned") ;
		apiPromise.resolve( data );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}

Countries.getByIsoId = function( isoId) {
	
	var geturl = 'api/country/' + isoId ;
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'GET',
		dataType: 'json',
		url: geturl,
	}).done(function(data){
		console.log( "country:" + data ) ;
		apiPromise.resolve( data );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}