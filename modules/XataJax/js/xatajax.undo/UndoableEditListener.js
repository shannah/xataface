//require <xatajax.undo.js>
//require <xatajax.undo/UndoableEditEvent.js>
(function(){
	
	var $ = jQuery;
	var UndoableEditEvent = XataJax.undo.UndoableEditEvent;
	
	XataJax.undo.UndoableEditListener = UndoableEditListener;
	
	/**
	 * An interface that should be implemented by any classes that was to
	 * listen to undoable edit events.
	 * @override-params any
	 *
	 * @constructor
	 *
	 """
	 UndoableEditListener.__methods__ = publicAPI;
	 """
	*/
	function UndoableEditListener(o){
		if ( typeof(o) == 'object' ){
			$.extend(this, o);
		}
	}
	
	var publicAPI = {
		
		undoableEditHappened: undoableEditHappened
	};
	UndoableEditListener.prototype = publicAPI;
	UndoableEditListener.constructor = UndoableEditListener;
	
	
	/**
	 * Method called when an object posts an undoable edit.
	 *
	 * @param 1 {UndoableEditEvent} event The event that happened.
	 */
	function undoableEditHappened(event){
	
	}
	
	
})();