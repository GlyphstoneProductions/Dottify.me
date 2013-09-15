function UsersCollection() {
	this.items = [];
}

UsersCollection.prototype.add = function(item) {
	this.items.push(item);
	this.trigger("added", item);
};

UsersCollection.prototype.trigger = function(eventName, eventData) {
	// We're using jQuery's event system to
	// listen for changes to the data. This lets us
	// decouple our displaying of data changes from the
	// UI changing the data.


	$(this).trigger(eventName, eventData);
}