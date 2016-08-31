//require <split.min.js>
(function() {
    var $ = jQuery;
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
		        
		        var unsavedChanges = false;
		        var iframeLoaded = false;
		        $('table tr td a[href]', mdn).each(function() {
		            if ($(this).attr('href').indexOf('-action=') !== -1) {
		                $(this).click(function() {
		                    if (iframeLoaded) {
		                        
		                        $('iframe.master-detail-iframe', mds).get(0).contentWindow.location=$(this).attr('href') + '&-ui-root=main-content';
		                        return false;
		                    }
		                    iframeLoaded = true;
		                    $(mds).empty();
		                    var iframe = $('<iframe class="master-detail-iframe" style="width:100%; height:100%; border:none; padding:0; margin:0">').attr('src', $(this).attr('href') + '&-ui-root=main-content');
		                    $(iframe).load(function() {
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
		                    return false;
		                });
		            }
		        });
		    }
            
        });
        
    });
    
})();