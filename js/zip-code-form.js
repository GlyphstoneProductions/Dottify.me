$(function() {

	function isZipCodeValid(zipCode) {
		return zipCode.match(/^\d{5}$/)
	};
	
	$.fn.zipCodeForm = function(app) {
		var $container = $(this);
		var $field = $container.find('input');
		var $button = $container.find('button');
		var $noThanksLink = $container.find('a');

		$noThanksLink.on('click', function() {
			$container.hide();
		});

		$button.on('click', function() {
			$container.resetErrors();
			var zipCode = $field.val();
			if (isZipCodeValid(zipCode)) {
				app.createUser(zipCode).done(function() {
					$container.hide();
				});
			} else {
				$container.showErrors({
					zipcode: ["Please enter just a 5 digit Zip Code."]
				});
			}
		});
	}
});