//require <jquery.packed.js>
//require <xatajax.core.js>
(function(){
	var $ = jQuery;
	
	/**
	 * @class
	 * @name form
	 * @memberOf XataJax
	 * @description A class with static utility functions for working with forms.
	 */
	XataJax.form = {
		findField: findField,
		createForm: createForm,
		submitForm: submitForm
	
	};
	
	/**
	 * @function
	 * @memberOf XataJax.form
	 * @description
	 * Finds a field by name relative to a starting point.  It will search only within
	 * the startNode's form group (i.e. class xf-form-group).
	 *
	 * <p>This method of finding sibling fields is compatible with the grid widget
	 * so that it will return the sibling widget of the specified name in the same
	 * row as the source widget.  However it will also work when the widgets are
	 * displayed normally.</p>
	 *
	 * <p><b>Note:</b> This is designed to work with fields in the Xataface edit and new
	 * record forms and not just general html forms.  It looks for the <em>data-xf-field-fieldname</em>
	 * HTML attribute to identify the fields by name.  Xataface automatically adds this
	 * attribute to all fields on its forms.</p>
	 *
	 * @param {HTMLElement} startNode The starting point of our search (we search for siblings).
	 * @param {String} fieldName The name of the field we are searching for.
	 *
	 * @return {HTMLElement} The found field or null if it cannot find it.
	 *
	 * @example
	 * //require &lt;xatajax.form.core.js&gt;
	 * var form = XataJax.load('XataJax.form');
	 * var firstNameField = jQuery('#first_name');
	 * var lastNameField = form.findField(firstNameField, 'last_name');
	 * // lastNameField should contain the last name field in the same form
	 * // group as the given first name field.
	 *
	 * 
	 */
	function findField(startNode, fieldName){
		
		var parentGroup = $(startNode).parents('.xf-form-group').get(0);
		if ( !parentGroup ) parentGroup = $(startNode).parents('form').get(0);
		if ( !parentGroup ) return null;
		//alert('here');
		var fld = $('[data-xf-field="'+fieldName+'"]', parentGroup).get(0);
		return fld;
	}
	
	
	/**
	 * @function 
	 * @memberOf XataJax.form
	 * @description
	 * Creates a form with the specified parameters.  This can be handy if you 
	 * want to submit a form dynamically and don't want to use AJAX.
	 *
	 * @param {String} method The method.  Either 'get' or 'post'
	 * @param {Object} params The key/value pairs that the form should submit.
	 * @param {String} target The target of the form.
	 * @return {HTMLElement} jQuery object wrapping the form tag.
	 *
	 * @example
	 * XataJax.form.createForm('GET', {
	 *     '-action': 'my_special_action',
	 *     'val1': 'My first value'
	 *     'val2'; 'My second value'
	 *  });
	 */
	function createForm(method, params, target, action){
		if ( typeof(action) == 'undefined' ) action = DATAFACE_SITE_HREF;
		var form = $('<form></form>')
			.attr('action', action)
			.attr('method', method);
		if ( target ) form.attr('target',target);
		
		$.each(params, function(key,value){
			form.append(
				$('<input/>')
					.attr('type', 'hidden')
					.attr('name', key)
					.attr('value', value)
					
			);
		});
		
		return form;
	}
	
	
	/**
	 * @function
	 * @memberOf XataJax.form
	 * @description
	 * Creates and submits a form with the specified parameters.
	 * @param {String} method The method of the form (e.g. get or post)
	 * @param {Object} The key/value pairs to submit with the form.
	 * @param {String} target The target of the form.
	 * @return {void}
	 *
	 * @example
	 * @example
	 * XataJax.form.submitForm('POST', {
	 *     '-action': 'my_special_action',
	 *     'val1': 'My first value'
	 *     'val2'; 'My second value'
	 *  });
	 */
	function submitForm(method, params, target, action){
		var form = createForm(method, params, target, action);
		$('body').append(form);
		form.submit();
	}
	
})();