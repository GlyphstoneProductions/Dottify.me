function initShare(divId) {
    $('#' + divId).share({
        networks: ['facebook','pinterest','googleplus','twitter','linkedin','tumblr','in1','email','stumbleupon','digg']
    });
}