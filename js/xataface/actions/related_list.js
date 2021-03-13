//require <jquery.packed.js>
//require <xataface/components/InfiniteScroll.js>
(function(){
	var $ = jQuery;
    window.xataface = window.xataface || {};
    window.xataface.relatedList = {};
    window.xataface.relatedList.openSortDialog = openSortDialog;
    window.xataface.relatedList.openFilterDialog = openFilterDialog;
    var settingsWrapper = document.querySelector('.mobile-list-settings-wrapper');
    
	$(document).ready(function() {
        if (window.innerWidth < 768 || $('.mobile-listing').hasClass('list-style-mobile')) {
            setTimeout(function() {
                document.addEventListener('scroll', function(event) {
                    showListSettings();
                }, true);
            }, 1000);
            
            // For mobile we enable infinite scrolling
            new xataface.InfiniteScroll({
                related: true,
                scrollEl : $('body').get(0),
                parentEl : $('.mobile-listing').get(0) 
            });
        } 
	});
    
    
    function decoratePreviews(root) {
        $('.external-link-preview[data-href]', root).click(function() {
            window.open($(this).attr('data-href'));
        });
    }
    registerXatafaceDecorator(decoratePreviews);
    
    $(window).on('xf-viewport-changed', updateSettingsButtonPosition);
	// Decorate the show/hide columns action
	$('li.show-hide-related-list-columns-action a').click(function(){
		var iframe = $('<iframe>')
			.attr('width', '100%')
			.attr('height', $(window).height() * 0.8)
			
			.on('load', function(){
				var winWidth = $(window).width() * 0.8;
				var width = Math.min(800, winWidth);
				$(this).width(width);
				//dialog.dialog("option" , "position", "center");
				
				var showHideController = iframe.contentWindow.xataface.controllers.ShowHideColumnsController;
				showHideController.saveCallbacks.push(function(data){
					data.preventDefault = true;
					dialog.dialog('close');
					window.location.reload(true);
				});
				
			})
			.attr('src', $(this).attr('href')+'&--format=iframe')
			.get(0);
			;
		var dialog = $("<div></div>").append(iframe).appendTo("body").dialog({
			autoOpen: false,
			modal: true,
			resizable: false,
			width: "auto",
			height: "auto",
			close: function () {
				$(iframe).attr("src", "");
			},
			buttons : {
				'Save' : function(){
					$('button.save', iframe.contentWindow.document.body).click();
				}
			},
			create: function(event, ui) {
			   $('body').addClass('stop-scrolling');
			 },
			 beforeClose: function(event, ui) {
			   $('body').removeClass('stop-scrolling');
			 }
		});
		/*jQuery(iframe).dialog({
			autoOpen : true,
			modal : true,
			resizable : false,
			
			width : "auto",
			height: "auto"
		});*/
		dialog.dialog("option", "title", "Show/Hide Columns").dialog("open");
		return false;
	});
    
    
    /**
     * Update the settings wrapper position to 10 px above the mobile footer.
     */
    function updateSettingsButtonPosition() {
        console.log("updating settings button position");
        var viewport = xataface.viewport;
        if (viewport) {
            settingsWrapper.style.bottom = (viewport.bottom + 10) + 'px';
        }
    }
    
    /**
     * Marks the settings wrapper as "active" which causes it to float.
    */
    function showListSettings() {
        $(settingsWrapper).addClass('active');
        
    }
    
    
    /**
     * Opens the sort dialog.
     */
    function openSortDialog() {
        var qStr = window.location.search;
        if (!qStr) {
            qStr = '?';
        }
        qStr = qStr.replace(/\?-action=[^&]*/, '?').replace(/&-action=[^&]*/, '');
        qStr += '&-action=related_sort_dialog';
        
        var position = $('body').hasClass('small') ? 'bottom' : 'right';
        var sheet = new xataface.Sheet({
            url : qStr,
            position: position
        });
        sheet.show();
    }
    
    /**
     * Opens the filter dialog.
     */
    function openFilterDialog() {
        var qStr = window.location.search;
        if (!qStr) {
            qStr = '?';
        }
        qStr = qStr.replace(/\?-action=[^&]*/, '?').replace(/&-action=[^&]*/, '');
        qStr += '&-action=related_filter_dialog';
        
        var position = $('body').hasClass('small') ? 'bottom' : 'right';
        var sheet = new xataface.Sheet({
            url : qStr,
            position : position
        });
        sheet.show();
    }
})();