/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2009 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
/**
 * This jquery plugin allows you to convert any HTML element or field into
 * a record browser.  Clicking the browser button will open a modal dialog
 * that allows the user to search and browse through a number of records
 * in a table.
 *
 * Example usage: 
 *
 * <a href="#" id="selector">Click me to find stuff</a>
 * ...
 *
 * $('#selector').RecordBrowser({
 *		table: 'people',
 *		filters: {
 *			group_id: 10
 *		},
 *		callback: function(values){
 *			// values is an object with the records that the user 
 *			// selected
 *			for ( var id in values ){
 *				// id is the id of the record
 *				// values[id] is the title of the record
 *			}
 *		}
 *	});
 *
 * Alternatively you could use the RecordBrowserWidget function to convert a text field
 * into a RecordBrowser:
 *
 * <input type="text" id="textfield"/>
 * ...
 * $('#textfield').RecordBrowserWidget({
 *		table: 'people',
 *		filters: {
 *			group_id: 10
 *		},
 *		callback: function(values){
 *			// values is an object with the records that the user 
 *			// selected
 *			for ( var id in values ){
 *				// id is the id of the record
 *				// values[id] is the title of the record
 *			}
 *		}
 *	});
 * 
 */
(function ($){
	var xataface = {};
	xataface.RecordBrowser = function(o){
		/**
		 * The name of the table to browse for records in.
		 * @var string
		 */
		this.table = null;
		
		/**
		 * The name of the column to use as the value in the option list.
		 * Set this value to __id__ to use the record id.
		 * If this value is blank, then the primary key is used so long as
		 * the primary key only has a single column.  If it is a compound
		 * primary key, then the record id is used by default.
		 * @var string
		 */
		this.value = null;
		
		/**
		 * The name of the column to use as the title in the option list.
		 * Set this value to __title__ to use the record title ( or leave blank).
		 *
		 * @var string
		 */
		this.text = null;
		
		/**
		 * Search filters to add to the query.
		 * @var object
		 */
		this.filters = {};
		
		/**
		 * Callback function to be called with the selected values.
		 * function(values){}
		 * @var function
		 */
		this.callback = null;
		
		/**
		 * The document element that is used to display the dialog.
		 * @var HTMLDOMElement
		 */
		this.el = document.createElement('div');
		
		/**
		 * The base url to the RecordBrowser directory.
		 * @var string
		 */
		this.baseURL = DATAFACE_URL+'/js/RecordBrowser';
		
		for ( var i in o ){
			this[i] = o[i];
		}
		
		/**
		 * A flag to indicate whether the record select list
		 * needs to be updated when updateRecords() is called.
		 * The list would need to be updated if the filter parameters change.
		 * @var boolean
		 */
		this.dirty = true;
		$('head').append('<link rel="stylesheet" type="text/css" href="'+DATAFACE_URL+'/css/smoothness/jquery-ui-1.7.2.custom.css"/>');
		
		
		
		
	}
	
	xataface.RecordBrowser.prototype = {
		display : function(){
			var rb = this;
			$('body').append(this.el);
			$(this.el).load(this.baseURL+'/templates/RecordBrowser.html', function(){
				var dialog = this;
				var searchChangeHandler = function(){
					rb.filterRecords({
						'-search' : $(this).val()
					});
				};
				$(this).find('.xf-RecordBrowser-search-field')
					.keyup(searchChangeHandler)
					.change(searchChangeHandler);
					//.blur(searchChangeHandler);
				//$(this).find('.xf-RecordBrowser-select').css('height', '90%');
				$(this).find('.xf-RecordBrowser-select-field')
					.css('width', '100%')
					.attr('size', 8);
					
				$(this).find('.xf-RecordBrowser-addnew-button').RecordDialog({
					table: rb.table,
					callback: function(){
						rb.dirty=true;
						rb.updateRecords();
					}
				});
				
				$(this).dialog({
					'title': 'Select Record',
					'buttons' : {
						'Select' : function(){
							var out = {};
							$(dialog).find('.xf-RecordBrowser-select-field :selected').each(function(i, selected){
								out[$(selected).attr('value')] = $(selected).text();
							});
								
							if ( rb.callback ) rb.callback(out);
							$(this).dialog("close");
						
						},
						'Cancel' : function(){
							$(this).dialog("close");
						
						}
						
					},
					'modal' : true,
					'resize': function(event, ui){
						$(dialog).find('.xf-RecordBrowser-select-field').css('height', ($(dialog).height()-60)+'px');
						
					}
				});
				
				rb.updateRecords();
			});
		},
		
		filterRecords : function(filter){
			
			for ( var i in filter ){
				if ( this.filters[i] != filter[i] ) this.dirty = true;
				this.filters[i] = filter[i];
			}
			this.updateRecords();
		},
		
		updateRecords : function(){
			
			if ( this.dirty ){
				var sel = $(this.el).find('.xf-RecordBrowser-select-field');
				var val = $(sel).val();
				//var el = $(this.el);
				sel.load(this.getDataURL(), function(){
					sel.val(val);
				});
				this.dirty = false;
			}
		},
		
		getDataURL : function(){
			var url = DATAFACE_SITE_HREF+'?-action=RecordBrowser_data&-table='+encodeURIComponent(this.table);
			if ( this.value ) url += '&-value='+encodeURIComponent(this.value);
			if ( this.text ) url += '&-text='+encodeURIComponent(this.text);
			for ( var i in this.filters ){
				url += '&'+encodeURIComponent(i)+'='+encodeURIComponent(this.filters[i]);
			}
			return url;
		}
	
	};
	
	$.fn.RecordBrowser = function(options){
		
		return this.each(function(){
			var obj = $(this);
			obj.click(function(){
				if ( typeof(options.click) == 'function' ){
					options.click();
				}
				var rb = new xataface.RecordBrowser(options);
				rb.display();
			});
		});
	};
	
	$.fn.RecordBrowserWidget = function(options){
		return this.each(function(){
			
			var obj = $(this);
			if ( obj.hasClass("xf-RecordBrowserWidget") ){
				// This field is already a record browser with different
				// settings.  We need to change it.  So we remove the old
				// display field.
				var oldDisplayField = obj.next();
				var oldButton = oldDisplayField.next();
				oldDisplayField.remove();
				oldButton.remove();
				
				obj.removeClass('xf-RecordBrowserWidget');
			}
			
			
			var displayField = document.createElement('input');
			$(displayField).attr('type','text')
				.addClass('xf-RecordBrowserWidget-displayField')
				//.css('width', obj.width()+'px')
				//.css('height', obj.height()+'px')
				.css('cursor', 'pointer')
				//.css('border', '1px solid black')
				.attr('readonly', 1);
			

			$(displayField).insertAfter(this);
			
			obj.css('display','none')
				.addClass('xf-RecordBrowserWidget');
			
			if ( !options.frozen ){
				obj.change(function(){
					var id;
					if ( options.value && options.value != '__id__' ){
						id = encodeURIComponent(options.value)+'='+encodeURIComponent(obj.val());
					} else {
						id = obj.val();
					}
					var url = DATAFACE_SITE_HREF+'?-action=RecordBrowser_lookup_single&-table='+options.table+'&-id='+encodeURIComponent(id);
					if ( options.text ) url += '&-text='+encodeURIComponent(options.text);
					$.get(url, function(text){
						
						$(displayField).val(text);
					});
				});
				var a = document.createElement('a');
				$(a).addClass('xf-RecordBrowser-button')
					.css('cursor', 'pointer')
					.html('<img src="'+DATAFACE_URL+'/images/search_icon.gif" border="0" /><span class="xf-RecordBrowser-button-label"> Lookup</span>');
				$(a).find('.xf-RecordBrowser-button-label')
					.css('display', 'none');
				
				$(a).insertAfter(displayField);
				if ( !options.callback ){
					options.callback = function(vals){
						for ( var i in vals ){
							//$(displayField).val(vals[i]);
							obj.val(i);
							obj.trigger('change');
						}
					};
				}
				$(a).RecordBrowser(options);
				$(displayField).RecordBrowser(options);
			} else {
				//alert(obj.val());
			}
			
			if ( obj.val() ){
				var id;
				if ( options.value && options.value != '__id__' ){
					id = encodeURIComponent(options.value)+'='+encodeURIComponent(obj.val());
				} else {
					id = obj.val();
				}
				var url = DATAFACE_SITE_HREF+'?-action=RecordBrowser_lookup_single&-table='+options.table+'&-id='+encodeURIComponent(id);
				if ( options.text ) url += '&-text='+encodeURIComponent(options.text);
				$.get(url, function(text){
					//alert(text);
					$(displayField).val(text);
				});
			}
			
			
		});
	};
})(jQuery);