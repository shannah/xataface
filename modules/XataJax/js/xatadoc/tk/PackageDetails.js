//require <xatajax.ui.tk/Component.js>
//require <xatadoc/tk/__init__.js>
//require <xatajax.doc.js>
//require-css <xatadoc/tk/PackageDetails.css>
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
	var ClassClickedEvent = xatadoc.tk.ClassClickedEvent;
	var PackageClickedEvent = xatadoc.tk.PackageClickedEvent;
	
	xatadoc.tk.PackageDetails = PackageDetails;
	
	
	
	
	/**
	 * A details panel to show the details about a particular package.
	 * @constructor
	 *
	 * @option {PackageInfo} o.packageInfo The PackageInfo object to use as the model.
	 *
	 * @event {PackageClickedEvent} packageClicked Fired when a package is clicked in this component.
	 * @event {ClassClickedEvent} classClicked Fired when a class is clicked in this component.
	 *
	 */
	function PackageDetails(o){
	
		
	
		if ( typeof(o) != 'object' ){
			o = {};
		}
		XataJax.extend(this, new Component(o));
		XataJax.publicAPI(this, {
			setPackageInfo: setPackageInfo,
			getPackageInfo: setPackageInfo,
			update: update,
			createClassLink: createClassLink,
			decorateClassLink: decorateClassLink,
			createTypeLink: createTypeLink,
			createPackageLink: createPackageLink,
			decoratePackageLink: decoratePackageLink
		
		});
		
		
		/**
		 * Stores the class info mode
		 * @type {PackageInfo}
		 */
		var packageInfo = null;
		
		if ( typeof(o.packageInfo) != 'undefined' ){
			packageInfo = o.packageInfo;
		}
		
		
		/**
		 * Sets the packageInfo for this model.
		 * @param 0 {PackageInfo} c The new PackageInfo object.
		 *
		 */
		function setPackageInfo(c){
			if ( c != packageInfo ){
				var old = packageInfo;
				packageInfo = c;
				this.firePropertyChange('packageInfo', old, c);
			}
		}
		
		
		/**
		 * Returns the current ClassInfo model.
		 * @returns {ClassInfo}
		 */
		function getPackageInfo(){
			return packageInfo;
		}
		
		
		function update(){
			var self = this;
			this.getSuper(Component).update();
			if ( ! packageInfo ) return;
			$(this.getElement()).html(@@(xatadoc/tk/PackageDetails.tpl.html));
			var el = this.getElement();
			$('.PackageDetails-packagename-title', el)
				.text(packageInfo.getName())
				;
			$('.package-description', el)
				.text(packageInfo.getDescription());
			
			var pkg = packageInfo.getParentPackage();
			//alert(pkg);
			if ( !pkg ){
				pkg = document.createTextNode('none');
			} else {
				
				pkg = this.createPackageLink(pkg);
			}
			
			$('.PackageDetails-parent-package', el).append(pkg);
			
			$('.PackageDetails-packagename', el).text(packageInfo.getName());
			
			$('.PackageDetails-defined-in', el).text(packageInfo.getFile());
			
			
			var methodTemplate = $('.PackageDetails-method-template', el).get(0);
			var methodsTbody = $('table.PackageDetails-methods-table > tbody', el);
			
			var methods = packageInfo.getFunctionInfos();
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
				$('table.PackageDetails-methods-table', el)
					.after('<div>No public methods</div>')
				.remove();
			}
			
			
			
			
				
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
			$('.PackageDetails-methods-name', row).text(method.getName());
			var buf = [];
			$.each(method.getParameters(), function(){
				var name = this.getName();
				var type = 'mixed';
				if ( this.getType() ){
					type = this.getType().getName();
					if ( this.getType().isArray() ){
						type = 'array('+type+')';
					} else if ( this.getType().isDictionary() ){
						type = 'dict('+type+')';
					}
				}
				buf.push(type + ' ' + name);
			});
			
			$('.PackageDetails-methods-parameters', row).text('('+buf.join(', ')+')');
			
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
			
			$('.PackageDetails-methods-return-type', row).text(returntype);
			
			
			var shortdesc = method.getDescription();
			var longdesc = method.getDescription();
			
			if ( shortdesc && shortdesc.length > 200 ){
				shortdesc = shortdesc.substring(0,200)+'...';
			}
			
			$('.PackageDetails-methods-short-description', row).html(shortdesc);
			$('.PackageDetails-methods-long-description', row).html(longdesc);
			
			$('.PackageDetails-methods-defined-in', row).text('');
			
			
			row.click(function(){
				if ( $(this).hasClass('expanded') ){
					$(this).removeClass('expanded');
				} else {
					$(this).addClass('expanded');
				}
			});
			
			// Set up the parameter options.
			
			var optionsTable = $('.options-table', row);
			var optionTemplate = $('.PackageDetails-option-row-template', optionsTable).get(0);
			
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
				$('.PackageDetails-methods-options', row).remove();
			}
			
			
			// Set up the returns
			var returnsTable = $('.returns-table', row);
			var returnTemplate = $('.PackageDetails-option-row-template', returnsTable).get(0);
			var hasReturns = false;
			$.each(method.getReturns(), function(){
				hasReturns = true;
				returnsTable.append(createOptionRow(self, this, returnTemplate));
				
			});
			$(returnTemplate).remove();
			
			if (!hasReturns){
				$('.PackageDetails-methods-returns', row).remove();
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
			$(a).attr('href', '#c:'+classInfo.getFullName());
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
				//alert('herenow');
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