//require <xataface/lang.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
//require-css <RecordDialog/RecordDialog.css>
(function($){
	if ( typeof(window.xataface) == 'undefined' ){
		window.xataface = {};
	}
	
	
	window.xataface.RecordDialog = RecordDialog;
	var _ = xataface.lang.get;
	
	/**
	 * @name Callback
	 * @memberOf xataface.RecordDialog
	 * @function
	 * @description The callback function that is to be passed to a RecordDialog so that it can be
	 *   called on completion.
	 *
	 * @param {Object} o
	 * @param {String} o.__title__ The record title of the record that was just saved.
	 * @param {String} o.__id__ The id of the record that was just saved.
	 * @param {String} o.$$key$$ Each field of the record is included in this data as key value pairs.
	 */


	/**
	 * @name RecordDialog
	 * @class
	 * @memberOf xataface
	 * @description A dialog that opens a new record form or edit record form as an internal
	 * 	jQuery dialog.
	 *
	 * @property {HTMLElement} el The HTML element that is used to house this dialog.
	 * @property {String} recordid The ID of the record to edit (if null then this will be a new record form).
	 * @property {String} table The table of the record to edit or to add to.
	 * @property {String} baseURL The base URL of the RecordDialog folder.  Default is DATAFACE_URL+'/js/RecordDialog'
	 * @property {String} title Optional title to be displayed in the title bar of the dialog.
	 * @property {int} width Optional width for the dialog (in pixels)
	 * @property {int} height Optional height for the dialog (in pixels)
	 * @property {int} marginW Optional left/right margins (with respect to the window).
	 * @property {int} marginH Optional top/bottom margins (with respect to the window).
	 * @param {Object} o
	 * @param {String} o.recordid The Record ID of the record to edit.
	 * @param {String} o.table The name of the table to add new records to.
	 * @param {xataface.RecordDialog.Callback} o.callback The callback method to be called when saving is complete.
	 * @param {String} o.title Optional title to be displayed in the title bar of the dialog.
	 * @param {int} o.width Optional width for the dialog (in pixels)
	 * @param {int} o.height Optional height for the dialog (in pixels)
	 * @param {int} o.marginW Optional left/right margins (with respect to the window).
	 * @param {int} o.marginH Optional top/bottom margins (with respect to the window).
	 */
	function RecordDialog(o){
		this.el = document.createElement('div');
		this.recordid = null;
		this.table = null;
		this.baseURL = DATAFACE_URL+'/js/RecordDialog';
		this.formChanged = false;
		
		for ( var i in o ) this[i] = o[i];
		
		this.marginH = this.marginH || 25;
		this.marginW = this.marginW || 25;
	};
	
	RecordDialog.version = 1;
	
	RecordDialog.prototype = {
	
	
		/**
		 * @function
		 * @name display
		 * @memberOf xataface.RecordDialog#
		 * @description Displays the record dialog.
		 */
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
				/*	
				var $scroller = $(this).find('.xf-RecordDialog-iframe-scroller')
					.css({
					
						'height' : '96%',
						'width' : '100%',
						'padding' : 0,
						'margin': 0
					});
				*/	
				
				$(frame).hide();
				//alert(frame.attr('width'));
				frame.load(function(){
					$(frame).hide();
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
								dialog.showResponseMessage(parsed['response_message']);
							}
							return;
						
						}
					} catch (err){
						//alert(err);
					
					}
					
					var portalMessage = iframe.find('.portalMessage');
					portalMessage.detach();
					
					iframe.find('.xf-button-bar').remove();
					
					var dc =iframe.find('.documentContent').first();
					if ( dc.length == 0 ) dc = iframe.find('#main_section');
					if ( dc.length == 0 ) dc = iframe.find('#main_column');
					
					dc.remove();
					dc.prepend(portalMessage);
					
					var ibody = iframe.find('body');
					var hidden = $(':hidden', ibody);
					
					iframe.find('body').addClass('RecordDialogBody').empty();
					$('script', dc).remove();	// So script tags don't get run twice.
					dc.appendTo(ibody);
					hidden.each(function(){
						if ( this.tagName == 'SCRIPT'  ){
							return;
						}
						//alert('About to append tag: '+this.tagName+' '+ $(this).text());
						$('script',this).remove();
						$(this).appendTo(ibody);
						$(this).hide();
						
					});
					//hidden.appendTo(ibody);
					//hidden.hide();
					$('#details-controller, .contentViews, .contentActions, .insert-record-label, .edit-record-label', ibody).hide();
					$(ibody).css('background-color','transparent');
					$('.documentContent', ibody).css({
						'border':'none',
						'margin' : 0,
						'padding' : 0,
						'background-color': 'transparent',
						'overflow' : 'scroll'
					});
					$(frame).fadeIn(function(){
						dc.height($(frame).parent().innerHeight() - 25);
					});
					
					
					$('input, textarea, select', ibody).change(function(){
						dialog.formChanged = true;
					});
					
						
				});
					
				
			});
			$(this.el).appendTo('body');
			
			//function noScrollTouch(e){
			//	e.preventDefault();
			//}
			
			
			$('body').addClass('stop-scrolling');
			//$(document).bind('touchstart touchmove', noScrollTouch);
			
			var buttons =  [
             {
                 text : _('RecordDialog.OK_BUTTON_LABEL', 'OK'),
                  click : function(){
                      
                      if ( dialog.callback ){
                          dialog.callback();
                      }
                      $(this).dialog('close');
                  }
              }
            
         ];
			
			
			$(this.el).dialog({
				beforeClose: function(){
					
					$('body').removeClass('stop-scrolling')
					//$(document).unbind('touchstart touchmove', noScrollTouch);

					if ( dialog.formChanged ){
						return confirm('You have unsaved changes.  Clicking "OK" will discard these changes.  Do you wish to proceed?');
						
					}
				},
				//buttons:buttons,
				height: dialog.height || $(window).height()-dialog.marginH,
				width: dialog.width || $(window).width()-dialog.marginW,
				title: dialog.title || (this.recordid?'Edit '+this.table+' Record':'Create New '+this.table+' Record'),
				modal: true
			});
			
		},
		
		/**
		 * @function
		 * @name getURL
		 * @memberOf xataface.RecordDialog
		 * @description Gets the URL to the form for this dialog.
		 * @returns {String} The url for the form of this dialog.
		 */
		getURL: function(){
			var action;
			if ( !this.recordid ){
				action='new';
			} else {
				action='edit';
			}
			var url = DATAFACE_SITE_HREF+'?-table='+encodeURIComponent(this.table)+(this.recordid?'&-recordid='+encodeURIComponent(this.recordid):'')+'&-action='+encodeURIComponent(action)+'&-response=json';
			
			if ( typeof(this.params) == 'object' ){
				// We have some parameters to pass along
				
				$.each(this.params, function(key,val){
					url += '&'+encodeURIComponent(key)+'='+encodeURIComponent(val);
				});
			}
			return url;
		
		},
		
		showResponseMessage : function(msg){
		    alert(msg);
		}
	};
	
	RecordDialog.constructor = RecordDialog;
	
	
	
	$.fn.RecordDialog = function(options){
		return this.each(function(){
		
			$(this).click(function(){
			    
			    var RecordDialog = xataface.RecordDialog;
                try {
                    // If we are inside a parent iframe already due to another record dialog
                    // we will use the Record dialog from the parent window (risky??)
                    if (xataface.RecordDialog.version === window.top.xataface.RecordDialog.version) {
                        RecordDialog = window.top.xataface.RecordDialog;
                    }
        
                } catch (e) {
        
                }
			
				var d = new RecordDialog(options);
				d.display();
			});
		});
	};
	
	
	
})(jQuery);