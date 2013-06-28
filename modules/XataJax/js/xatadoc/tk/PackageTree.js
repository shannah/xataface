//require <jquery.treeview.js>
//require-css <jquery-treeview/jquery.treeview.css>
//require <xatajax.ui.tk/Component.js>
//require <xatadoc/tk/__init__.js>
//require <xatadoc/tk/PackageClickedEvent.js>
//require <xatadoc/tk/ClassClickedEvent.js>
(function(){
	
	
	var $ = jQuery;
	var PackageInfo = XataJax.doc.PackageInfo;
	var Component = XataJax.ui.tk.Component;
	var ClassInfo = XataJax.doc.ClassInfo;
	var PackageClickedEvent = xatadoc.tk.PackageClickedEvent;
	var ClassClickedEvent = xatadoc.tk.ClassClickedEvent;
	
	
	xatadoc.tk.PackageTree = PackageTree;
	
	
	/**
	 * A component to display a tree to navigate through packages and classes.
	 * @constructor
	 */
	function PackageTree(o){
		
		
		/**
		 * The root package for this tree.
		 * @type {PackageInfo}
		 */
		var rootPackage = null;
		
		XataJax.extend(this, new Component(o));
		
		XataJax.publicAPI(this, {
			update: update,
			setRootPackage: setRootPackage,
			getRootPackage: getRootPackage
		});
		
		
		/**
		 * Sets the root package of this browser.
		 *
		 * @param {PackageInfo} pkg The new root package.
		 */
		function setRootPackage(pkg){
			if ( pkg != rootPackage ){
				var old = rootPackage;
				rootPackage = pkg;
				this.firePropertyChange('rootPackage', old, pkg);
			}
		}
		
		
		/**
		 * Returns the root package.
		 *
		 * @returns {PackageInfo} The root package of this browser.
		 */
		function getRootPackage(){
			return rootPackage;
		}
		
		function update(){
			this.getSuper(Component).update();
			var el = this.getElement();
			$(el).html(@@(xatadoc/tk/PackageTree.tpl.html));
			var packages = [];
			$.each(PackageInfo.getLoadedPackages(), function(){
				packages.push(this);
			});
			if ( rootPackage ){
				packages = [rootPackage];
			}
			var rootEl = $('ul.root', el).get(0);
			var packageTemplate = $('li.packageTemplate', el).get(0);
			var classTemplate = $('li.classTemplate', el).get(0);
			var self = this;
			$.each(packages, function(){
				if ( this.getName().indexOf('.') != -1 ){
					// We only want to show top level packages. 
					// we'll recurse for the rest.
					return;
				}
				
				
				buildPackageTree(self, this, rootEl, packageTemplate, classTemplate); 
				
			});
			$(packageTemplate).remove();
			$(classTemplate).remove();
			$(rootEl).treeview({collapsed:true});
			
			
		}
		
		/**
		 * Builds the package subtree starting from a particular node.
		 *
		 * @param {PackageTree} self Reference to the PackageTree component.
		 * @param {PackageInfo} packageInfo The PackageInfo representing the package root of
		 * 	of this subtree.
		 * @param {HTMLElement} el The HTMLElement ul node that this subtree will
		 *		be added to.
		 * @param {HTMLElement} packageTemplate The li element template for a package.
		 * @param {HTMLElement} classTemplate The li element template for a class.
		 * @returns {void}
		 */
		function buildPackageTree(self, packageInfo, el, packageTemplate, classTemplate){
			var row = $(packageTemplate).clone();
			
			$('span.folder', row)
				.text(packageInfo.getName())
				.click(function(){
					self.trigger('packageClicked', new PackageClickedEvent({
						packageInfo: packageInfo
					}));
				})
				;
			
			var subul = $('ul', row).get(0);
			$.each(packageInfo.getSubPackages(), function(){
				buildPackageTree(self, new PackageInfo(this), subul, packageTemplate, classTemplate); 
			});
			
			$.each(packageInfo.getClassInfos(), function(){
				buildClassTree(self, this, subul, packageTemplate, classTemplate);
			});
			$(el).append(row);
			
		
		}
		
		/**
		 * Builds a class node in a package tree.
		 * @param {PackageTree} self Reference to the packageTree component.
		 * @param {ClassInfo} classInfo The ClassInfo object describing the class that this node
		 *		will represent.
		 * @param {HTMLElement} el The HTMLElement of the ul to which this row will be added.
		 * @param {HTMLElement} packageTemplate The HTMLElement li node template (unused)
		 * @param {HTMLElement} classTemplate The HTMLElement li node template for the class.
		 * @returns {void}
		 */
		function buildClassTree(self, classInfo, el, packageTemplate, classTemplate){
			var row = $(classTemplate).clone();
			$('span.file', row)
				.text(classInfo.getName())
				.click(function(){
					self.trigger('classClicked', new ClassClickedEvent({
						classInfo: classInfo
					
					}));
				})
				;
			$(el).append(row);
		}
	}
	
	
})();