function initSharrre() {
	$('#share').sharrre({
		url : "http://dottify.me/main.html",
		share: {
			facebook: true,
			googlePlus: true,
			twitter: true
	    },
	    buttons: {
	    	googlePlus: {size: 'tall'},
	    	facebook: {layout: 'box_count'},
	    	twitter: {count: 'vertical'},
	    },
	    hover: function(api, options){
	    	$(api.element).find('.buttons').show();
	    },	
	    hide: function(api, options){
	    	$(api.element).find('.buttons').hide();
	    }
	});
}