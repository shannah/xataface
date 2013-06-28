(function($){
	if ( typeof(window.xataface) == 'undefined' ){
		window.xataface = {};
	}
	
	window.xataface.RecordDialog = RecordDialog;

	function RecordDialog(o){
		this.el = document.createElement('div');
		this.recordid = null;
		this.table = null;
		this.baseURL = DATAFACE_URL+'/js/RecordDialog';
		this.formChanged = false;
		for ( var i in o ) this[i] = o[i];
	};
	
	RecordDialog.prototype = {
	
		display: function(){
			var dialog = this;
			$(this.el).load(this.baseURL+'/templates/dialog.html', function(){
				var frame = $(this).find('.xf-RecordDialog-iframe')
					.css({
						'width': '100%',
						'height': '96%',
						'padding':0,
						'margin':0,
						'border': 'none'
					})
					.attr('src', dialog.getURL());
					
				
				$(frame).hide();
				//alert(frame.attr('width'));
				frame.load(function(){
					dialog.formChanged = false;
					var iframe = $(this).contents();
					try {
						var parsed  = null;
						
						eval('parsed = '+iframe.text()+';');
						if ( parsed['response_code'] == 200 ){
							
							// We saved it successfully
							// so we can close our window
							if ( dialog.callback ){
								dialog.callback(parsed['record_data']);
							}
							
							$(dialog.el).dialog('close');
							if ( parsed['response_message'] ){
								alert(parsed['response_message']);
							}
							return;
						
						}
					} catch (err){
						//alert(err);
					
					}
					
					var dc =iframe.find('.documentContent');
					if ( dc.length == 0 ) dc = iframe.find('#main_section');
					if ( dc.length == 0 ) dc = iframe.find('#main_column');
					
					dc.remove();
					
					var ibody = iframe.find('body');
					var hidden = $(':hidden', ibody);
					
					iframe.find('body').empty();
					$('script', dc).remove();	// So script tags don't get run twice.
					dc.appendTo(ibody);
					hidden.each(function(){
						if ( this.tagName == 'SCRIPT'  ){
							return;
						}
						//alert('About to append tag: '+this.tagName+' '+ $(this).text());
						$(this).appendTo(ibody);
						$(this).hide();
						
					});
					//hidden.appendTo(ibody);
					//hidden.hide();
					$('#details-controller, .contentViews, .contentActions', ibody).hide();
					$(ibody).css('background-color','transparent');
					$('.documentContent', ibody).css({
						'border':'none',
						'background-color': 'transparent'
					});
					$(frame).fadeIn();
					
					
					$('input, textarea, select', ibody).change(function(){
						dialog.formChanged = true;
					});
					
						
				});
					
				
			});
			$(this.el).appendTo('body');
			
			
			$(this.el).dialog({
				beforeClose: function(){
					if ( dialog.formChanged ){
						return confirm('You have unsaved changes.  Clicking "OK" will discard these changes.  Do you wish to proceed?');
						
					}
				},
				buttons: {
					OK : function(){
						
						if ( dialog.callback ){
							dialog.callback();
						}
						$(this).dialog('close');
					}
					
				},
				height: $(window).height()-25,
				width: $(window).width()-25,
				title: (this.recordid?'Edit '+this.table+' Record':'Create New '+this.table+' Record'),
				modal: true
			});
			
		},
		
		getURL: function(){
			var action;
			if ( !this.recordid ){
				action='new';
			} else {
				action='edit';
			}
			return DATAFACE_SITE_HREF+'?-table='+encodeURIComponent(this.table)+(this.recordid?'&-recordid='+encodeURIComponent(this.recordid):'')+'&-action='+encodeURIComponent(action)+'&-response=json';
		}
	};
	
	RecordDialog.constructor = RecordDialog;
	
	
	
	$.fn.RecordDialog = function(options){
		return this.each(function(){
		
			$(this).click(function(){
				var d = new RecordDialog(options);
				d.display();
			});
		});
	};
	
	
	
})(jQuery);