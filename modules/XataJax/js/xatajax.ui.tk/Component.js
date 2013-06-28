//require <xatajax.ui.tk.js>
//require <xatajax.ui.tk/LayoutManager.js>
//require <xatajax.ui.tk/ComponentEvent.js>
//require <xatajax.ui.tk/ComponentListener.js>
//require <xatajax.beans/PropertyChangeSupport.js>
//require <xatajax.beans/Subscribable.js>
/**
 * The base class for visual components that can be added to a page.
 * A component essentially wraps an HTMLDOM element and allows it to
 * be used in a more component-wise fashion.  Components can have
 * child components and layout managers to layout these children.
 *
 * @created Feb. 7, 2011
 * @author Steve Hannah <steve@weblite.ca>
 */
(function(){
	var ComponentEvent = XataJax.ui.tk.ComponentEvent;
	var ComponentListener = XataJax.ui.tk.ComponentListener;
	var Exception = XataJax.Exception;
	var PropertyChangeSupport = XataJax.beans.PropertyChangeSupport;
	var LayoutManager = XataJax.ui.tk.LayoutManager;
	var Subscribable = XataJax.beans.Subscribable;


	/**
	 * Register the public API
	 */
	XataJax.errorcodes.COMPONENT_INDEX_OUT_OF_BOUNDS= XataJax.nextErrorCode();
	XataJax.errorcodes.COMPONENT_NOT_FOUND=XataJax.nextErrorCode();
	XataJax.errorcodes.CHILD_COMPONENT_NOT_ALLOWED = XataJax.nextErrorCode();
	
	XataJax.ui.tk.Component = Component;
	XataJax.ui.tk.Component.getComponentById = getComponentById;
	XataJax.ui.tk.Component.getComponentWrapper = getComponentWrapper;
	XataJax.ui.tk.Component.VIEW_MODE_EDIT = 1;
	XataJax.ui.tk.Component.VIEW_MODE_VIEW = 2;
	XataJax.ui.tk.Component.VIEW_MODE_FIND = 3;
	XataJax.ui.tk.Component.VIEW_MODE_DESIGN = 4;
	
	
	/**
	 * Implementation Details below this line
	 */
	var $ = jQuery;
	
	var nextComponentID = 0;
	
	/**
	 * An index to keep track of all components that are currently
	 * alive and functioning.  Maps component ids to components.
	 *
	 * NOTE: Component ids are transient so you shouldn't use these
	 * ids for persistent storage.  The ids are dynamically generated
	 * at runtime when a component is created.
	 *
	 * @type object(int => Component)
	 */
	var componentIndex = {};
	
	
	/**
	 * Registers a component with the component index.
	 * @param {Component} c
	 */
	function registerComponent(c){
		componentIndex[c.getComponentID()] = c;
	}
	
	/**
	 * Gets a component by its id. 
	 *
	 * NOTE: Component ids are transient so you shouldn't use these ids for
	 * persistent storage.  The ids are dynamically generated at runtime when
	 * a component is created.
	 *
	 * @param {int} id
	 * @returns {Component}
	 */
	function getComponentById(id){
		return componentIndex[id];
	}
	
	/**
	 * Returns the component that wraps a particular dom element.
	 * @param 0 {HTMLElement} el
	 * @returns {Component}
	 */
	function getComponentWrapper(el){
		var id = $(el).data('xatajax-component-id');
		if ( !id ){
			el = $($(el).parents('.xatajax-component').get(0));
			
			//alert("Parent component "+el);
			id = $(el).data('xatajax-component-id');
			if ( !id ){
				//alert("No id found");
				//alert('html '+$(el).html());
				return null;
			}
		}
		//alert('id is '+id);
		id = parseInt(id);
		return getComponentById(id);
	}
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * A base class for visual components that can be used to layout a 
	 * user interface.
	 * @constructor
	 * @param {Object} o Input properties
	 * @option {HTMLElement} o.el The HTMLElement that this component is to wrap.
	 *
	 *
	 */
	function Component(o){
		
	
		/**
		 * We extend the PropertyChangeSupport class so that we 
		 * can notify other classes of changes to our properties.
		 */
		XataJax.extend(this, new PropertyChangeSupport());
		XataJax.extend(this, new Subscribable());
		
		
		/**
		 * Define public methods.
		 */
		 
		var publicAPI = {
			init: init,
			getElement: getElement,
			createElement: createElement,
			decorateElement: decorateElement,
			getComponentID: getComponentID,
			getParent: getParent,
			setParent:setParent,
			add: add,
			remove: remove,
			get: get,
			setLayout: setLayout,
			getLayout: getLayout,
			addComponentListener: addComponentListener,
			removeComponentListener: removeComponentListener,
			getChildComponents: getChildComponents,
			numChildren: numChildren,
			css: css,
			addClass: addClass,
			removeClass: removeClass,
			getClasses: getClasses,
			setValue: setValue,
			getValue: getValue,
			getViewMode: getViewMode,
			setViewMode: setViewMode,
			isChildAllowed: isChildAllowed,
			update:update,
			serialize: serialize,
			unserialize: unserialize,
			getCanonicalClassName: getCanonicalClassName
		};
		//$.extend(this, publicAPI);
		XataJax.publicAPI(this, publicAPI);
		
		/**
		 * Private member variables.
		 */
		var members = {
			/**
			 * @type HTMLElement
			 */
			el: null,
			
			/**
			 * @type array(Component)
			 */
			children: [],
			
			/**
			 * @type LayoutManager
			 */
			layout: null, // 
			
			/**
			 * @type dict(componentID => Component)
			 */
			childIndex: {},
			
			/**
			 * @type int
			 */
			componentID: ++nextComponentID,
			
			/**
			 * @type dict(string => Component)
			 */
			nameToChild: {},
			
			/**
			 * @type dict(int => string)
			 */
			childToName: {},
			
			
			/**
			 * @type array(ComponentListener)
			 */
			componentListeners: [],
			
			/**
			 * CSS directives applied to this component.
			 *
			 * @type object(string => string)
			 */
			css: {},
			
			
			/**
			 * CSS Classes applied to this component.
			 * @type array(string)
			 */
			cssClasses: [],
			
			
			/**
			 * The value currently stored in this component.  This is used
			 * in conjuction with data sources to provide a standard way
			 * to read and write data from a datasource to a component.
			 */
			value: null,
			
			viewMode: Component.VIEW_MODE_VIEW,
			
			
			/**
			 * @type Component the parent component of this component.
			 */
			parent: null
			
			
		};
		
		registerComponent(this);
		
		if ( typeof(o) == 'object' ){
			$.extend(members, o);
		}
		
		/**
		 * Initializes this component.  It registers this component in the 
		 * registry so that it can be retrieved and referenced by ID later.
		 * @returns {void}
		 */
		function init(){
			registerComponent(this);
		}
		
		/**
		 * Gets this component's parent component.
		 * @returns {Component} The parent component.
		 */
		function getParent(){
			return members.parent;
		}
		
		/**
		 * Sets the parent component of this component.
		 * @param {Component} c The parent component.
		 * @returns {void}
		 */
		function setParent(c){
			members.parent = c;
		}
		
		
		/**
		 * Returns the unique component id of this component.
		 * When components are created they are automatically assigned a unique
		 * integer id to help catalog them.
		 *
		 * @returns {int}
		 */
		function getComponentID(){
			return members.componentID;
		}
		
		/**
		 * Sets the layout manager for this component.
		 * @param {LayoutManager} l
		 */
		function setLayout(l){
			if ( members.layout != null ) members.layout.uninstall(this);
			if ( l != members.layout ){
				var old = members.layout;
				members.layout = l;
				l.install(this);
				this.firePropertyChange('layout', old, l);
			}
		}
		
		/**
		 * Gets the layout manager for this component.
		 * @returns {LayoutManager}
		 */
		function getLayout(){
			return members.layout;
		}
		
		
		/**
		 * Returns the HTMLElement that this component uses for display.
		 * @returns {HTMLElement}
		 */
		function getElement(){
			if ( members.el == null ){
				members.el = this.createElement();
				this.decorateElement(members.el);
			}
			return members.el;
		}
		
		
		/**
		 * Creates the DOM element for this component.  Bare-bones.  Doesn't 
		 * assign any classes or styles to it.
		 *
		 * @returns {HTMLElement}
		 */
		function createElement(){
			return document.createElement('div');
		}
		
		
		/**
		 * Decorates a HTMLElement for this component. This is called by
		 * getElement() after it creates the element with createElement to
		 * add the appropriate classes and styles to the element.  This makes
		 * it easier for subclasses to just override the decoration portion
		 * of the element creation.
		 *
		 * @param {HTMLElement} el The HTML element to decorate.
		 * @returns {void}
		 */
		function decorateElement(el){
			$(el).addClass('xatajax-component');
			$(el).data('xatajax-component-id', getComponentID());
			$(el).css(css());
			$.each(getClasses(), function(){
				$(el).addClass(this);
			});
		}
		
		/**
		 * Adds a child component to this component.  This method has an optional 
		 * second parameter that can either be an integer or a string.  If 
		 * a string is provided it is treated as a label for this child so that
		 * it can be looked up by name.  If it is an integer, it merely
		 * dictates the position in the children array where the component will be placed.
		 * If this is not specified it is given no name, and the component will simply
		 * be appended to the end of the children array.
		 *
		 * @event beforeChildAdded(ComponentEvent)
		 * @event childAdded(ComponentEvent)
		 *
		 * @param {Component} c The child component to be added.
		 * @param {mixed} param  Either a string or an integer.
		 *
		 * @throws {XataJax.Exception(code: XataJax.errorcodes.COMPONENT_INDEX_OUT_OF_BOUNDS)}
		 *		if the specified parameter is out of bounds.
		 *
		 */
		function add(c, param){
			if ( !this.isChildAllowed(c, param) ){
				throw new Exception({
					message: 'Child component is not allowed at this location.',
					code: XataJax.errorcodes.CHILD_COMPONENT_NOT_ALLOWED
				});
			}
			var index = members.children.length;
			if ( typeof(param) == 'number' ){
				index = param;
			}
			
			if ( index < 0 || index > members.children.length ){
				throw new XataJax.Exception({
					message: 'Component index out of bounds.  Attempt to add component to index '+index+' failed because that is out of bounds.',
					code: XataJax.errorcodes.COMPONENT_INDEX_OUT_OF_BOUNDS
				});
			}
			
			
			
			var name = null;
			if ( typeof(param) =='string' ){
				name = param;
			}
			
			// No special parameter... we add this component to 
			// the end of the child list
			fireBeforeChildAdded(new ComponentEvent({
				source: this,
				component: c,
				index: index,
				name: name
			}));
			
			if ( index < members.children.length ){
				// we are adding this component at an index
				// that is already occupied by another child
				// component.  We need to first remove this
				// other component.
				remove(members.children[param], false);
				
			}
			
			// Check if the parent of this component is not null.
			if ( c.getParent() != null ){
				c.getParent().remove(c);
			}
			
			members.children.push(c);
			members.childIndex[c.getComponentID()] = c;
			members.nameToChild[name] = c;
			members.childToName[c.getComponentID()] = name;
			
			
			
			fireChildAdded(new ComponentEvent({
				source: this,
				component: c,
				index: index,
				name: name
				
			}));
			
				
				
				
				
			
		}
		
		
		/**
		 * Removes a child component.
		 * @param {Component} c The child component to remove.
		 * @param optional {boolean} repack Set false to leave an empty slot at the 
		 *	place where this child resides.  This is handy if you mean to replace
		 *	it with another component immediately.  Default value is true (i.e.
		 *	it repacks them.
		 *
		 * @event ComponentListener#beforeChildRemoved
		 * @event ComponentListener#childRemoved
		 *
		 * @throws {XataJax.Exception(code:XataJax.errorcodes.COMPONENT_NOT_FOUND)} if
		 *	if the specied component is not a child of this component.
		 */
		function remove(c, repack){
			if ( typeof(repack) == 'undefined' ){
				repack = true;
			}
			var index = members.children.indexOf(c);
			if ( index == -1 ){
				throw new XataJax.Exception({
					message: 'Failed to remove component from parent because it is not a child of this component',
					code: XataJax.errorcodes.COMPONENT_NOT_FOUND
				});
			}
			
			var name = members.childToName[c.getComponentID()];
			
			
			fireBeforeChildRemoved(new ComponentEvent({
				source: this,
				component: c,
				index: index,
				name: name
			}));
			
			
			if ( repack ){
				members.splice(index, 1);
			} else {
				delete members.children.indexOf(c);
			}
			
			if ( name ){
				delete members.nameToChild[name];
				delete members.childToName[c.getComponentID()];
			}
			delete childIndex[c.getComponentID()];
			
			fireChildRemoved(new ComponentEvent({
				source: this,
				component: c,
				index: index,
				name: name
			}));
			
		}
		
		
		/**
		 * Gets one or more child components from this component.
		 * @variant ByIndex Gets a child component by its numeric index.
		 * @param {int} key The index of the child to retrieve (0-based).
		 * @returns {Component} The child component at index <code>key</code>.
		 *
		 * @variant ByLabel Gets a child component by label.
		 * @param {String} key The name of the child to retrieve.
		 * @returns {Component} The child component stored with the given label.
		 *
		 * @variant InputObject Gets multiple child components by labels.
		 * @param {Object} key An object that will be used as an out parameter.  Whatever
		 *		keys are passed with the object are given values of the components at the
		 *		corresponding label within this component.
		 * @returns {Object} The same Object that is input as a parameter.
		 *
		 */
		function get(key){
			if ( typeof(key) == 'number' ){
				return members.children[key];
			} else if ( typeof(key) == 'string' ){
				return members.nameToChild[key];
			} else if ( typeof(key) == 'function' ){
				var out = [];
				$.each(members.children, function(){
					if ( key(this) ) out.push(this);
				});
			} else if ( typeof(key) == 'object' ){
				for ( var i in key ){
					key[i] = members.nameToChild[i];
				}
				return key;
			}
		}
		
		/**
		 * Adds a ComponentListener to listen to component events in this
		 * component.
		 *
		 * @param {ComponentListener} l
		 */
		function addComponentListener(l){
			members.componentListeners.push(l);
		}
		
		
		/**
		 * Removes a ComponentListener from the set of objects that are registered
		 * to listener to component events in this Component.
		 *
		 * @param {ComponentListener} l
		 */
		function removeComponentListener(l){
			var idx = members.componentListeners.indexOf(l);
			if ( idx != -1 ){
				members.componentListeners.splice(idx, 1);
			}
		}
		
		
		/**
		 * Fires the childAdded event to all component listeners.
		 * @param {ComponentEvent} event
		 */
		function fireChildAdded(event){
			$.each(members.componentListeners, function(){
				if ( typeof(this.childAdded) == 'function' ){
					this.childAdded(event);
				}
			});
		}
		
		/**
		 * Fires the childRemoved event to all component listeners.
		 * @param {ComponentEvent} event
		 */
		function fireChildRemoved(event){
			$.each(members.componentListeners, function(){
				if ( typeof(this.childRemoved) == 'function' ){
					this.childRemoved(event);
				}
			});
			
		}
		
		/**
		 * Fires the beforeChildAdded event to all component listeners.
		 * @param {ComponentEvent} event
		 * @throws {XataJax.Exception} If any listener decides to cancel the add.
		 */
		function fireBeforeChildAdded(event){
			$.each(members.componentListeners, function(){
				if ( typeof(this.beforeChildAdded) == 'function' ){
					this.beforeChildAdded(event);
				}
			});
		}
		
		/**
		 * Fires the beforeChildRemoved event to all component listeners.
		 * @param {ComponentEvent} event
		 * @throws {XataJax.Exception} if any listener decides to cancel the add.
		 */
		function fireBeforeChildRemoved(event){
			$.each(members.componentListeners, function(){
				if ( typeof(this.beforeChildRemoved) == 'function' ){
					this.beforeChildRemoved(event);
				}
			});
		}
		
		function fireBeforeUpdate(component){
			$.each(members.componentListeners, function(){
				if ( typeof(this.beforeUpdate) == 'function' ){
					this.beforeUpdate(component);
				}
			});
		}
		
		function fireAfterUpdate(component){
			$.each(members.componentListeners, function(){
				if ( typeof(this.afterUpdate) == 'function' ){
					this.afterUpdate(component);
				}
			});
		}
		
		function update(){
			fireBeforeUpdate(this);
			$.each(this.getChildComponents(), function(){
				this.update();
			});
			var layout = this.getLayout();
			if ( layout != null ){
				layout.update();
			} 
			fireAfterUpdate(this);
		}
		
		
		
		
		/**
		 * Gets an array of the child components in this component.
		 *
		 * @returns array(Component)
		 */
		function getChildComponents(){
			return $.merge([], members.children);
		}
		
		/**
		 * Returns the number of children in this component.
		 * @returns int
		 */
		function numChildren(){
			return members.children.length;
		}
		
		
		/**
		 * Sets or gets CSS properties for this component.  There are a few variations
		 * of this method:
		 *
		 * css(string key) : Returns the css value associated with string key.
		 * css(string key, string value) : sets the css value associated with key.
		 * css(void) : gets object with all current css values on this component.
		 * css(object values):  Sets css values base on key value pairs of values.
		 *
		 * @returns mixed
		 *
		 */
		function css(key, value){
			if ( typeof(key) == 'object' ){
				for ( var i in key ){
					this.css(i, key[i]);
				}
			} else if ( typeof(value) != 'undefined' ){
				if ( members.css[key] != value ){
					var old = members.css[key];
					members.css[key] = value;
					$(this.getElement()).css(key, value);
					
					// We fire the property change out to all
					// property change listeners.
					// Inherited from PropertyChangeSupport
					this.firePropertyChange('css#'+key, old, value);
				}
			} else if ( typeof(key) == 'string' ){
				return members.css[key];
			} else {
				return $.extend({}, members.css);
			}
		}
		
		/**
		 * Adds a css class to this component.
		 * @param string cls The css class to add.
		 * @returns void
		 */
		function addClass(cls){
			var idx = members.cssClasses.indexOf(cls);
			if ( idx == -1 ){
				members.cssClasses.push(cls);
				$(this.getElement()).addClass(cls);
				this.firePropertyChange('classes', null, cls, members.cssClasses.length-1);
			}
		}
		
		/**
		 * Removes a CSS class from this component.
		 * @param string cls The css class to remove.
		 * @returns void
		 */
		function removeClass(cls){
			var idx = members.cssClasses.indexOf(cls);
			if ( idx != -1 ){
				members.cssClasses.splice(idx, 1);
				$(this.getElement()).removeClass(cls);
				this.firePropertyChange('classes', cls, null, idx);
			}
		}
		
		/**
		 * Gets the list of CSS classes currently registered to this Component.
		 * @returns array(string)
		 */
		function getClasses(){
			return $.merge([], members.cssClasses);
		}
		
		/**
		 * Sets the value stored in this component (for use with form elements).
		 * This should be overridden by subclasses.
		 *
		 * @param mixed v
		 */
		function setValue(v){
			if ( v != members.value ){
				var old = members.value;
				members.value = v;
				this.firePropertyChange('value', old, v);
			}
		}
		
		/**
		 * Gets the value stored in this component.  (for use with form elements).
		 * This should be overridden by subclasses.
		 *
		 * @returns mixed
		 */
		function getValue(){
			return members.value;
		}
		
		/**
		 * Sets the view mode of the component.
		 * @param int mode
		 */
		function setViewMode(mode){
			if ( mode != members.viewMode ){
				var old = members.viewMode;
				members.viewMode = mode;
				this.firePropertyChange('viewMode', old, mode);
			}
		}
		
		/**
		 * Gets the view mode of the component.
		 * @returns int view mode
		 */
		function getViewMode(){
			return members.viewMode;
		}
		
		
		/**
		 * Checks to see if the child object can be added to this component
		 *
		 * @param {Component} child The child object (component) that is to be added
		 *	as a child of this component.
		 *
		 * @param {mixed} param Corresponds with the 2nd parameter of add().  It may be
		 *		an index or a slot name.
		 *
		 * @returns {boolean}  Whether the child can be added.  The default implementation
		 *		returns true if child is a Component.  Subclasses should override this
		 *		with their own requirements.
		 */
		function isChildAllowed(child, param){
			return XataJax.instanceOf(child, Component);
		}
		
		
		/**
		 * Inserts a child component at the specified x,y coordinate (or the closest thing).
		 *
		 * @param Component c
		 * @param int x
		 * @param int y
		 * @param mixed param Corresponds to param parameter of Component#add() method
		 *
		 */
		function insertAt(c, x, y, param){
			this.add(c, param);
			this.moveTo(c, x, y, param);
		}
		
		
		/**
		 * Moves a child component to a specified x, y coordinate (or the closest)
		 * thing.
		 *
		 * @param Component c
		 * @param int x
		 * @param int y
		 * @param mixed param Corresponds to the param parameter of Component#add() method
		 *
		 */
		function moveTo(c, x, y, param){
			//if ( c.
			var layout = this.getLayout();
			if ( layout != null && typeof(layout.moveTo) == 'function'){
				layout.moveTo(c, x, y, param);
			} else {
				var offset = $(c.getElement()).offset();
				var poffset = $(this.getElement()).offset();
				var deltaX = poffset.left + x - offset.left;
				var deltaY = poffset.top + y - offset.top;
				var leftCSS = c.css('left');
				var rightCSS = c.css('right');
				var topCSS = c.css('top');
				var bottomCSS = c.css('bottom');
				var widthCSS = c.css('width');
				var heightCSS = c.css('height');
				
				if ( typeof(leftCSS) != 'undefined' ){
					// This component explicitly specifies its left position.
					// So we should be able to 
					c.css('left', leftCSS + deltaX);
					
				}
				if ( typeof(rightCSS) != 'undefined' ){
					c.css('right', rightCSS-deltaX);
				}
				
				if ( typeof(topCSS) != 'undefined' ){
					c.css('top', topCSS+deltaY);
				}
				
				if( typeof(bottomCSS) != 'undefined' ){
					c.css('bottom', bottomCSS-deltaY);
				}
				
			}
		}
		
		/**
		 * Unserializes an object into this component.
		 *
		 * @param {Object} o The generic object that is being unserialized.
		 * @returns {Component} Self for chaining.
		 *
		 */
		function unserialize(o){
			var self = this;
			var data = o.data;
			if ( typeof(o.data) == 'object' ){
				if ( typeof(o.data.css) == 'object' ){
					$.each(o.data.css, function(key,val){
						self.css(key,val);
					});
				}
				
				if ( typeof(o.data.cssClasses) != 'undefined' ){
					$.each(o.data.cssClasses, function(){
						self.addClass(this);
					});
				}
				
				self.value = o.value;
				
				if ( typeof(o.data.children) != 'undefined' ){
					$.each(o.data.children, function(){
						var label = this.label;
						
						var cls = XataJax.findConstructor(this.type);
						if ( !cls ){
							throw new Exception({
								message: 'Could not find constructor for type '+this.type+'. Please make sure it is loaded and in the class path.'
							});
						}
						
						var obj = new cls;
						obj.unserialize(this);
						self.add(obj, label);
					});
				}
			}
			return self;
		
		}
		
		/**
		 * Returns the canonical class name of this component.  Should be overridden
		 * by subclasses to show their correct canonical classname.  This is used
		 * for serialization to be able to record and look up the class that 
		 * should be used to unmarshall an object.
		 *
		 * @returns {String}
		 */
		function getCanonicalClassName(){
			return 'XataJax.ui.tk.Component';
		}
		
		
		/**
		 * Serializes this component into a generic object that can be 
		 * easily converted to JSON.
		 *
		 * @param {Object} o A generic object which is populated with properties by this
		 *	method invocatyion.
		 * @returns {Object} The same object that is input.  Gives alternative means of
		 *	outputting it.
		 *
		 */
		function serialize(o){
			o.type = this.getCanonicalClassName();
			o.data = {};
			o.data.css = $.extend({}, members.css);
			o.data.cssClasses = $.merge([], members.cssClasses);
			o.data.value = members.value;
			o.data.children = [];
			$.each(members.children, function(){
				var c = {};
				this.serialize(c);
				c.label = members.childToName(c.getComponentID());
				o.data.children.push(c);
			});
			return o;
			
		}
		
	}
	
	
})();