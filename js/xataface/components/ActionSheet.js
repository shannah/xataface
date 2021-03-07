//require <jquery.packed.js>
//require-css <xataface/components/ActionSheet.css>
(function() {
    var $ = jQuery;
    var xataface = window.xataface || {};
    window.xataface = xataface;
    xataface.ActionSheet = ActionSheet;
    
    /**
     * An action sheet component similar to the one in Android.  It is primarly used
     * in mobile to provide a menu that slides up from the bottom of the screen and has
     * buttons for menu items.
     *
     * @param mixed opts Either a string, an HTMLElement, or an object.  If a string, then it is 
     *      treated as the CSS selector for a ul tag that defines the menu options.  If an HTMLElement
     *      then it is expected to be a ul tag that defines the menu options.  If an object, then see
     *      next param entries.
     * @param string opts.position The position parameter.  default 'bottom'.  Currently only bottom supported.
     *          This is the position on the page where the menu is displayed.
     * @param HTMLElement opts.modelEl The ul tag that defines the menu options.
     * @param ActionSheet opts.parent If this is a sub-menu, then this is the parent sheet.  If this parameter
     *      is provided, then instead of a "close" button, there will be a "back" button that will return to the
     *      parent sheet.
     */
    function ActionSheet(opts) {
        if (opts instanceof HTMLElement) {
            opts = {modelEl:opts};
        } else if ((typeof opts) === 'string') {
            opts = {modelEl:document.querySelector(opts)};
        }
        this.position = opts.position || 'bottom';

        this.parent = opts.parent || null;
        this.modelEl = opts.modelEl;
        this.installed = false;
        this.backgroundEl = this.parent ? this.parent.backgroundEl : document.createElement('div');
        this.closeButton = createCloseButton(this);
        $(this.backgroundEl).addClass('xataface-components-ActionSheet-background');
        this.el = null;

        
    }
    
    /**
     * Shows the ActionSheet.
     * @param int startingHeight Optional integer with a starting height for the ActionSheet.
     *      This is used when transitioning from a parent or child ActionSheet so that the height
     *      starts out as the height of the previous sheet.  This makes for a smoother transition.
     */
    ActionSheet.prototype.show = show;
    
    
    /**
     * Builds a menu from a source <ul> tag.
     * @param ActionSheet menu The ActionSheet to build.
     */
    function build(menu) {
        if (menu.el) {
            return menu.el;
        }
        var srcUl = menu.modelEl;
        var root = $('<ul class="xataface-components-ActionSheet xataface-components-ActionSheet-' + menu.position+'">');
        srcUl.classList.forEach(function(cls) {
            if (cls.startsWith('xf-record-status')) {
                $(root).addClass(cls);
            }
        });
        if ($(srcUl).attr('xf-record-id')) {
            root.attr('xf-record-id', $(srcUl).attr('xf-record-id'));
        } else {
            var xfRecordIdParent = $(srcUl).parents('[xf-record-id]').first();
            if (xfRecordIdParent.length > 0) {
                root.attr('xf-record-id', xfRecordIdParent.attr('xf-record-id'));
                xfRecordIdParent.get(0).classList.forEach(function(cls) {
                    if (cls.startsWith('xf-record-status')) {
                        $(root).addClass(cls);
                    }
                });
            }
        }
        var closeBackLi = $('<li class="close">');
        closeBackLi.append(menu.closeButton);
        root.append(closeBackLi);
        $('>li', srcUl).each(function() {
            var link = $('>a', this).first();
            var span = link.length === 0 ? $('>span', this).first() : $('>span', link).first();
            var labelEl = span.length > 0 ? span : link.length > 0 ? link : $(this);
            var label = labelEl.text();
            
            var icon = link.length === 0 ? $('>i', this).first() : $('>i', link).first();
            
            
            var node = $('<li><a><span></span></a></li>');
            link.each(function() {
                this.classList.forEach(function(cls) {
                    $('a>', node).addClass(cls);
                });
            });
            this.classList.forEach(function(cls) {
                $(node).addClass(cls); 
            });
            $('span', node).text(label);
            if (icon.length > 0) {
                $('>a', node).prepend(icon.clone());
            }
            
            var subUl = $('>ul', this).first();
            if (subUl.length > 0) {
                if (!label) {
                    $('span', node).text('More...');
                }
                if ($('i.material-icons', node).text() == 'more_vert') {
                    $('i.material-icons', node).text('folder');
                }
                $('>a', node).append($('<i class="material-icons">navigate_next</i>'));
                $('>a', node).click(function() {
                    var subMenu = new ActionSheet({modelEl : subUl.get(0), parent: menu});
                    subMenu.show($(menu.el).height());
                    
                });
            } else {
                if (link.length > 0) {
                    $('>a', node).click(function() {

                        $(link).get(0).click();
                        menu.close(true);
                    });
                }
                
            }
            

            
            root.append(node);
            
        });
        menu.el = root.get(0);
        return menu.el;
    }
    
    function createCloseButton(menu) {
        var btn = document.createElement('a');
        $(btn).addClass('xf-actionsheet-close');
        if (menu.parent) {
            $(btn).html('<i class="material-icons xataface-components-ActionSheet-back-icon">arrow_back_ios</i>');
        } else {
            $(btn).html('<i class="material-icons xataface-components-ActionSheet-close-icon">close</i>');
        }
        
        
        return btn;
    }
    
   
    function registerEvents(menu) {
        $(menu.closeButton).on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            menu.close();
        });
        
        $(menu.backgroundEl).on('touchstart click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            menu.close(true);
            
        });
    }
    
    function show(startingHeight) {
        if (this.installed) {
            return;
        }
        this.installed = true;
        var menuEl = build(this);
        
        if (startingHeight) {
            $(menuEl).css({'height': startingHeight + "px", 'max-height': '100vh'});
        }
        var self = this;
        function onAnimationEnd() {
            
        }
        var fadeInBackgound = false;
        if (!document.contains(this.backgroundEl)) {
            fadeInBackground = true;
            $(document.body).append(this.backgroundEl);
        }
        
        $(document.body).append(menuEl);
        setTimeout(function() {
            $(menuEl).css({'height': '', 'max-height': ''});
            $(self.el).addClass('slidein');
            if (fadeInBackground) {
                $(self.backgroundEl).addClass('fadein');
            }
            
        }, 20);
        
        
        registerEvents(this);
        if (this.parent) {
            $(this.parent.el).remove();
            this.parent.el = null;
            this.parent.installed = false;
        }
        
    }
    
    
    
    ActionSheet.prototype.close = function(all) {
        var self = this;
        if (!this.installed || this.closing) {
            return;
        }
        
        
        
        this.closing = true;
        if (this.parent && !all) {
            this.parent.show($(this.el).height());
            $(this.el).remove();
            this.closing = false;
            return;
        }

        function onAnimationEnd() {
            self.closing = false;
            $(self.el).remove();
            $(self.backgroundEl).remove();
        }
        
        $(this.backgroundEl).bind('webkitTransitionEnd', onAnimationEnd);
        $(this.backgroundEl).bind('transitionend', onAnimationEnd);
        $(this.el).removeClass('slidein');
        $(self.backgroundEl).removeClass('fadein');

    }
    
    
    
})();