function initShare(divId, shareUrl) {
	$('#' + divId).empty() ;	// in case this a reload
    $('#' + divId).share({
        networks: ['facebook','twitter','pinterest','googleplus','linkedin','tumblr','in1','email']
    });
}