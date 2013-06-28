/*
 * Xataface SchemaBrowser Widget
 * Copyright (C) 2011  Steve Hannah <steve@weblite.ca>
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Library General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Library General Public License for more details.
 * 
 * You should have received a copy of the GNU Library General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, Inc., 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301, USA.
 *
 */
 
//require <jquery.packed.js>
//require <jquery.jstree.js>
//require <xatajax.core.js>
//require <xatajax.ui.tk/ToolBar.js>
//require <xatajax.ui.tk/Button.js>
//require <xatajax.ui.tk/ButtonGroup.js>
//require-css <xataface/ui/SchemaBrowser.css>

(function(){
	
	var $ = jQuery;
	var ToolBar = XataJax.ui.tk.ToolBar;
	var Button = XataJax.ui.tk.Button;
	var ButtonGroup = XataJax.ui.tk.ButtonGroup;
	var Component = XataJax.ui.tk.Component;
	
	var ui = XataJax.load('xataface.ui');
	ui.SchemaBrowser = SchemaBrowser;
	
	// We need to set the themes directory for jstree
	$.jstree._themes = DATAFACE_URL+'/modules/XataJax/css/jstree/themes/';
	
	
	
	/**
	 * The SchemaBrowser is a simple component that allows users to browse the 
	 * schema of a table.  This includes fields (grafted, native, and calculated),
	 * relationships, and related fields - and may be extended later to include
	 * other aspects.
	 *
	 * The primary purpose of this component is for building reports in a WYSIWYG e
	 * editor so that users can easily see which fields are available, and add
	 * them to their reports with click or a drag.
	 *
	 * @constructor
	 *
	 * @event nodeClicked {NodeClickedEvent} Event fired when any node in the tree is clicked.
	 * @event fieldClicked {FieldClickedEvent} Event fired when a field node in the tree is clicked.
	 *
	 *
	 * Events:
	 * =======
	 *
	 * NodeClickedEvent {
	 * 		sourceEvent : <JQuery Event Object>  // The underlying jQuery event object
	 *		sourceElement: <HTMLElement> 		 // The A tag that was clicked
	 *		sourceBrowser: <SchemaBrowser>		 // The SchemaBrowser that originated the event.
	 * }
	 *
	 * FieldClickedEvent {
	 * 		sourceEvent : <JQuery Event Object>  // The underlying jQuery event object
	 *		sourceElement: <HTMLElement> 		 // The A tag that was clicked
	 *		sourceBrowser: <SchemaBrowser>		 // The SchemaBrowser that originated the event.
	 *		fieldName: <String>					 // The name of the filed that was clicked.
	 *		macro: <String>						 // The macro for the field (e.g. {$fieldname}
	 * }
	 *
	 * @author Steve Hannah <steve@weblite.ca>
	 * @created July 11, 2011
	 */
	function SchemaBrowser(o){
		XataJax.extend(this, new Component(o));
		XataJax.publicAPI(this, {
			update:update
		});
		//this.setLayout(new BorderLayout());
		$.extend(this, o);
		if ( !this.el ) this.el = document.createElement('div');
		

		
		
		initToolBar(this);
		
		
		init(this);
		
		
		
	
	}
	
	/**
	 * Initializes the schema browser.
	 * @param {SchemaBrowser} sb The schema browser that is being  initialized.
	 * @return {void}
	 */
	function init(sb){
		$(sb.el).html(sb.template);
		initTree(sb);
		//$('xf-schemabrowser-actions', sb.el).append(sb.toolbar.getElement());
		
		
	}
	
	
	/**
	 * Initializes the toolbar for the schema.
	 * @param {SchemaBrowser} The schema browser for which the toolbar is being 
	 * 	initialized.
	 * @return {void}
	 */
	function initToolBar(sb){
	
		sb.toolbar = new ToolBar({});
		
		var g = new ButtonGroup();
		sb.navButtonGroup = g;
		var b = new Button({});
		b.setLabel('Prev');
		b.setIcon('triangle-1-w');
		b.setIconStyle('solo');
		g.add(b);
		//b.setDisabled(false);
		$(b.getElement()).click( function(){
			var cursor = parseInt(sb.query['-cursor']||0);
			sb.query['-cursor'] = cursor-1;
			if ( cursor < 0 ){
				cursor = 0;
			}
			refreshTreePreview(sb, sb.el);
		});
		
		
		
		
		
		sb.prevButton = b;
		
		b = new Button();
		b.setLabel('X/Y');
		
		g.add(b);
		sb.statusButton = b;
		
		
		b = new Button({});
		b.setLabel('Next');
		b.setIcon('triangle-1-e');
		b.setIconStyle('solo');
		
		$(b.getElement()).click( function(){
			var cursor = parseInt(sb.query['-cursor']||0);
			sb.query['-cursor'] = cursor+1;
			if ( cursor < 0 ){
				cursor = 0;
			}
			refreshTreePreview(sb, sb.el);
		});
		
		
		
		g.add(b);
		sb.toolbar.add(g);
		//sb.toolbar.update();
		sb.add(sb.toolbar);
		
		
		
		
		
	}
	
	/**
	 * Initializes the tree for a schema browser.
	 *
	 * @param {SchemaBrowser} sb The schema browser for which the tree is
	 *  being initialized.
	 * @return {void}
	 */
	function initTree(sb){
	
		var q = {
			'-table': sb.query['-table'],
			'-action': 'xf_schemabrowser_getschema'
		
		};
		
		$(sb.el)
			.bind('loaded.jstree', function(event, data){
				// Now let's load the previews for it
				
				initTreePreview(sb, this);
				initTreeEvents(sb, this);
			
			})
			.jstree({
			'plugins': ['themes','json_data'],
			'json_data': {
				'ajax': {
					url: DATAFACE_SITE_HREF,
					data: q,
					success: function(res){
						try {
							if ( res.code == 200 ) return res.schema;
							else if ( res.message ) throw res.message;
							else throw 'Faild to load fields.  See server log for details.';
						} catch(e){
							alert(e);
						}
					}
				}
			}
		});
		
		
	}
	
	/**
	 * Initializes events on a tree.
	 * @param {SchemaBrowser} sb The schema browser we are initializing.
	 * @param {HTMLElement} el The HTML element for the tree part of the browser.
	 * @return {void}
	 */
	function initTreeEvents(sb, el){
	
		$('li a').click(function(event){
			var a = this;
			sb.trigger('nodeClicked', {
				sourceEvent: event,
				sourceElement: a,
				sourceBrowser: sb
			});
			return false;
		});
	
	
		$('li[xf-schemabrowser-macro] a').click(function(event){
			var a = this;
			sb.trigger('fieldClicked', {
				sourceEvent: event,
				sourceElement: a,
				sourceBrowser: sb,
				fieldName: $(a).parent('li').attr('xf-schemabrowser-fieldname'),
				macro: $(a).parent('li').attr('xf-schemabrowser-macro')
			});
			return false;
		});
	}
	
	/**
	 * The SchemaBrowser component provides a preview of the record data associated with 
	 * each field.  This method initializes that to the first record of the table.
	 *
	 * @param {SchemaBrowser} sb The schema browser that houses the tree.
	 * @param {HTMLElement} el The HTML element housing the tree.
	 *
	 * @return {void}
	 */
	function initTreePreview(sb, el){
	
		$('li[xf-schemabrowser-macro]', el).append('<span class="xf-preview">...</span>');
		refreshTreePreview(sb,el);
	}
	
	
	/**
	 * Updates the preview of the record data in the tree.  This should be called each
	 * time some parameters are changed that might cause different record data to 
	 * show up.  This includes:
	 *
	 * - sb.query['-table']
	 * - sb.query['-cursor']
	 *
	 * @param {SchemaBrowser} sb The subject schema browser.
	 * @param {HTMLElement} el The tree's element.
	 * @return {void}
	 */
	function refreshTreePreview(sb, el){
	
		var url = DATAFACE_SITE_HREF;
		var q = {
			'-table': sb.query['-table'],
			'-action': 'xf_schemabrowser_preview_row',
			'-cursor': sb.query['-cursor'] || 0
		};
		$.get(url, q, function(res){
			try {
				if ( res.code == 200 ){
					var values = res.values;
					sb.previewValues = values;
					$('li[xf-schemabrowser-macro]', el).each(function(){
						var macro = $(this).attr('xf-schemabrowser-macro');
						if ( typeof(values[macro]) != 'undefined' ){
							$('span.xf-preview', this).html(createPreview(values[macro]));
						} else {
							$('span.xf-preview', this).text('');
						}
					});
					sb.statusButton.setLabel("Now Showing Record #"+(parseInt(sb.query['-cursor']||0)+1));
				} else if ( res.message ){
					throw res.message;
				} else {
					throw 'Failed to load preview.  See server error log for details.';
				}
			} catch (e){
				alert(e);
			}
		});
	}
	
	/**
	 * Utility function to shorten a value so that it can be displayed in the limited
	 * preview space in the tree (next to its associated field node).  It strips out
	 * all HTML tags and trims it to a maximum 20 characters.
	 *
	 *
	 * @param {String} val The field value to be shortened.
	 * @return {String} The shortened field value.
	 */
	function createPreview(val){
		if ( val == null ) return '';
		val = (''+val).replace(/<[^>]+>/, '');
		var sval = val.substring(0,20);
		if ( sval.length < val.length ) sval +='...';
		return sval;
	}
	
	
	/**
	 * Updates the visual display of the schema browser component.
	 * @extends Component.update()
	 * @return {void}
	 */
	function update(){
	

		this.getSuper(Component).update();
		var el = this.getElement();
		$(el).html('');
		$.each(this.getChildComponents(), function(){
			$(el).append(this.getElement());
		});
	
		$(el).append(this.el);
		
		
		
	}
	
	/**
	 * Prototype providing defualt values for the schema browser.
	 */
	SchemaBrowser.prototype = {
	
		template:  @@(xataface/ui/schemabrowser/template.html),
		query: null,
		el: null,
		toolbar: null
	};
	
	/**
	 // Some Sample Code for initializing the SchemaBrowser
	XataJax.ready(function(){
	
		
	
		var div = document.createElement('div');
		
		var sb = new SchemaBrowser({
			query: {'-table': 'dtg_recipes'}
		});
		sb.bind('fieldClicked', function(event){
			alert(event.macro);
		});
		sb.update();
		
		$(div).append(sb.getElement());
		//$(div).append(sb.prevButton.getElement());
		//$(div).append(btn);
		$('body').append(div);
		$(div).dialog();
		//$('body').append(sb.getElement());
	});
	
	// End sample Code
	**/
	
	
	
	
	
	

})();