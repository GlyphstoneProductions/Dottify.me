function Stats() {
	//  nothing
}

Stats.getIdentityTags = function( ) {
	var geturl = 'api/stats/taglist' ;
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'GET',
		dataType: 'json',
		url: geturl,
	}).done(function(data){
		console.log( "get taglist returned") ;
		apiPromise.resolve( data );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}