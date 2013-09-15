$(function() {

	function isZipCodeValid(zipCode) {
		return zipCode.match(/^\d{5}(-\d{4})?$/)
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
					zipcode: ["Please ensure your zip code is either a 5 digit Zip Code, or Zip plus 4 format. (e.g. 12345 or 12345-6789)"]
				});
			}
		});
	}
});