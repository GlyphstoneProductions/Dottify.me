function UsersCollection() {
	this.items = new Array();
	this.itemcount = 0 ;
}

UsersCollection.prototype.add = function(item, overwrite) {
	// this.items.push(item);
	
	if( overwrite || !(item.uuid in this.items)) {
		this.items[item.uuid] = item ;
	}
	this.itemcount++ ;
	this.trigger("added", item);
};

UsersCollection.prototype.getCount = function() {
	return this.itemcount ;
}

UsersCollection.prototype.loadAll = function() {
	var collection = this;
	console.log('begin loading allusers ' ) ;
	return $.get('api/user').done(function(response){
		console.log('get allusers return' ) ;
		//return $.each(JSON.parse(response).elements, function(k, user) {
		// We are now returning actual JSON instead of json encoded text...
		return $.each(response.elements, function(k, user) {
			collection.add(new User(user), false); // do not overwrite a user that is already in the collection
		});
	});
}

// We're using jQuery's event system to
// listen for changes to the data. This lets us
// decouple our displaying of data changes from the
// UI changing the data.

// For an example, look at the Map constructor function
// in map.js.
UsersCollection.prototype.trigger = function(eventName, eventData) {
	$(this).trigger(eventName, eventData);
}
UsersCollection.prototype.on = function(eventName, callback) {
	$(this).on(eventName, callback)
}