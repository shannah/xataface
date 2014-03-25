//require <jquery-ui.min.js>
//require-css <xataface/actions/view_related_records.css>
(function(){
	var $ = jQuery;
	
	registerXatafaceDecorator(function(el){
		$('.subrecord-tabs').tabs();
	
		$('.view-tab-content .edit-btn a', el).click(function(){

			var viewTabContent = $(this).parents('[data-iframe-url]');
			$(viewTabContent).removeClass('view-mode');
			var url = $(viewTabContent).attr('data-iframe-url');
			var portalId = $(viewTabContent).attr('data-portal-id');
			url = url + '&-portal-context='+encodeURIComponent(portalId);
			$('.record-view-wrapper', viewTabContent).hide();
			var iframe = $('<iframe>')
				.on('load',
					function(){
						var $head = $(this).contents().find("head");                
						$head.append($("<link/>", { 
							rel: "stylesheet", 
							href: DATAFACE_URL+'/iframe.css', 
							type: "text/css" 
						}));
						
						$(this.contentWindow.document.body)
							.addClass('no-main-border')
							.addClass('hide-button-bars');
						$(this).attr('height', this.contentWindow.document.body.offsetHeight+'px');

						
					}
				)
				.addClass('edit-related-record-iframe')
				.css('border', 'none')
				.attr('width', '100%')
				.attr('src', url);
			$(viewTabContent).append(iframe);
			return false;
			
		});
		
		$('.view-tab-content .cancel-btn a', el).click(function(){
			var viewTabContent = $(this).parents('[data-iframe-url]');
			$('iframe.edit-related-record-iframe', viewTabContent).remove();
			$(viewTabContent).addClass('view-mode');
			$('.record-view-wrapper', viewTabContent).show();
		});
	
		
	
	});
	
	
})();