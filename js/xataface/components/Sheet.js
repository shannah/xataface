//require <jquery.packed.js>
//require-css <xataface/components/Sheet.css>
(function() {
    var $ = jQuery;
    var idIndex = 1;
    window.xataface = window.xataface || {};
    window.xataface.Sheet = Sheet;
    
    function Sheet(options) {
        if (options) {
            Object.assign(this, options);
        }
        
        
        if (!this.position) {
            this.position = 'bottom';
        }
        if (!this.el) {
            this.el = document.createElement('div');
        }
        $(this.el).addClass('xf-sheet').addClass('xf-sheet-'+this.position);
        if (!this.el.id) {
            this.el.setAttribute('id', 'xf-sheet-'+(idIndex++));
        }
        this.closeButton = createCloseButton();
        $(this.el).append(this.closeButton);
        
        this.backgroundEl = document.createElement('div');
        $(this.backgroundEl).addClass('xf-sheet-background');
        this.loaded = false;

        
    }
    
    function createCloseButton() {
        var btn = document.createElement('a');
        $(btn).addClass('xf-sheet-close');
        $(btn).html('<i class="material-icons xf-sheet-close-icon">close</i> <i class="material-icons xf-sheet-back-icon">arrow_back_ios</i>');
        
        return btn;
    }
    
    
    
    function registerEvents(sheet) {
        $(sheet.closeButton).on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            if (sheet.navigationStack && sheet.navigationStack.length > 0) {
                var back = sheet.navigationStack.pop();
                var el = null;
                var top = sheet.navigationStack.length > 0 ? sheet.navigationStack[sheet.navigationStack.length-1] : null;
                if (top && top.el) {
                    el = top.el;
                }
                if (back.back) {
                    back = back.back;
                }
                    
                back();
                adjustSize(sheet, el);
                if (sheet.navigationStack.length == 0) {
                    $(sheet.closeButton).removeClass('xf-sheet-back-btn');
                }
            } else {
                sheet.close();
            }
        });
        
        $(sheet.backgroundEl).on('touchstart click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            sheet.close();
        });
    }
    
    /**
     * Check if the sheet is currently installed
     */
    Object.defineProperty(Sheet.prototype, 'installed', {
        get : function() {
            if (document.getElementById(this.el.id)) {
                return true;
            } else {
                return false;
            }
        }
    })
    
    Sheet.prototype.adjustSize = function(el) {
        
        adjustSize(this, el);
    }

    
    Sheet.prototype.load = function(url) {
        if (url) {
            this.url = url;
            this.loaded = false;
        }
        if (this.loaded) {
            return;
        }
        this.loaded = true;
        var self = this;
        var spinner = $('<div class="spin xf-sheet-spinner"></div>');
        $(this.el).append(spinner);
        function onLoad() {
            spinner.remove();
            $(self.iframe).off('load', onLoad);
            window.activeSheet = self;
            adjustSize(self);
        }
        
        
        if (!this.iframe) {
            this.iframe = $('<iframe allowtransparency="true" class="xf-sheet-contentframe" src="'+this.url+'"/>').get(0);
            $(this.iframe).insertBefore(this.closeButton);
        } else {
            this.iframe.setAttribute('src', this.url);
        }
        $(this.iframe).on('load', onLoad);
    }
    
    function adjustSize(sheet, el) {
        if (sheet.position !== 'bottom' && sheet.position !== 'top') {
            return;
        }
        if (!sheet.installed || !sheet.iframe) {
            if (sheet.intervalHandle) {
                clearInterval(sheet.intervalHandle);
                sheet.intervalHandle = null;
            }
            return;
        }
        if (!el) {
            el = sheet.iframe.contentWindow.document.body;
        }
        
        var contentHeight = el.scrollHeight;
        var maxHeight = window.innerHeight * 0.8;
        
        sheet.iframe.style.height = Math.min(maxHeight, contentHeight)+"px";
    }
    
    Sheet.prototype.show = function() {
        if (this.installed) {
            return;
        }
        
        var self = this;
        //this.intervalHandle = setInterval(function() {
        //    adjustSize(self);
        //}, 1000);
        $(document.body).append(this.backgroundEl);
        $(document.body).append(this.el);
        setTimeout(function() {
            $(self.el).addClass('slidein');
            $(self.backgroundEl).addClass('fadein');
        }, 20);
        registerEvents(this);
        if (this.url) {
            this.load();
        }
        
    }
    
    Sheet.prototype.pushState = function(backFn) {
        if (!this.navigationStack) {
            this.navigationStack = [];
        }
        this.navigationStack.push(backFn);
        var el = null;
        if (backFn.el) {
            el = backFn.el;
        }
        $(this.closeButton).addClass('xf-sheet-back-btn');
        adjustSize(this, el);
    }
    
    Sheet.prototype.back = function() {
        if (!this.iframe) {
            iframe.contentWindow.history.back();
        }
    }
    
    Sheet.prototype.close = function() {
        var self = this;
        if (!this.installed || this.closing) {
            return;
        }
        /*
        if (this.intervalHandle) {
            clearInterval(this.intervalHandle);
            this.intervalHandle = null;
        }
        */
        this.navigationStack = null;
        this.closing = true;
        $(this.el).removeClass('slidein');
        $(self.backgroundEl).removeClass('fadein');
        function onAnimationEnd() {
            self.closing = false;
            $(self.el).remove();
            $(self.backgroundEl).remove();
        }
        $(this.el).bind('webkitTransitionEnd', onAnimationEnd);
        $(this.el).bind('transitionend', onAnimationEnd);

    }
    
    
    
})();