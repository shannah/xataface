(function() {
    function installFab() {
        var $ = jQuery;
        $('#zoomBtn').click(function() {
          $('.zoom-btn-sm').toggleClass('scale-out');
          if (!$('.zoom-card').hasClass('scale-out')) {
            $('.zoom-card').toggleClass('scale-out');
          }
        });

    }
    
    function updatePosition() {
        var zoom = document.querySelector('.zoom');
        
        if (zoom) {
            var footer = document.querySelector('.mobile-footer');
            if (footer) {
                zoom.style.bottom = (footer.offsetHeight + 10) + "px";
            }    
        }
    }
    window.addEventListener('xf-viewport-changed', updatePosition);
    window.addEventListener('DOMContentLoaded', installFab);
})();