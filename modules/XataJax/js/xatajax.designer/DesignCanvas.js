//require <jquery-ui.min.js>
//require <xatajax.designer.core.js>
//require <xatajax.ui.tk/Component.js>
//require <xatajax.ui.tk/ComponentListener.js>
//require <xatajax.beans/PropertyChangeListener.js>
//require <xatajax.undo/UndoableEditSupport.js>
//require-css <jquery-ui/jquery-ui.css>
//require-css <xatajax.designer/DesignCanvas.css>
(function(){

	/**
	 * Import classes that we use into this namespace.
	 */
	var Component = XataJax.ui.tk.Component;
	var ComponentListener = XataJax.ui.tk.ComponentListener;
	var PropertyChangeListener = XataJax.beans.PropertyChangeListener;
	var $ = jQuery;
	var Exception = XataJax.Exception;
	var UndoableEditSupport = XataJax.undo.UndoableEditSupport;


	/**
	 * Register public API.
	 */
	XataJax.ui.designer.DesignCanvas = DesignCanvas;
	
	
	/**
	 * Returns the DOMElement that represents the slot that can
	 * be added to in the ancestor chain for a given element target.  This is 
	 * helpful when testing where to drop a component in the designer
	 * so that we know which components can actually be dropped onto.
	 *
	 * @param DOMElement el The child element of the slot we are looking for.  This
	 * 	will usually be the event.target of a jquery event.
	 *
	 * @param int index The index of the element.  Default is 0.  If this number
	 * 	is greater than 0 then it will use a slot higher up in the chain.
	 *
	 * @param function indicator An indicator function that can optionally provided
	 * 	to indicate whether a given slot is eligible.  This is handy if a component
	 *	only allows certain types of components to be dropped in some slots.  Then
	 *	they can provide this parameter as a function to return true.
	 *
	 * @returns DOMElement The DOM Element where a component can be added.
	 */
	function getSlot(el, index, indicator){
		if ( typeof(indicator) != 'function' ) indicator = function(){return true;}
		if ( typeof(index) == 'undefined' ){
			index = 0;
		}
		var currEl = $(el);
		while ( currEl.size() > 0 ){
			var slot = currEl.parent('.xatajax-ui-component-slot');
			if ( indicator(slot) && index--==0 ) return $(slot).get(0);
			else currEl = slot;
		}
		return null;
		
	}
	
	
	/**
	 * Gets the component where a specified child component can be dropped.
	 *
	 * @param Component component The child component that we are testing
	 * 	to try to drop.
	 *
	 * @param DOMElement targetEl The target of the mouse event.  This would
	 *	be a descendent of the drop target (if one is found).
	 *
	 * @param int index If we don't want to take the first matching drop
	 * 	target slot, this optional integer can be used to indicate which
	 *	one we want to take.  E.g. index=1 would indicate that we want
	 *	to take the second ancestor we find in the component heirarchy. Default
	 *	value is 0.
	 *
	 * @returns object(component: COMPONENT, param: SLOTNAME or INDEX) An object with the
	 *		parent component to which to add the child, and the name of the slot
	 *		(if any) in which to add the child to said component.
	 *
	 */
	function getDropTarget(component, targetEl, index){
		return getSlot(targetEl, index, function(el){
			var wrapperComponent = Component.getComponentWrapper(el);
			var slotName = $(el).data('xatajax-component-slot');
			
			if ( wrapperComponent != null && wrapperComponent.isChildAllowed(component, slotName) ){
				return true;
			}
			return false;
		});
		
			
	}
	
	function getDropComponentTarget(component, targetEl, index){
		var slot = getDropTarget(component, targetEl, index);
		
		if ( !slot ) return null;
		
		var wrapperComponent = Component.getComponentWrapper(el);
		var slotName = $(el).data('xatajax-component-slot');
		if ( !wrapperComponent ){
			throw new Exception({
				message: 'Internal error trying to find wrapper component for drop target.'
			});
		}
		
		
		return {
			component: wrapperComponent,
			param: slotName
		};
	}
	
	/**
	 * @constructor
	 */
	function DesignCanvas(o){
		XataJax.extend(this, new Component(o));
		XataJax.extend(this, new UndoableEditSupport(o));
		
		XataJax.publicAPI(this, {
			decorateSelected: decorateSelected,
			undecorateSelected: undecorateSelected,
			setSelected: setSelected
		});
		var self = this;
		
		/**
		 * Internal list of currently selected components on this canvas.
		 *
		 * @see setSelected
		 */
		var selectedComponents = [];
		
		/**
		 * Internal reference to the component (if any) that is currently hovered
		 * over.
		 *
		 * @see setHovered
		 */
		var hoveredComponent = null;
		
		
		/**
		 * A reference to the component that is currently being resized.
		 * 
		 * @type Component
		 */
		var resizingComponent = null;
		
		
		/**
		 * A list of the components that are currently being dragged around the canvas.
		 *
		 * @type array(Component)
		 */
		var draggingComponents = null;
		
		
		/**
		 * The DOMElement that is the current drop target.
		 * @see setDropTarget
		 *
		 * @type DOMElement 
		 */
		var dropTarget = null;
		
		
		
		/**
		 * Instead of using the mouseEntered and mouseExited events
		 * we are tracking the current mouse target ourselves that 
		 * we refresh on the mouseMove event.  This allows us to track
		 * everything with a single listener attached to the canvas, rather
		 * than having to attach a separate listener to each child component
		 * on the canvas.
		 *
		 * @type DOMElement
		 */
		var mouseTarget = null;
		
		
		/**
		 * The current mouse event.
		 *
		 * @type jQuery.Event
		 */
		var mouseEvent = null;
		
		
		
		/**
		 * A listener to handle when components are added to and removed
		 * from the canvas.
		 */
		var componentListener = new CanvasChildListener();
		this.addComponentListener(componentListener);
		installMouseListeners(this);
		activateDropZones(this);
		
		//this.addPropertyChangeListener('mouseTarget', new MouseTargetChangeListener());
		
		
		
		
		/**
		 * Installs mouse listeners into a component.
		 * 
		 * @param Component c
		 */
		function installMouseListeners(c){
			$(c.getElement())
				.click(elementMouseClicked)
				//.mouseenter(elementMouseEnter)
				.mousedown(elementMouseDown)
				//.mouseleave(elementMouseLeave)
				.mouseup(elementMouseUp)
				//.mousemove(elementMouseMove)
				;
				
		}
		
		
		/**
		 * Uninstalls mouse listeners into a component.
		 *
		 * @param Component c
		 */
		function uninstallMouseListeners(c){
			$(c.getElement())
				.unbind('click', elementMouseClicked)
				//.unbind('mouseenter', elementMouseEnter)
				.unbind('mousedown', elementMouseDown)
				//.unbind('mouseleave', elementMouseLeave)
				//.unbind('mousemove', elementMouseMove)
				.unbind('mouseup', elementMouseUp);
		}
		
		function elementMouseMove(event){
			mouseEvent = event;
			setMouseTarget(event.target);
			return false;
		}
		
		
		/**
		 * Handles jquery element mouse event.
		 *
		 * Executed in the context of the source DOM element.
		 *
		 * @param jquery.event event
		 */
		function elementMouseClicked(event){
			var c = Component.getComponentWrapper(event.target);
			if ( c ){
				componentMouseClicked(c, event);
			}
			
			return false;
		}
		
		/**
		 * Handles jquery element mouse event.
		 *
		 * Executed in the context of the source DOM element.
		 *
		 * @param jquery.event event
		 */
		function elementMouseEnter(event){
			var c = Component.getComponentWrapper(event.target);
			if ( c ){
				componentMouseEnter(c, event);
			}
			
			if ( draggingComponents != null ){
				
				var comp = draggingComponents[0];
				setDropTarget(getDropTarget(comp, event.target, 0));
				
			}
			
		}
		
		
		/**
		 * Sets the current drop target.  The drop target is an HTML element
		 * representing a slot into which a component can be dropped.  This element
		 * will always have the CSS class "xatajax-ui-component-slot".  It may
		 * optionally also have data associated with it with the key 
		 * "xatajax-component-slot" identifying the name of the slot within
		 * the wrapper component.  This name can be used as the 2nd parameter
		 * of the Component#add method to specify where the child component
		 * is to be added.
		 *
		 * A drop target is assigned the 'xatajax-ui-designer-droptarget' css class
		 * and takes on a notable visual appearance to provide feedback to the user
		 * indicating that the component can be dropped here.
		 *
		 * Generally the drop target would only be set while another component is 
		 * being dragged.
		 * 
		 * @param DOMElement target The element to be set as the drop target.
		 */
		function setDropTarget(target){
			if ( dropTarget != target ){
				if ( dropTarget != null ){
					$(dropTarget).removeClass('xatajax-ui-designer-droptarget');
				}
				dropTarget = target;
				if ( dropTarget != null ){
					$(dropTarget).addClass('xatajax-ui-designer-droptarget');
				}
			}
		}
		
		
		/**
		 * Sets the mouse target (this is the element that has received the most
		 * recent mouse event.
		 *
		 * If the target is different than the previous one, then a PropertyChangeEvent
		 * is fired on the mouseTarget property.
		 *
		 * @param DOMElement target
		 */
		function setMouseTarget(target){
			if ( mouseTarget != target ){
				var old = mouseTarget;
				mouseTarget = target;
				self.firePropertyChange('mouseTarget', old, mouseTarget);
			}
		}
		
		/**
		 * Handles jquery element mouse event.  Executed in the context
		 * of the source DOM element.
		 *
		 * @param jquery.event event
		 */
		function elementMouseDown(event){
			var c = Component.getComponentWrapper(event.target);
			if ( c ){
				componentMouseDown(c, event);
			}
			return false;
		}
		
		/**
		 * Handles jquery mouse event.  Executed in the context of the source 
		 * DOM Element.
		 *
		 * @param jquery.event event
		 */
		function elementMouseLeave(event){
			var c = Component.getComponentWrapper(event.target);
			if ( c ){
				componentMouseLeave(c, event);
			}
			
			if ( draggingComponents ){
				setDropTarget(null);
			}
		}
		
		/**
		 * Handles jquery mouse event.  Executed in the context of the source 
		 * DOM Element.
		 *
		 * @param jquery.event event
		 */
		function elementMouseUp(event){
			var c = Component.getComponentWrapper(event.target);
			if ( c ){
				componentMouseUp(c, event);
			}
			setDropTarget(null);
		}
		
		/**
		 * Triggered when a component on the canvas is clicked.  This will 
		 * handle the selection/deselection of components on the canvas 
		 * appropriately.
		 *
		 * @param Component c
		 * @param jquery.event event
		 */
		function componentMouseClicked(c, event){
			
			if ( !event.shiftKey ){
				$.each(selectedComponents, function(){
					if ( c != this ){
						self.setSelected(this, false);
					}
				});
			}
			if ( c != self ){
				self.setSelected(c, true);
			}
		}
		
		/**
		 * Triggered when mouse enters the component on the canvas.  This 
		 * will handle the hover feedback of the components on the canvas
		 * appropriately.
		 *
		 * @param Component c
		 * @param jquery.event event
		 */
		function componentMouseEnter(c, event){
			setHovered(c, true);
		}
		
		/**
		 * Triggered when mouse is pressed down on a component on the canvas.
		 * This may signal the start of a drag event.
		 *
		 * @param Component c
		 * @param jquery.event event
		 */
		function componentMouseDown(c, event){
			var offset = $(c.getElement()).offset();
			var el = c.getElement();
			var resizeLeft = false;
			var resizeTop = false;
			var resizeRight = false;
			var resizeBottom = false;
			if ( Math.abs(offset.top - event.pageY) < 10 ){
				resizeTop = true;
			} else if ( Math.abs(offset.top+$(c.getElement()).height() - event.pageY) < 10 ){
				resizeBottom = true;
			}
			
			if ( Math.abs(offset.left - event.pageX) < 10 ){
				resizeLeft = true;
			} else if ( Math.abs(offset.left+$(c.getElement()).width() - event.pageX) < 10 ){
				resizeRight = true;
			}
			
			if ( resizeLeft || resizeTop || resizeBottom || resizeRight ){
				resizingComponent = c;
				var startBounds = {
					top: offset.top,
					left: offset.left,
					width: $(el).width(),
					height: $(el).height()
				};
				var currX = event.pageX;
				var currY = event.pageY;
				
				$(self.getElement()).bind('mousemove.XataJax.DesignCanvas.resize', function(evt){
					var deltaX = evt.pageX-currX;
					var deltaY = evt.pageY-currY;
					var leftCSS = c.css('left');
					var rightCSS = c.css('right');
					var topCSS = c.css('top');
					var bottomCSS = c.css('bottom');
					var widthCSS = c.css('width');
					var heightCSS = c.css('height');
					if ( resizeLeft ){
						
						if ( typeof(leftCSS) != 'undefined' ){
							// This component explicitly specifies its left position.
							// So we should be able to 
							c.css('left', leftCSS + deltaX);
							if ( typeof(rightCSS) == 'undefined' ){
								if ( typeof(widthCSS) == 'undefined' ){
									widthCSS = $(el).width();
								}
								c.css('width', widthCSS - deltaX);
							}
						} else {
							// This component doesn't explicitly specify its left
							// position... but if it specifies its right position,
							// then we can probably achieve the same effect
							// by simply increasing its width
							if ( typeof(rightCSS) == 'undefined' ){
								// No explicit right position so we can't do anything here.
								
							} else {
								if ( typeof(widthCSS) == 'undefined' ){
									widthCSS = $(el).width();
								}
								c.css('width', widthCSS-deltaX);
							}
						
						}
					} else if ( resizeRight ){
						if ( typeof(rightCSS) != 'undefined' ){
							// This component explicitly specifies its right position
							// so we should be able to resize the right side
							// by simply adjusting the 'right' css parameter.
							c.css('right', rightCSS-deltaX);
						} else {
							// This component does not specify its right position explicitly
							// so our best bet is to adjust its width.
							if ( typeof(widthCSS) == 'undefined' ){
								widthCSS = $(el).width();
							}
							c.css('width', widthCSS+deltaX);
						}
					}
					
					
					if ( resizeTop ){
						if ( typeof(topCSS) != 'undefined' ){
							// top is explicitly specified
							c.css('top', topCSS + deltaY );
							if ( typeof(bottomCSS) == 'undefined' ){
								if ( typeof(heightCSS) == 'undefined' ){
									heightCSS = $(el).height();
								}
								c.css('height', heightCSS-deltaY);
							}
						} else {
							// top is not explicitly specified
							if ( typeof(bottomCSS) == 'undefined' ){
								// No explicit bottom position so we can't do anything here.
								
							} else {
								if ( typeof(heightCSS) == 'undefined' ){
									heightCSS = $(el).height();
								}
								c.css('height', heightCSS-deltaY);
							}
						} 
					} else if ( resizeBottom ){
						if ( typeof(bottomCSS) != 'undefined' ){
							// bottom is explicitly specified so we should be able
							// to adjust the bottom directly.
							c.css('bottom', bottomCSS-deltaY);
						} else {
							// no bottom specified so we will adjust this using the 
							// height setting
							if ( typeof(heightCSS) == 'undefined' ){
								heightCSS = $(el).height();
							}
							c.css('height', heightCSS+deltaY);
						}
					}
					
					currX = evt.pageX;
					currY = evt.pageY;
				});
			} else {
				/*
				// This isn't a resize, it's a move.
				draggingComponents = [c];
				//alert(draggingComponents);
				//get the bounds of the component.. we aren't actually
				// going to move the component just now... we're going to 
				// create an outline and move that around.
				var width = $(el).width();
				var height = $(el).height();
				var offset = $(el).offset();
				var dy = offset.top - event.pageY;
				var dx = offset.left - event.pageX;
				
				var silhouette = document.createElement('div');
				$(silhouette).addClass('xatajax-component-moving-silhouette');
				$(self.getElement()).append(silhouette);
				$(silhouette)
					.width(width)
					.height(height)
					.offset(offset);
				$(self.getElement())
					.bind('mousemove.XataJax.DesignCanvas.move', function(e){
						$(silhouette).css({
							top: e.pageY+dy,
							left: e.pageX+dx
						});
					})
					.bind('mouseup.XataJax.DesignCanvas.move', function(event){
						$(self.getElement()).unbind('mousemove.XataJax.DesignCanvas.move');
						$(self.getElement()).unbind('mouseup.XataJax.DesignCanvas.move');
						var deltaX = $(silhouette).offset().left - offset.left;
						var deltaY = $(silhouette).offset().top - offset.top;
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
						
						$(silhouette).remove();
						draggingComponents = null;
						
						
					});
				
				*/
			
			}
			
			
			
			
			
		}
		
		/**
		 * Triggered when the mouse pointer leaves the confines of a component.  This
		 * cancels the "hovered" status of the component.
		 *
		 * @param Component c
		 * @param jquery.event event
		 *
		 */
		function componentMouseLeave(c, event){
			setHovered(c, false);
		}
		
		/**
		 * Triggered when a mouse is unpressed over a component.
		 *
		 * @param Component c
		 * @param jquery.event event
		 */
		function componentMouseUp(c, event){
			//alert("unbinding");
			$(self.getElement()).unbind('mousemove.XataJax.DesignCanvas.resize');
		
		}
		
		
		/**
		 * Sets whether a component is selected or not.
		 *
		 * @param Component c
		 * @param boolean sel
		 * @event PropertyChangeEvent(selectedComponent : Component)
		 * @event PropertyChangeEvent(selectedComponents: array(Component))
		 */
		function setSelected(c, sel){
			var el = $(c.getElement());
			var cls = 'xatajax-ui-designer-selected';
			if ( sel ){
				if ( !el.hasClass(cls) ){
					el.addClass(cls)
					
				}
				var idx = selectedComponents.indexOf(c);
				if ( idx == -1 ){
					var old = $.merge([], selectedComponents);
					selectedComponents.push(c);
					//alert('decorating selected');
					this.decorateSelected(c);
					this.firePropertyChange('selectedComponent', null, c, selectedComponents.length);
					this.firePropertyChange('selectedComponents', old, selectedComponents);
					
				}
			} else {
				if ( el.hasClass(cls) ){
					el.removeClass(cls);
					var idx = selectedComponents.indexOf(c);
					if ( idx != -1 ){
						var old = $.merge([], selectedComponents);
						selectedComponents.splice(idx, 1);
						this.undecorateSelected(c);
						this.firePropertyChange('selectedComponent', c, null, idx);
						this.firePropertyChange('selectedComponents', old, selectedComponents);
						
					}
				}
			}
		}
		
		/**
		 * When a component is selected, this modifies it and its elements 
		 * to add necessary CSS and listeners.
		 *
		 * @param Component c
		 */
		function decorateSelected(c){
			$(c.getElement()).addClass('ui-state-focus');
			addDragHandle(c);
			//activateDropZones(c);
		}
		
		
		
		function handleDrop(sourceComponent, targetComponent, droppable, event, ui){
			var sourceParent = Component.getComponentWrapper($(sourceComponent().getElement()).parent());
			var targetParent = Component.getComponentWrapper($(targetComponent().getElement()).parent());
			
			var slotName = $(droppable).data('xatajax-component-slot');
			// if they are in the same parent
				// if the layout is absolute
					// if source in same slot where it is being dropped
						// move source to new x,y coords
					// else
						// remove source from existing slot
						// add to new slot
						// move to correct x,y coords
				// else 
					// if source in same slot where it is being dropped
						//
				//
		}
		
		
		/**
		 * Activates the drop zones for a component.  This is called on a component
		 * when it is added to the designer.
		 *
		 * @param Component c The component which has been selected and may be
		 *	 dropped somewhere else in the canvas.
		 */
		function activateDropZones(c){
		
			function activate(){
				var slotname = $(this).data('xatajax-component-slot');
				//alert($(this).html());
				var componentWrapper = Component.getComponentWrapper(this);
				//alert(this);
				//alert(componentWrapper.isChildAllowed(c, slotname));
				if ( componentWrapper != null ){
					//alert('here');
					$(this).droppable({
						accept: function(draggable){
							return true;
							if ( !$(draggable).hasClass('xatajax-component') ) return false;
							var comp = Component.getComponentWrapper(draggable);
							if ( !comp ) return false;
							return componentWrapper.isChildAllowed(comp, slotname);
						},
						tolerance: 'pointer',
						hoverClass: 'ui-state-focus',
						greedy: true,
						
						drop: function(event, ui){
							
						}
						
						
					});
				}
			}
			
			
			$('.xatajax-ui-component-slot',c.getElement()).each(activate);
			$(c).each(activate);
			//$(self.getElement()).each(activate);
			
			//$('.xatajax-ui-component-slot', c.getElement()).droppable('destroy');
		}
		
		
		/**
		 * The opposite of the activateDropZones.  Removes listeners from
		 * drop zones for a component.  This is generally performed when a component
		 * is deselected.
		 *
		 * @param Component c The component that has been deselected.
		 */
		function deactivateDropZones(c){
			function deactivate(){
				$(this).droppable('destroy');
			}
			
			$('.xatajax-ui-component-slot', c.getElement()).each(deactivate);
			$(c).each(deactivate);
		}
		
		
		/**
		 * Repositions the drag handle for a component.  This is sometimes necessary if
		 * the component has been moved or resized.
		 *
		 * @param Component c The component onto which we are installing the drag handle.
		 */
		function updateDragHandle(c){
		
			if ( c.dragHandle != null ){
				var cOffset = $(c.getElement()).offset();
				var dOffset = $(self.getElement()).offset();
				var rOffset = {
					top: cOffset.top-dOffset.top,
					left: cOffset.left-dOffset.left
				};
				
				var cWidth = $(c.getElement()).width();
				var cHeight = $(c.getElement()).height();
				
				var hWidth = $(c.dragHandle).width();
				var hHeight = $(c.dragHandle).height();
				
				// We want the drag handle centered on the element
				$(c.dragHandle).offset({
					top: rOffset.top + cHeight/2 - hHeight/2,
					left: rOffset.left + cWidth/2 - hWidth/2
				});
			}
		}
		
		
		/**
		 * Adds a drag handle to a component.  This is generally performed with
		 * the component is selected so that it gives the user an opportunity to 
		 * reposition the component by dragging it.
		 *
		 * @param Component c The component to which the drag handle is being added.
		 */
		function addDragHandle(c){
			//alert("adding handle");
			if ( !c.dragHandle  ){
				//alert("in if");
				var handle = document.createElement('div');
				c.dragHandle = handle;
				$(handle).addClass('xatajax-designer-draghandle');
				$(handle).addClass('ui-icon');
				$(handle).addClass('ui-icon-arrow-4');
				$(c.getElement()).draggable({
					handle: handle,
					helper: function(){
						var el = c.getElement();
						var width = $(el).width();
						var height = $(el).height();
						var offset = $(el).offset();
						
						
						var silhouette = document.createElement('div');
						$(silhouette).addClass('xatajax-component-moving-silhouette');
						$(self.getElement()).append(silhouette);
						$(silhouette)
							.width(width)
							.height(height)
							.offset(offset);
						return silhouette;
					},
					opacity: 0.35,
					revert: 'invalid',
					stack: '.xatajax-component',
					appendTo: self.getElement()
					//containment: self.getElement()
				});
				
				$(c.getElement()).append(c.dragHandle);
				updateDragHandle(c);
			}
			
			
			
		}
		
		
		/**
		 * The opposite of addDragHandle.  Removes the drag handle from 
		 * a component.
		 *
		 * @param Component c The component from which the drag handle is being
		 * 	removed.
		 */
		function removeDragHandle(c){
			if ( c.dragHandle ){
				$(c.dragHandle).remove();
				$(c.getElement()).draggable("destroy");
				delete c.dragHandle;
			}
		}
		
		/**
		 * When a component is deselected, this modifies it and its elements
		 * to remove CSS and listeners that were added by the decorateSelected
		 * method.
		 *
		 * @param Component c
		 */
		function undecorateSelected(c){
			$(c.getElement()).removeClass('ui-state-focus');
			removeDragHandle(c);
			//deactivateDropZones(c);
		}
		
		
		/**
		 * Sets whether a component is hovered over or not.
		 *
		 * @param Component c
		 * @param boolean sel
		 */
		function setHovered(c, sel){
			var el = $(c.getElement());
			var cls = 'xatajax-ui-designer-hovered';
			if ( sel ){
				if ( !el.hasClass(cls) ){
					if ( c != self ){
						el.addClass(cls)
					}
					
					if ( hoveredComponent != null && c != hoveredComponent){
						setHovered(hoveredComponent, false);
					}
			
					if ( c != self ){
						hoveredComponent = c;
					}
				}
			} else {
				if ( el.hasClass(cls) ){
					el.removeClass(cls);
					if ( hoveredComponent == c ){
						hoveredComponent = null;
					}
				}
			}
		}
		
		
		/**
		 * An internal class that responds when components are 
		 * added and removed.
		 */
		function CanvasChildListener(o){
			XataJax.extend(this, new ComponentListener(o));
			
			$.extend(this, {
				childAdded:childAdded,
				childRemoved:childRemoved,
				afterUpdate: afterUpdate
			});
			
			
			/**
			 * Whenever a child is added we need to add the appropriate
			 * listeners to that child.
			 *
			 * @param ComponentEvent
			 */
			function childAdded(event){
				//installMouseListeners(event.component);
				event.component.addComponentListener(this);
				event.component.setViewMode(Component.VIEW_MODE_DESIGN);
				activateDropZones(event.component);
				//$(event.component.getElement()).mouseover(function(){
				//	$('body').append("Mouse is over.... ");
				//});
			}
			
			function childRemoved(event){
				///uninstallMouseListeners(event.component);
				event.component.removeComponentListener(this);
				deactivateDropZones(event.component);
			}
			
			function afterUpdate(component){
				
			}
		
		}
		
		
		function MouseTargetChangeListener(o){
			XataJax.extend(this, new PropertyChangeListener(o));
			
			$.extend(this,{
				propertyChange:propertyChange
			});
			function propertyChange(source, propertyName, oldValue, newValue){
				$('body').append(' now');
				if ( draggingComponents != null ){
					$('body').append(' here');
					var comp = draggingComponents[0];
					setDropTarget(getDropTarget(comp, newValue, 0));
					
				}
			}
		}
	}
	
	
	
})();