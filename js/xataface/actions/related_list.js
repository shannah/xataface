//require <jquery.packed.js>
(function(){
	var $ = jQuery;
	
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
})();