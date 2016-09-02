//require <split.min.js>
(function() {
    var $ = jQuery;
    
    function replaceQueryParam(str, name, value) {
        var parts = str.split(/&/);
        var out = '';
        var found = false;
        for (var i=0; i<parts.length; i++) {
            var kv = parts[i];
            var k = kv;
            var v = undefined;
            if (kv.indexOf('=') !== -1) {
                kv = kv.split(/=/);
                k = kv[0];
                v = kv[1];
            }
            if (k === name) {
                v = encodeURIComponent(value);
            }
            
            out += k;
            if (v !== undefined) {
                out += '=' + v;
            }
            out += '&';
        }
        if (!found) {
            out += name + '=' + encodeURIComponent(value) + '&';
        }
        if (out.length > 0) {
            return out.substring(0, out.length-1);
        }
        return out;
    }
    
    function getQueryParam(str, name) {
        var parts = str.split(/&/);
        for (var i=0; i<parts.length; i++) {
            var kv = parts[i];
            var k = kv;
            var v = '';
            if (kv.indexOf('=') !== -1) {
                kv = kv.split(/=/);
                k = kv[0];
                v = kv[1];
            }
            if (k === name) {
                return decodeURIComponent(v);
            }
        }
        return '';
    }
    
    $(document).ready(function() {
        
        
        
    
        $('#xf-master-detail').each(function() {
            var n = $('#xf-master-detail-n', this);
            var s = $('#xf-master-detail-s', this);
            var md = $(this);
            Split(['#xf-master-detail-n', '#xf-master-detail-s'], {
                direction: 'vertical',
                gutterSize: 8,
                cursor: 'row-resize'
            });
            
            if (md.length > 0) {
		        var padding = md.offset().left-md.parent().offset().left;
		        md.width(md.parent().innerWidth() - 2 * padding);
		        md.height($(window).height() - md.offset().top - padding - $('.fineprint').height());
		        var ctr = 0;
		        while ($('.fineprint').offset().top + $('.fineprint').outerHeight() > $(window).innerHeight()) {
		            md.height(md.height() - 2);
		            if (ctr++ > 50) {
		                break;
		            }
		        }
		        
		        var mdn = $('#xf-master-detail-n', md);
		        var mds = $('#xf-master-detail-s', md);
		        
		        
		        var detailUrl = getQueryParam(window.location.hash, 'detail-url');
		        
		        var unsavedChanges = false;
		        var iframeLoaded = false;
		        
		        function loadDetails(url) {
		            if (url.indexOf('&-ui-root=main-content') !== -1) {
		                url = url.replace(/&-ui-root=main-content/, '');
		            }
		            if (iframeLoaded) {
		                        
                        $('iframe.master-detail-iframe', mds).get(0).contentWindow.location=url + '&-ui-root=main-content';
                        return false;
                    }
                    iframeLoaded = true;
                    $(mds).empty();
                    var iframe = $('<iframe class="master-detail-iframe" style="width:100%; height:100%; border:none; padding:0; margin:0">').attr('src', url + '&-ui-root=main-content');
                    $(iframe).load(function() {
                        var self = this;
                        window.location.hash = replaceQueryParam(window.location.hash, 'detail-url', this.contentWindow.location.href);
                        
                        // Select the row of the table that corresponds with this page
                        $('table.listing tr td', mdn).each(function() {
                            $(this).removeClass('master-detail-selected');
                            $(this).parent().removeClass('master-detail-selected');
                        });
                        $('table.listing > tbody > tr > td > a', mdn).each(function() {
                            //console.log('link: '+$(this).attr('href'));
                            //console.log('win url: '+self.contentWindow.location.href);
                            var winCursor = getQueryParam(self.contentWindow.location.search, '-cursor');
                            var linkCursor = getQueryParam($(this).attr('href'), '-cursor');
                            //console.log("Win cursor: "+winCursor+"; linkCursor: "+linkCursor);
                            if (winCursor === linkCursor) {
                                $(this).closest('td').addClass('master-detail-selected');
                                $(this).closest('tr').addClass('master-detail-selected');
                            }
                        });
                        
                        unsavedChanges = false;
                        $(iframe).contents().find('li.record-back').hide();
                        $(iframe).contents().find('input[data-xf-field],select[data-xf-field],textarea[data-xf-field]').change(function() {
                            unsavedChanges = true;
                        });
                        $(iframe).contents().find('form.xf-form-group').submit(function() {
                            unsavedChanges = false;
                        });
                        
                        $(this.contentWindow).on('beforeunload', function() {
                            if (unsavedChanges) {
                                return 'This form has unsaved changes.';
                            }
                        });
                        
                    });
                    
                    
                    $(mds).append(iframe);
		        }
		        
		        var currentDetailsUrl = getQueryParam(window.location.hash, 'detail-url');
		        if (currentDetailsUrl) {
		            //alert(currentDetailsUrl);
		            loadDetails(currentDetailsUrl);
		        }
		        
		        
		        $('table tr td a[href]', mdn).each(function() {
		            if ($(this).attr('href').indexOf('-action=') !== -1) {
		                $(this).click(function() {
		                    loadDetails($(this).attr('href'));
		                    return false;
		                });
		            }
		        });
		    }
            
        });
        
    });
    
})();