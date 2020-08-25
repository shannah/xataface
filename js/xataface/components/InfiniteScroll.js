/**
 * An infinite scroll component which will automatically load new content when the user scrolls to the bottom 
 * of an element.
 */
(function() {
    var $ = jQuery;
    window.xataface = window.xataface || {};
    window.xataface.InfiniteScroll = InfiniteScroll;
    
    
    /**
     * InfiniteScroll component.
     * @param HTMLElement options.scrollEl The element to which scrolling is applied.  Default to <body>
     * @param HTMLElement options.parentEl The element to which new rows are to be added.  Default options.scrollEl
     * @param int options.pageSize The number of rows to load at a time.  Default 30
     * @param int options.currentQuery The starting query.  THis query is not actually loaded.  It is used
     *          as a starting point.  Default window.location.search
     * @param string options.action The action to call for loading rows.  Default xf_infinite_scroll
     * @param string options.selector jQuery selector used to identify elements in response that should 
     *      be added to the parentEl.
     */
    function InfiniteScroll(options) {
        if (options) {
            Object.assign(this, options);
        }
        
        if (!this.scrollEl) {
            this.scrollEl = document.querySelector('body');
        }
        
        if (!this.parentEl) {
            this.parentEl = this.scrollEl;
        }
        
        if (!this.pageSize) {
            this.pageSize = 30;
        }
        
        if (!this.currentQuery) {
            this.currentQuery = window.location.search
        }
        
        if (!this.action) {
            if (this.related) {
                this.action = 'xf_infinite_scroll_related';
            } else {
                this.action = 'xf_infinite_scroll';
            }
            
        }
        
        if (!this.selector) {
            this.selector = '.mobile-listing-row';
        }
        
        var self = this;
        $(this.scrollEl).on('scroll', function() {
            if (self.reachedEnd) {
                return;
            }
            //console.log('1', self.scrollEl.scrollTop, self.scrollEl.clientHeight, self.scrollEl.scrollTop + self.scrollEl.clientHeight, self.scrollEl.scrollHeight);
            if (self.scrollEl.scrollTop + self.scrollEl.clientHeight >= self.scrollEl.scrollHeight) {
                //console.log(2);
                self.loadMore();
            }
        });
    }
    
    /**
     * Gets a value for a query parameter.
     * @param string queryString the query string.  Starting with '?'
     * @param string variable The name of the parameter
     * @return string Query parameter value or null if not found.
     */
    function getQueryVariable(queryString, variable) {
        var query = queryString.substring(1);
        var vars = query.split('&');
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split('=');
            if (decodeURIComponent(pair[0]) == variable) {
                return decodeURIComponent(pair[1]);
            }
        }
        return null;
    }
    
    /**
     * Generates the next query given the previous query.  This will use the -skip and -limit parameters
     * in the current Query to figure out the next query.
     * 
     * @param string currentQuery The current query.
     * @param int pageSize The page size.
     * @return The next query.
     */
    function getNextQuery(currentQuery, pageSize) {
        
        var skip = getQueryVariable(currentQuery, '-skip');
        if (!skip) {
            skip = 0;
        }
        skip = parseInt(skip);
        skip += pageSize;
        
        currentQuery = currentQuery.replace(/&-skip=[^&]*/, '')
            .replace(/\?-skip=[^&]*/, '?')
            .replace(/&-limit=[^&]*/, '')
            .replace(/\?-limit=[^&]*/, '?');
        currentQuery += '&-skip='+skip+'&-limit='+pageSize;
        return currentQuery;
    }
    
    function getNextQueryRelated(currentQuery, pageSize, action) {
        var skip = getQueryVariable(currentQuery, '-related:start');
        if (!skip) {
            skip = 0;
        }
        
        skip = parseInt(skip);
        var queryPageSize = getQueryVariable(currentQuery, '-related:limit');
        if (queryPageSize) {
            pageSize = parseInt(queryPageSize);
        }
        skip += pageSize;
        
        currentQuery = currentQuery
            .replace(/\?-action=[^&]*/, '?')
            .replace(/&-action=[^&]*/, '')
            .replace(/\?-related%3Askip=[^&]*/, '?')
            .replace(/&-related%3Askip=[^&]*/, '')
            .replace(/\?-related%3Astart=[^&]*/, '?')
            .replace(/&-related%3Astart=[^&]*/, '')
            .replace(/&-related%3Alimit=[^&]*/, '')
            .replace(/\?-related%3Alimit=[^&]*/, '?');
            console.log("Query", currentQuery);
        currentQuery += '&' + encodeURIComponent('-related:start') + '='+skip+'&'+encodeURIComponent('-related:skip')+'='+skip+'&' +encodeURIComponent('-related:limit')+'='+pageSize + '&-action=' + encodeURIComponent(action);
        console.log('q=',currentQuery);
        return currentQuery;
    }
    
    /**
     * Loads more rows into the infinite scroll.  This is triggered in the 'scroll' listener.
     */
    InfiniteScroll.prototype.loadMore = function() {
        if (this.reachedEnd) {
            return;
        }
        if (this.loading) {
            return;
        }
        this.loading = true;
        var self = this;
        var query = this.related ? 
            getNextQueryRelated(this.currentQuery, this.pageSize, this.action) :
            getNextQuery(this.currentQuery, this.pageSize, this.action);
        $.get(query).done(function(data) {
            self.loading = false;
            if (!data) {
                self.reachedEnd = true;
                return;
            }
            self.currentQuery = query;
            var found = false;
            $(data).find(self.selector).each(function() {
                found = true;
                self.parentEl.appendChild(this);
                decorateXatafaceNode(this);
            });
            if (!found) {
                self.reachedEnd = true;
            }
        });
    }
    
    

})();