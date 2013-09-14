$.fn.mapZipCodeField = function(map) {
  var $field = $(this).find('input');
  var $button = $(this).find('button');
  $button.on('click', function() {
    handleEnterZipCode();
  });
}