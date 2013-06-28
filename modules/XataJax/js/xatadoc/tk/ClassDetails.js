//require <xatajax.ui.tk/Component.js>
//require <xatadoc/tk/__init__.js>
//require <xatajax.doc.js>
//require-css <xatadoc/tk/ClassDetails.css>
//require <xatadoc/tk/PackageClickedEvent.js>
//require <xatadoc/tk/ClassClickedEvent.js>

(function(){
	var $ = jQuery;
	var Component = XataJax.ui.tk.Component;
	var ClassInfo = XataJax.doc.ClassInfo;
	var ReturnInfo = XataJax.doc.ReturnInfo;
	var PackageInfo = XataJax.doc.PackageInfo;
	var TypeInfo = XataJax.doc.TypeInfo;
	var PropertyInfo = XataJax.doc.PropertyInfo;
	var PackageClickedEvent = xatadoc.tk.PackageClickedEvent;
	var ClassClickedEvent = xatadoc.tk.ClassClickedEvent;
	
	xatadoc.tk.ClassDetails = ClassDetails;
	
	
	
	
	/**
	 * A details panel to show the details about a particular class.
	 * @constructor
	 *
	 * @event {PackageClickedEvent} packageClicked Fired when a package is clicked in this component.
	 * @event {ClassClickedEvent} classClicked Fired when a class is clicked in this component.
	 *
	 */
	function ClassDetails(o){
	
		
	
		if ( typeof(o) != 'object' ){
			o = {};
		}
		XataJax.extend(this, new Component(o));
		XataJax.publicAPI(this, {
			setClassInfo: setClassInfo,
			getClassInfo: getClassInfo,
			update: update,
			createClassLink: createClassLink,
			decorateClassLink: decorateClassLink,
			createTypeLink: createTypeLink,
			createPackageLink: createPackageLink,
			decoratePackageLink: decoratePackageLink
		
		});
		
		
		/**
		 * Stores the class info mode
		 * @type {ClassInfo}
		 */
		var classInfo = null;
		
		if ( typeof(o.classInfo) != 'undefined' ){
			classInfo = o.classInfo;
		}
		
		
		/**
		 * Sets the classInfo for this model.
		 * @param 0 {ClassInfo} c The new ClassInfo object.
		 *
		 */
		function setClassInfo(c){
			if ( c != classInfo ){
				var old = classInfo;
				classInfo = c;
				this.firePropertyChange('classInfo', old, c);
			}
		}
		
		
		/**
		 * Returns the current ClassInfo model.
		 * @returns {ClassInfo}
		 */
		function getClassInfo(){
			return classInfo;
		}
		
		
		function update(){
			this.getSuper(Component).update();
			if ( !classInfo ) return;
			$(this.getElement()).html(@@(xatadoc/tk/ClassDetails.tpl.html));
			var el = this.getElement();
			$('.ClassDetails-classname-title', el)
				.text(classInfo.getName())
				;
			$('.class-description', el)
				.html(classInfo.getDescription());
				
				
			var superClasses = classInfo.getSuperClasses();
			var buf = [];
			var self = this;
			$.each(superClasses, function(){
				var link = self.createClassLink(this);
				buf.push(link);
				//if ( link ){
				//	buf.push(link);
				//} else {
				//	buf.push(document.createTextNode(this.getName()));
				//}
				buf.push(document.createTextNode(', '));
				
			});
			if ( buf.length > 0 ) buf.pop();
			$('.ClassDetails-superclasses', el).text('');
			$.each(buf, function(){
				$('.ClassDetails-superclasses', el).append(this);
			});
			
			
			var subClasses = classInfo.getSubClasses();
			var buf = [];
			
			$('.ClassDetails-subclasses', el).text('');
			$.each(subClasses, function(){
				var link = self.createClassLink(this);
				if ( link ){
					buf.push(link);
				} else {
					buf.push(document.createTextNode(this.getName()));
				}
				buf.push(document.createTextNode(', '));
			});
			
			if ( buf.length > 0 ) buf.pop();
			$.each(buf, function(){
				$('.ClassDetails-subclasses', el).append(this);
			});
			
			var pkg = classInfo.getPackage();
			if ( !pkg ){
				pkg = document.createTextNode('none');
			} else {
				pkg = this.createPackageLink(pkg);
			}
			$('.ClassDetails-package', el)
				.text('')
				.append(pkg)
				;
			
			$('.ClassDetails-classname', el).text(classInfo.getName());
			
			$('.ClassDetails-defined-in', el).text(classInfo.getFile());
			
			
			var methodTemplate = $('.ClassDetails-method-template', el).get(0);
			var methodsTbody = $('table.ClassDetails-methods-table > tbody', el);
			
			var methods = classInfo.getMethods(true);
			var methodsArr = [];
			$.each(methods, function(){
				var variants = this.getVariants();
				if ( variants.length == 0 ){
					methodsArr.push(this);
				} else {
				
					$.each(variants, function(){
						//alert(this.getParameters()[0].getType().getName());
						methodsArr.push(this);
					});
				}
				
			});
			methodsArr.sort(function(a,b){
				if ( a.getName() < b.getName() ) return -1;
				else if ( a.getName() > b.getName() ) return 1;
				else return 0;
			});
			
			if ( methodsArr.length > 0 ){
				$.each(methodsArr, function(){
					methodsTbody.append(createMethodRow(self, this, methodTemplate));
					
				});
				$(methodTemplate).remove();
			} else {
				$('table.ClassDetails-methods-table', el)
					.after('<div>No public methods</div>')
				.remove();
			
			}
			
			var propertiesArr = [];
			$.each(classInfo.getProperties(), function(){
				propertiesArr.push(this);
			});
			//if ( propertiesArr.length 
			//alert(JSON.stringify(propertiesArr));
			if ( propertiesArr.length > 0 ){
				var propertyTemplate = $('.ClassDetails-properties-template', el).get(0);
				var propertiesBody = $('table.ClassDetails-properties-table > tbody', el);
				//alert("Body size: "+propertiesBody.size());
				$.each(propertiesArr, function(){
					propertiesBody.append(createPropertyRow(self, this, propertyTemplate));
				});
				$(propertyTemplate).remove();
			} else {
				$('table.ClassDetails-properties-table', el)
					.after('<div>No public properties</div>')
				.remove();
				//after('<div>No public properties.</div>')
				//	.remove();
			}
			
			var constr = classInfo.getConstructorInfo();
			var constructors = [];
			if ( constr.getVariants().length > 0 ){
				$.each(constr.getVariants(), function(){
					constructors.push(this);
				});
			} else {
				constructors.push(constr);
			}
			var constructorTemplate = $('.ClassDetails-constructor-template', el).get(0);
			var constructorsBody = $('table.ClassDetails-constructors-table > tbody', el);
			
			$.each(constructors, function(){
				constructorsBody.append(createConstructorRow(self, this, constructorTemplate));
			});
			$(constructorTemplate).remove();
			
			
			var events = classInfo.getEvents(true);
			var eventsArr = [];
			$.each(events, function(){
				eventsArr.push(this);
			});
			eventsArr.sort(function(a,b){
				if ( a.getName() < b.getName() ) return -1;
				else if ( a.getName() > b.getName() ) return 1;
				else return 0;
			});
			
			if ( eventsArr.length > 0 ){
				var eventTemplate = $('.ClassDetails-events-template', el).get(0);
				var eventsBody = $('table.ClassDetails-events-table > tbody', el);
				$.each(eventsArr, function(){
					eventsBody.append(createEventRow(self, this, eventTemplate));
				});
				$(eventTemplate).remove();
			} else {
				$('table.ClassDetails-events-table', el)
					.after('<div>No public events</div>')
				.remove();
				//after('<div>No public properties.</div>')
				//	.remove();
			}
			
			
			
				
		}
		
		function createConstructorRow(self, method, template){
			var row = createMethodRow(self, method, template);
			$('.ClassDetails-methods-short-description', row).remove();
			$('.ClassDetails-methods-long-description', row).remove();
			
			return row;
		}
		
		function createEventRow(self, prop, template){
		
			var row = $(template).clone();
			$('.ClassDetails-events-name',row).text(prop.getName());
			
			var typename = 'mixed';
			if ( prop.getType() ){
				typename = prop.getType().getName();
			}
			var link = null;
			link = self.createTypeLink(prop.getType());
			
			if ( link ){
				$('.ClassDetails-events-type', row).text('');
				$('.ClassDetails-events-type',row).append(link);
			} else {
				$('.ClassDetails-events-type',row).text(typename);
			}
			
			var shortDesc = prop.getDescription();
			var longDesc = prop.getDescription();
			if ( shortDesc && shortDesc.length > 200 ){
				shortDesc = shortDesc.substr(0, 200)+'...';
			}
			
			$('.ClassDetails-events-short-description',row).html(shortDesc);
			$('.ClassDetails-events-long-description',row).html(longDesc);
			
			$(row).click(function(event){
				
				if ( $(this).hasClass('expanded') ){
					$(this).removeClass('expanded');
				} else {
					$(this).addClass('expanded');
				}
				//event.stopPropagation();
			});
			
			return row;
		}
		
		
		function createPropertyRow(self, prop, template){
		
			var row = $(template).clone();
			$('.ClassDetails-properties-name', row).text(prop.getName());
			
			var typename = 'mixed';
			if ( prop.getType() ){
				typename = prop.getType().getName();
			}
			var link = null;
			link = self.createTypeLink(prop.getType());
			
			if ( link ){
				$('.ClassDetails-properties-type',row).text('');
				$('.ClassDetails-properties-type', row).append(link);
			} else {
				$('.ClassDetails-properties-type', row).text(typename);
			}
			
			var shortDesc = prop.getDescription();
			//alert(XataJax.instanceOf(prop, PropertyInfo));
			var longDesc = prop.getDescription();
			if ( shortDesc && shortDesc.length > 200 ){
				shortDesc = shortDesc.substr(0, 200)+'...';
			}
			
			$('.ClassDetails-properties-short-description', row).html(shortDesc);
			$('.ClassDetails-properties-long-description', row).html(longDesc);
			
			$(row).click(function(event){
				
				if ( $(this).hasClass('expanded') ){
					$(this).removeClass('expanded');
				} else {
					$(this).addClass('expanded');
				}
				//event.stopPropagation();
			});
			
			return row;
		}
		
		function createOptionRow(self, option, template){
		
			var row = $(template).clone();
			$('.option-name', row).text(option.getName());
			var typename = 'mixed';
			if ( option.getType() ){
				typename = option.getType().getName();
			}
			
			var link = null;
			if ( option.getType().getClassInfo() ){
				link = self.createClassLink(option.getType().getClassInfo());
				
			}
			if ( link ){
				$('.option-type', row).text('');
				$('.option-type', row).append(link);
			} else {
			
				$('.option-type', row).text(typename);
			}
			
			var shortDesc = option.getDescription();
			var longDesc = option.getDescription();
			if ( longDesc && longDesc.length > 200 ){
				shortDesc = shortDesc.substr(0, 200)+'...';
				
			}
			$('.option-short-description', row).html(shortDesc);
			$('.option-long-description', row).html(longDesc);
			
			$(row).click(function(event){
				//alert('here');
				if ( $(this).hasClass('expanded') ){
					$(this).removeClass('expanded');
				} else {
					$(this).addClass('expanded');
				}
				
				event.stopPropagation();
			});
			
			return row;
		}
		
		
		function createMethodRow(self, method, template){
			var row = $(template).clone();
			$('.ClassDetails-methods-name', row).text(method.getName());
			var buf = [];
			$.each(method.getParameters(), function(){
				var name = this.getName();
				var type = 'mixed';
				if ( this.getType() ){
					type = this.getType().getName();
					if ( this.getType().isArray() ){
						buf.push(document.createTextNode('array('));
						buf.push(self.createTypeLink(this.getType()));
						buf.push(document.createTextNode(')'));
						//type = 'array('+type+')';
					} else if ( this.getType().isDictionary() ){
						buf.push(document.createTextNode('dict('));
						buf.push(self.createTypeLink(this.getType()));
						buf.push(document.createTextNode(')'));
					} else {
						buf.push(self.createTypeLink(this.getType()));
					}
				} else {
					buf.push(document.createTextNode(type));
				}
				buf.push(document.createTextNode(' '+name));
				buf.push(document.createTextNode(', '));
				//buf.push(type + ' ' + name);
			});
			
			buf.pop();
			buf.unshift(document.createTextNode('('));
			buf.push(document.createTextNode(')'));
			$('.ClassDetails-methods-parameters', row).text('');
			$.each(buf, function(){
				$('.ClassDetails-methods-parameters', row).append(this);
			});
			//.text('('+buf.join(', ')+')');
			
			var returns = method.getReturns();
			if ( returns.length > 0 ){
				returns = returns[0];
			}
			var returntype = 'void';
			if ( XataJax.instanceOf(returns, ReturnInfo) ){
				returntype = returns.getType().getName();
				if ( returns.getType().isArray() ){
					returntype = 'array('+returntype+')';
				} else if ( returns.getType().isDictionary() ){
					returntype = 'dict('+returntype+')';
				}
			}
			
			$('.ClassDetails-methods-return-type', row).text(returntype);
			
			
			var shortdesc = method.getDescription();
			var longdesc = method.getDescription();
			
			if ( shortdesc && shortdesc.length > 200 ){
				shortdesc = shortdesc.substring(0,200)+'...';
			}
			
			$('.ClassDetails-methods-short-description', row).html(shortdesc);
			$('.ClassDetails-methods-long-description', row).html(longdesc);
			
			$('.ClassDetails-methods-defined-in', row)
				.text('')
				.append(self.createClassLink(method.getClassInfo()));
			
			
			row.click(function(){
				if ( $(this).hasClass('expanded') ){
					$(this).removeClass('expanded');
				} else {
					$(this).addClass('expanded');
				}
			});
			
			// Set up the parameter options.
			
			var optionsTable = $('.options-table', row);
			var optionTemplate = $('.ClassDetails-option-row-template', optionsTable).get(0);
			
			var hasParameters = false;
			$.each(method.getParameters(), function(){
				hasParameters = true;
				optionsTable.append(createOptionRow(self, this, optionTemplate));
				$.each(this.getOptions(), function(){
					optionsTable.append(createOptionRow(self, this, optionTemplate));
				});
			});
			$(optionTemplate).remove();
			
			if (!hasParameters){
				$('.ClassDetails-methods-options', row).remove();
			}
			
			
			// Set up the returns
			var returnsTable = $('.returns-table', row);
			var returnTemplate = $('.ClassDetails-option-row-template', returnsTable).get(0);
			var hasReturns = false;
			$.each(method.getReturns(), function(){
				hasReturns = true;
				returnsTable.append(createOptionRow(self, this, returnTemplate));
				
			});
			$(returnTemplate).remove();
			
			if (!hasReturns){
				$('.ClassDetails-methods-returns', row).remove();
			}
			return row.get(0);
			
			
			
			
		}
		
		/**
		 * Creates a link for the given ClassInfo object.  The default implementation
		 * just returns null, but you can override it in a subclass, or redefine your
		 * own implementation that returns an HTMLElement that will be used to represent
		 * classes in this component.  This is useful if you want class names to be 
		 * active in the interface.
		 *
		 * @param {ClassInfo} classInfo The ClassInfo object for which you are building a link.
		 * @returns {HTMLElement} Should return an A tag HTMLElement for the given classInfo
		 * object.
		 */
		function createClassLink(classInfo){
			
			var self = this;
			var a = document.createElement('a');
			var classPath = classInfo.getFullName();
			$(a).attr('href', '#!/classes/'+classPath);
			$(a).click(function(){
				self.trigger('classClicked', new ClassClickedEvent({
					classInfo: classInfo
				}));
			});
			$(a).text(classInfo.getName());
			this.decorateClassLink(classInfo, a);
			return a;
		}
		
		/**
		 * Decorates a link to a class as generated by ClassDetails#createClassLink
		 * @param {ClassInfo} classInfo The class to generate the link for.
		 * @param {HTMLElement} el The HTMLElement to decorate.
		 * @returns {void}
		 */
		function decorateClassLink(classInfo, el){
				
		
		}
		
		
		/**
		 * Creates a link for a specified type.
		 * @param {TypeInfo} type The type for which a link is being generated.
		 * @returns {HTMLElement} An HTMLElement link to the type.  This link
		 * 		may in fact go nowhere if the type is a primitive type of 
		 *		doesn't have documentation for some reason.
		 * 
		 * @see createClassLink If the type is a valid class then this method simply returns
		 * 	 the result of createClassLink on the type's ClassInfo object.
		 *
		 */
		function createTypeLink(type){
			if ( !type ){
				var a = document.createElement('a');
				$(a).text('mixed');
				return a;
			}
			var classInfo = type.getClassInfo();
			if ( classInfo ){
				return this.createClassLink(classInfo);
			} else {
				var a = document.createElement('a');
				$(a).text(type.getName());
				return a;
			}
		}
		
		/**
		 * Creates a link for a specified package.
		 * @param {PackageInfo} pkg The package for which we are creating a link.
		 * @returns {HTMLElement} The link element to link to this package.
		 *
		 */
		function createPackageLink(pkg){
			var self = this;
			var a = document.createElement('a');
			$(a)
				.text(pkg.getName())
				.attr('href', '#!/packages/'+pkg.getName())
				.click(function(){
					self.trigger('packageClicked', new PackageClickedEvent({
						packageInfo: pkg
					}));
				})
				;
				
			this.decoratePackageLink(pkg, a);
			return a;
		
		}
		
		/**
		 * Decorates a link for a package as generated by the createPackageLink method.
		 * @param {PackageInfo} pkg The package for which the link was created.
		 * @param {HTMLElement} el The link element that was generated by the createPackageLink
		 *	method.
		 * @returns {void}
		 */
		function decoratePackageLink(pkg, el){
		
		}
		
		
		
		
		
		
		
		
		
	}
	
	
	

})();