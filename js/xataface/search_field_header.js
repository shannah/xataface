//require <jquery.packed.js>
(function() {
    var $ = jQuery;

    var searchField = $('input.search-field-header');
    var timeoutHandle = null;
    searchField.on('input', function() {
        console.log("on input");
        if (timeoutHandle) {
            clearTimeout(timeoutHandle);
            timeoutHandle = null;
        }
        
        timeoutHandle = setTimeout(update, 500);
    }).focus();
    
    var loading = false;
    var parentEl = $('.mobile-listing').get(0);
    $('.page-title-search .cancel-btn').click(function() {
        $(searchField).val('');
        update();
        $(searchField).focus();
    });

    function update() {
        
        if (loading) {
            return;
        }
        loading = true;

        var query = window.location.search;
        if (!query) {
            query = '?';
        }
        
        query = query.replace(/\?-search=[^&]*/, '?').replace(/&-search=[^&]*/, '');
        var windowQuery = query;
        query = query.replace(/\?-action=[^&]*/, '?').replace(/&-search=[^&]*/, '');
        windowQuery += '&-search=' + encodeURIComponent($(searchField).val());
        query += '&-search=' + encodeURIComponent($(searchField).val()) + '&-action='+encodeURIComponent('xf_infinite_scroll');
        var selector = '.mobile-listing-row';
        $(selector).remove();
        window.history.pushState({}, null, windowQuery);
        $.get(query).done(function(data) {
            loading = false;
            if (!data) {
                return;
            }
            
            var found = false;
            $(data).find(selector).each(function() {
                found = true;
                parentEl.appendChild(this);
                decorateXatafaceNode(this);
            });
            
            if (!found) {
                $('.resultlist-parent').addClass('empty').removeClass('non-empty');
            } else {
                $('.resultlist-parent').addClass('non-empty').removeClass('empty');
            }
            
        });
        
       
    }

})();