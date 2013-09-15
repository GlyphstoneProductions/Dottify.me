$(function() {
	$.fn.showErrors = function(errors) {
		var $form = $(this);
		$.each(errors, function(field, errors) {
			var errorsHtml = "";
			$.each(errors, function() {
				errorsHtml += '<p class="error">' + this +'</p>';
			})
			$form.find('input[name="' + field + '"]').after(errorsHtml);
		});
	}

	$.fn.resetErrors = function() {
		$(this).find('.error').remove();
	}
})