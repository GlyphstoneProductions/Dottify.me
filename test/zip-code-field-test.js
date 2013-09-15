$(function() {
	function setUp(zip) {
		var app = { createUser: function() {} }
    $('#fake-dom').append('<div id="zip"><input name="zipcode"><button></button>');
    $('#zip input').val(zip);
    $('#zip').mapZipCodeField(app);
	}
  it("hides the form after submitting a 5 digit zip code", function() {
  	setUp('48867');
    $('#zip button').click();
    refute($('#zip').is(":visible"), "form was hidden");
  });

  it("hides the form after submitting a Plus 4 zip code", function() {
  	setUp('48867-1234');
    $('#zip button').click();
    refute($('#zip').is(":visible"), "form was hidden");
  });

 	it("Does not hide the form if submitting an invalid zip code", function() {
		setUp('4886-1234');
    $('#zip button').click();
    assert($('#zip').is(":visible"), "form stayed visible");
 	});

 	it("Adds an error for the zipcode", function() {
 		setUp("4882");
 		$('#zip button').click();
 		assert($('#zip .error').siblings().first().attr('name') == "zipcode", "error was added");
 	})

 	it("considers zip + 3 to be invalid", function() {
 		setUp("48823-123");
 		$('#zip button').click();
 		assert($('#zip .error').siblings().first().attr('name') == "zipcode", "zip + 3 was rejected");
 	})

});
