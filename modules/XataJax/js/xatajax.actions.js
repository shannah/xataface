//require <jquery.packed.js>
//require <xatajax.core.js>
//require <xatajax.form.core.js>
(function(){
	
	var $ = jQuery;
	
	/**
	 * @class
	 * @name actions
	 * @memberOf XataJax
	 * @description Utility functions for dealing with actions and selected actions.
	 */
	if ( typeof(XataJax.actions) == 'undefined' ){
		XataJax.actions = {};
	}
	
	XataJax.actions.doSelectedAction = doSelectedAction;
	XataJax.actions.handleSelectedAction = handleSelectedAction;
	XataJax.actions.hasRecordSelectors = hasRecordSelectors;
	XataJax.actions.getSelectedIds = getSelectedIds;
	
	/**
	 * @function
	 * @memberOf XataJax.actions
	 * @name ConfirmCallback
	 * @description
	 * A callback function that can be passed to doSelectedAction() to serve as 
	 * a confirmation to the user that they want to proceed with the action.
	 *
	 * @param {Array} recordIds An array of record ids that are to be acted upon.
	 * @returns {Boolean} true if the user confirms that they want to proceed.  False otherwise.
	 */
	
	
	/**
	 * @function
	 * @memberOf XataJax.actions
	 * @description
	 * In a result list with checkboxes to select record ids, this gets an array
	 * of the recordIds of the checked records (or a newline-delimited string).
	 *
	 * <p>This is useful for sending to Xataface actions in the --selected-ids parameter
	 * because the df_get_selected_records() function is set up to read the --selected-ids
	 * parameter and return the corresponding records.</p>
	 *
	 * @param {HTMLElement} container The HTML DOM element that contains the checkboxes.
	 * This may be the result list table or a container thereof.
	 * @param {boolean} asString If false, this will return an array of record ids.  If true,
	 * this will return the ids as a newline-delimited string.
	 * @return {mixed} Either an array of record ids or a newline-delimited string of
	 * record ids depending on the value of the <var>asString</var> parameter.
	 *
	 * @example
	 * var ids = XataJax.actions.getSelectedIds($('#result_list'), true);
	 * $.post(DATAFACE_SITE_HREF, {'-action': 'myaction', '--selected-ids': ids}, function(res){
	 *		alert("we did it");
	 * });
	 */
	function getSelectedIds(/*HTMLElement*/ container, asString){
		if ( typeof(asString) == 'undefined' ) asString = false;
		var ids = [];
		var checkboxes = $('input.rowSelectorCheckbox', container);
		checkboxes.each(function(){
			if ( $(this).is(':checked') && $(this).attr('xf-record-id') ){
				ids.push($(this).attr('xf-record-id'));
			}
		});
		if ( asString ) return ids.join("\n");
		return ids;
	
	}
	
	/**
	 * @function
	 * @memberOf XataJax.actions
	 * @description
	 * Performs an action on the currently selected records in a container.
	 *
	 * <p>Note that the selected IDs will be sent to the action in the --selected-ids
	 * POST parameter.  One record ID per line.  See df_get_selected_records() PHP function to load these records.</p>
	 *
	 * @param {Object} params The POST parameters to send to the action.
	 * @param {HTMLElement} container The container that houses the checkboxes.
	 * @param {XataJax.actions.ConfirmCallback} confirmCallback Optional callback function that can be used to prompt the user to confirm that they would like to proceed.
	 * @param {Function} emptyCallback Callback to be called if there are no records currently selected.
	 * @return {void}
	 *
	 * @example
	 * // This will perform the my_special_action action on all selected records in 
	 * // the result_list section of the page.  It looks through the checkboxes.
	 *
	 * XataJax.actions.doSelectedAction({
	 *     '-action': 'my_special_action'
	 *     },
	 *     jQuery('#result_list'),
	 *     function(ids){
	 *         return confirm('This will perform my special action on '+ids.length+' records.  Are you sure you want to proceed?');
	 *     }
	 * });
	 * 
	 */
	function doSelectedAction(/*Object*/ params, /*HTMLElement*/container, /*XataJax.actions.ConfirmCallback*/confirmCallback, /*Function*/emptyCallback){
		var ids = [];
		var checkboxes = $('input.rowSelectorCheckbox', container);
		checkboxes.each(function(){
			if ( $(this).is(':checked') && $(this).attr('xf-record-id') ){
				ids.push($(this).attr('xf-record-id'));
			}
		});

		if ( ids.length == 0 ){
			if ( typeof(emptyCallback) == 'function' ){
				emptyCallback(params, container);
			} else {
				alert('No records are currently selected.  Please first select the records that you wish to act upon.');
			}
			
			return;
		}
		
		if ( typeof(confirmCallback) == 'function' ){
			if ( !confirmCallback(ids) ){
				return;
			}
		}
		//alert(ids);
		params['--selected-ids'] = ids.join("\n");
		
		XataJax.form.submitForm('post', params);
	
	}
	
	/**
	 * @function
	 * @memberOf XataJax.actions
	 * @description
	 * Checks to see if the given element contains any selector checkboxes for selecting records.
	 *
	 * @param {HTMLElement} container  The html element to check.
	 * @return {boolean} True if it contains at least one selector checkbox.
	 */
	function hasRecordSelectors(/*HTMLElement*/container){
		return ($('input.rowSelectorCheckbox', container).size() > 0);
	}
	
	
	/**
	 * @function
	 * @memberOf XataJax.actions
	 * @description
	 * Handles a selected action that was triggered using a given link.  The link itself
	 * should contain the information about the action to be performed.
	 *
	 * @param {HTMLElement} aTag The html link that was clicked to invoke the action.  The 
	 *   href tag for this link is used as the target action to perform - except the parameters
	 *   are parsed out so that the action will ultimately be submitted via POST.
	 *
	 * @param {String} selector The selector to the container thart contains the checkboxes
	 *   representing the selected records on which to perform this action.
	 */
	function handleSelectedAction(/*HTMLElement*/ aTag, selector){
		var href = $(aTag).attr('href');
		var confirmMsg = $(aTag).attr('data-xf-confirm-message');
		var confirmCallback = null;
		if ( confirmMsg ){
			confirmCallback = function(){
				return confirm(confirmMsg);
			};
		}
		//alert(confirmMsg);
		var params = XataJax.util.getRequestParams(href);

		XataJax.actions.doSelectedAction(params, $(selector), confirmCallback);
		return false;
	
	}
	
})();