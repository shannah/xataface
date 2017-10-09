//require <split.min.js>
(function() {
    var $ = jQuery;
    
    function replaceQueryParam(str, name, value) {
        /*
        if (name == 'detail-url' && value !== null) {
            var valueParts = value.split('?');
            console.log(valueParts);
            if (valueParts.length > 1) {
                value = valueParts[0] + '?' + replaceQueryParam(valueParts[1], 'detail-url', null);
            }
        }*/
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
                if (value === null || found) {
                    continue;
                }
                found = true;
                v = encodeURIComponent(value);
            }
            
            out += k;
            if (v !== undefined) {
                out += '=' + v;
            }
            out += '&';
        }
        if (!found && value !== null) {
            out += name + '=' + encodeURIComponent(value) + '&';
        }
        //console.log('str=',str, ' name=', name, ' value=', value, ' out=', out);
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
                        var oldSelectedRow = null;
                        $('table.listing tr.master-detail-selected td', mdn).each(function() {
                            $(this).removeClass('master-detail-selected');
                            $(this).parent().removeClass('master-detail-selected');
                            oldSelectedRow = $(this).parent();
                        });
                        $('table.listing > tbody > tr > td > a', mdn).each(function() {
                            var winCursor = getQueryParam(self.contentWindow.location.search, '-cursor');
                            var linkCursor = getQueryParam($(this).attr('href'), '-cursor');
                            if (winCursor === linkCursor) {
                                $(this).closest('td').addClass('master-detail-selected');
                                $(this).closest('tr').addClass('master-detail-selected');
                            }
                        });
                        
                        if (this.contentWindow.location.search.indexOf('--master-detail-delete-row=1') !== -1) {
                            // This was a delete operation.  We should delete the row in the master detail
                            // view and load the next row.
                            if (oldSelectedRow !== null) {
                                $(oldSelectedRow).each(function() {
                                    $(this).removeClass('master-detail-selected');
                                    $('td', this).removeClass('master-detail-selected');
                                    var nex = $(this).next();
                                    while (nex.length > 0 && !nex.is(':visible')) {
                                        nex = nex.next();
                                    }
                                    
                                    if (nex.length === 0) {
                                        nex = $(this).prev();
                                        while (nex.length > 0 && !nex.is(':visible')) {
                                            nex = nex.prev();
                                        }
                                    } 
                                    

                                    if (nex.length === 0) {
                                        iframeLoaded = false;
                                        $(iframe).remove();
                                        window.location.hash = replaceQueryParam(window.location.hash, 'detail-url', null);
                                    } else {
                                       nex.addClass('master-detail-selected');
                                        $('td', nex).addClass('master-detail-selected');
                                        var newUrl = null;
                                        $('a[href]', nex).each(function() {
                                            if ($(this).attr('href').indexOf('-cursor=') !== -1) {
                                                newUrl = $(this).attr('href');
                                            }
                                        });
                                        
                                        
                                        if (newUrl !== null) {
                                            loadDetails(newUrl);
                                        } else {
                                            iframeLoaded = false;
                                            $(iframe).remove();
                                            window.location.hash = replaceQueryParam(window.location.hash, 'detail-url', null);
                                        }
                                    }
                                    $(this).hide();
                                    
                                    
                                    
                                });
                            } else {
                                iframeLoaded = false;
                                $(iframe).remove();
                                window.location.hash = replaceQueryParam(window.location.hash, 'detail-url', null);
                            }
                        }
                        
                        
                        unsavedChanges = false;
                        try {
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
                        } catch (e){}
                            
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