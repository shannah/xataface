//require <xatajax.undo.js>
//require <xatajax.undo/UndoableEdit.js>
(function(){
	var $ = jQuery;
	var UndoableEdit = XataJax.undo.UndoableEdit;
	
	
	XataJax.undo.UndoableEditEvent = UndoableEditEvent;
	
	/**
	 * Represents an undoable edit event.
	 *
	 * @override-params any
	 * @constructor
	 *
	 """
	 UndoableEditEvent.__properties__ = XataJax.doc.getProperties(publicAPI);
	 """
	 */
	function UndoableEditEvent(o){
		if ( typeof(o) == 'object' ){
			$.extend(this, o);
		}
	}
	
	var publicAPI = {
		source: source,
		edit: edit
	};
	
	UndoableEditEvent.prototype = publicAPI;
	UndoableEditEvent.constructor = UndoableEditEvent;
	
	
	/**
	 * @type {Object} The source of the event.
	 */
	var source = null;
	
	/**
	 * @type {UndoableEdit} The edit that was posted.
	 */
	var edit = null;
})();