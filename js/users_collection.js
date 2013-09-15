function UsersCollection() {
	this.items = [];
}

UsersCollection.prototype.add = function(item) {
	this.items.push(item);
	this.trigger("added", item);
};

UsersCollection.prototype.loadAll = function() {
	var collection = this;
	return $.get('api/user').done(function(response){
		return $.each(JSON.parse(response).elements, function(k, user) {
			collection.add(new User(user));
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