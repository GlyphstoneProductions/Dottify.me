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

Stats.getHeatmapData = function( countrycode ) {
	var geturl = 'api/stats/usersperzip' ;
	if( countrycode != null ) {
		geturl += "?country=" + countrycode ;
	} 
	
	var apiPromise = $.Deferred();
	$.ajax({
		method: 'GET',
		dataType: 'json',
		url: geturl,
	}).done(function(data){
		//console.log( "get taglist returned") ;
		var heatmapData = [];
		
		//for( var n = 0; n < data.length; n++ ) {
		for( var n = 0; n < 20; n++ ) {
			var item = data[n];
			var dpoint = new Object() ;
			dpoint.lat = item.latitude ;
			dpoint.lon = item.longitude ;
			dpoint.value = item.count ;
			heatmapData.push( dpoint ) ;
		}
		
		apiPromise.resolve( heatmapData );
	}).fail(function() {
		apiPromise.reject()
	});
	return apiPromise;
}