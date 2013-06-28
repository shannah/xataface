//require <xatajax.undo.js>
(function(){
	var $ = jQuery;
	var Exception = XataJax.Exception;
	
	XataJax.undo.UndoableEdit = UndoableEdit;
	
	/**
	 * Error code for exceptions thrown when an undo cannot be done.
	 */
	XataJax.errorcodes.CANNOT_UNDO_EXCEPTION = XataJax.nextErrorCode();
	
	/**
	 * Error code for exceptions thrown when a redo cannot be done.
	 */
	XataJax.errorcodes.CANNOT_REDO_EXCEPTION = XataJax.nextErrorCode();
	
	
	
	/**
	 * Constructor for UndoableEdit class.  This is basically a javascript port
	 * of the javax.swing.undo.UndoableEdit
	 *
	 * @param {String} presentationName  The presentation name of the edit.
	 * @param {boolean} significant Flag indicating whether edit is significant.  Default true
	 * @param {Function} undo Operation to perform to undo this edit.
	 * @param {Function} redo Operation to perform to redo this edit.
	 *
	 * @constructor
	 *
	 """
	 new UndoableEdit();
	 """
	 */
	function UndoableEdit(o){
		/**
		 """
		 UndoableEdit.__methods__ = publicAPI;
		 """
		*/
		/**
		 * Extends Object
		 */
		 
		XataJax.extend(this, new Object());
		
		var publicAPI = {
			undo: undo,
			redo: redo,
			addEdit: addEdit,
			die: die,
			canUndo: canUndo,
			canRedo: canRedo,
			getPresentationName: getPresentationName,
			isSignificant: isSignificant,
			setSignificant: setSignificant
			
		};
		
		/**
		 * Register public API methods
		 */
		XataJax.publicAPI(this, publicAPI);
		
		var _protected = {
			_undo: function(){},
			_redo: function(){}
		};
		
		if ( typeof(o) == 'object' ){
			if ( typeof(o.undo) == 'function' ){
				_protected._undo = o.undo;
			}
			
			if ( typeof(o.redo) == 'function' ){
				_protected._redo = o.redo;
			}
			
			if ( !typeof(this._protected)=='object' ){
				this._protected = {};
			}
		}
		
		$.extend(this._protected, _protected);
		
		
		/**
		 * Private member variables
		 */
		
		/**
		 * @type {String}
		 * The presentation name of the edit.
		 */
		var presentationName = '';
		
		
		/**
		 * @type {boolean}
		 * Whether this edit is significant or not.
		 */
		var significant = true;
		
		if ( typeof(o) == 'object' ){
			if ( typeof(o.presentationName) == 'string' ){
				presentationName = o.presentationName;
			}
			if ( typeof(o.significant) == 'boolean' ){
				significant = o.significant;
			}
		}
	
		
		/**
		 * @type {boolean}
		 * Indicates whether this edit is alive or not.  If the edit
		 * is not alive it can neither be redone nor undone.
		 *
		 */
		var alive = true;
		
		/**
		 * @type {boolean}
		 *
		 * Flag indicating whether the edit has been done or not.  If the edit 
		 * has been done it can be undone.  If it hasn't been done, it can be
		 * redone.
		 */
		var hasBeenDone = true;
		
		/**
		 * @type {array UndoableEdit}
		 *
		 * An array storing the child edits of this edit (the edit could comprise 
		 * multiple edits.
		 *
		 */
		var edits = [];
		
		/**
		 * Adds an edit to this edit.
		 *
		 * @param {UndoableEdit} edit
		 */
		function addEdit(edit){
			edits.push(edit);
			if ( edit.isSignificant() ){
				this.setSignificant(true);
			}
		}
		
		
		/**
		 * Returns whether this edit is significant or not.
		 * @returns {boolean}
		 */
		function isSignificant(){
			return significant;
		}
		
		/**
		 * Sets whether this edit is significant or not.
		 *
		 * @param {boolean} s
		 */
		function setSignificant(s){
			significant = s;
		}
		
		/**
		 * Undoes this edit.
		 * @throws {Exception(code=XataJax.errorcodes.CANNOT_UNDO_EXCEPTION)} if edit cannot be undone.
		 * @returns {void}
		 */
		function undo(){
			if ( this.canUndo() ){
				for ( var edit in edits){
					if ( typeof(edit.undo) == 'function' ){
						edit.undo();
					}
				}
				if ( typeof(this._protected._undo) == 'function' ){
					this._protected._undo();
				}
				hasBeenDone = false;
			} else {
				throw new Exception({
					message: 'Cannot undo this edit',
					code: XataJax.errorcodes.CANNOT_UNDO_EXCEPTION
				});
			}
		}
		
		/**
		 * Redoes this edit.
		 *
		 * @throws {Exception(code=XataJax.errorcodes.CANNOT_REDO_EXCEPTION)} if edit cannot be redone.
		 */
		function redo(){
			if ( this.canRedo() ){
				for ( var edit in edits ){
					if ( typeof(edit.redo) == 'function' ){
						edit.redo();
					}
				}
				if ( typeof(this._protected._redo) == 'function' ){
					this._protected._redo();
				}
				hasBeenDone = true;
			} else {
				throw new Exception({
					message: 'Cannot redo this edit',
					code: XataJax.errorcodes.CANNOT_REDO_EXCEPTION
				});
			}
		}
		
		/**
		 * Kills this edit so that it can neither be undone nor redone.
		 *
		 * @returns {void}
		 */
		function die(){
			alive = false;
		}
		
		/**
		 * Returns boolean telling whether this edit can be undone.  This
		 * returns true iff the edit is alive and it has been done.
		 * 
		 * @returns {boolean}
		 */
		function canUndo(){
			return (alive && hasBeenDone);
		}
		
		
		/**
		 * Returns a boolean telling whether this edit can be redone.  This 
		 * returns true iff the edit is alive and has not been done.
		 *
		 * @returns {boolean}
		 */
		function canRedo(){
			return (alive && !hasBeenDone);
		
		}
		
		
		/**
		 * Returns the presentation name of this edit.
		 * @returns {String}
		 */
		function getPresentationName(){
			return presentationName;
		}
		
		
		
		
	
	}
	
	/**
	 * The prototype for undoableedit which defines stub placeholders
	 * for methods.
	 */
	UndoableEdit.prototype = {
		addEdit: function(edit){
		
		},
		
		
		canUndo: function(){return false;},
		canRedo: function(){return false;},
		
		die: function(){
			
			
		},
		
		getPresentationName: function(){
			return '';
		},
		
		getRedoPresentationName: function(){
			return 'Redo '+this.getPresentationName();
		},
		
		getUndoPresentationName: function(){
		
			return 'Undo '+this.getPresentationName();
		},
		
		isSignificant: function(){ return true;},
		
		redo: function(){},
		
		replaceEdit: function(edit){
			return false;
		},
		
		undo: function(){}
		
		
	};
})();