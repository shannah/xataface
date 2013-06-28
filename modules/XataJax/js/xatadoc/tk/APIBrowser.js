
//require <xatajax.ui.tk/Component.js>
//require <xatadoc/tk/ClassDetails.js>
//require <xatadoc/tk/PackageDetails.js>
//require <xatadoc/tk/PackageTree.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
//require-css <xatadoc/tk/APIBrowser.css>

(function(){
	var $ = jQuery;
	var Component = XataJax.ui.tk.Component;
	var ClassDetails = xatadoc.tk.ClassDetails;
	var PackageDetails = xatadoc.tk.PackageDetails;
	var ClassInfo = XataJax.doc.ClassInfo;
	var PackageInfo = XataJax.doc.PackageInfo;
	var PackageTree = xatadoc.tk.PackageTree;
	
	xatadoc.tk.APIBrowser = APIBrowser;
	
	/**
	 * An API browser component that contains a tree of all of the 
	 * loaded package and class heirarchy, and details panels to 
	 * show the details of any class or package.
	 *
	 * @constructor
	 */
	function APIBrowser(o){
		
		
		/**
		 * Inialize internal components first .....
		 */
		 
		 function classClicked(event){
		 	showClass(event.classInfo);
		 	var classPath = event.classInfo.getFullName();
			window.location.hash = '#!/classes/'+classPath;
		 
		 }
		 
		 function packageClicked(event){
		 	showPackage(event.packageInfo);
		 	window.location.hash = '#!/packages/'+event.packageInfo.getName();
		 	
		 }
		 
		/**
		 * @type {ClassDetails}
		 */
		var classDetails = new ClassDetails();
		classDetails
			.bind('classClicked', classClicked)
			.bind('packageClicked', packageClicked);
		
		
		/**
		 * @type {packageDetails}
		 */
		var packageDetails = new PackageDetails();
		packageDetails
			.bind('classClicked', classClicked)
			.bind('packageClicked', packageClicked);
		
		
		var packageTree = new PackageTree();
		//packageTree.setRootPackage(new PackageInfo(XataJax));
		packageTree
			.bind('classClicked', classClicked)
			.bind('packageClicked', packageClicked);
		
		
		// Extends Component.
		XataJax.extend(this, new Component(o));
		
		
		// Define the public API now
		XataJax.publicAPI(this, {
			showClass: showClass,
			showPackage: showPackage,
			getClassDetailsPanel: getClassDetailsPanel,
			getPackageDetailsPanel: getPackageDetailsPanel,
			update: update
		});
		
		
		
		// Layout the canvas with our sub components.
		this.getElement().appendChild(packageTree.getElement());
		this.getElement().appendChild(classDetails.getElement());
		this.getElement().appendChild(packageDetails.getElement());
		
		
		/**
		 * Shows the details for a particulr class.
		 * @param {ClassInfo} classInfo The ClassInfo object of the class to display.
		 */
		function showClass(classInfo){
			classDetails.setClassInfo(classInfo);
			classDetails.update();
			$(classDetails.getElement()).show();
			$(packageDetails.getElement()).hide();
		}
		
		/**
		 * Shows the details for a particular package.
		 * @param {PackageInfo} packageInfo The PackageInfo object of the package to display.
		 */
		function showPackage(packageInfo){
			packageDetails.setPackageInfo(packageInfo);
			packageDetails.update();
			$(classDetails.getElement()).hide();
			$(packageDetails.getElement()).show();
		}
		
		/**
		 * Returns the ClassDetails panel (the panel used to display the details
		 * of currently selected classes.
		 * @returns {ClassDetails}
		 */
		function getClassDetailsPanel(){
			return classDetails;
		}
		
		/**
		 * Returns the PackageDetails panel (the panel used to display the details
		 * of currently selected packages.
		 *
		 * @returns {PackageDetails}
		 */
		function getPackageDetailsPanel(){
			return packageDetails;
		}
		
		function update(){
			this.getSuper(Component).update();
			
			var setSize = function(){
				var winHeight = $(window).height();
				var winWidth = $(window).width();
				var panelHeight = winHeight - 60;
				$('.ui-split-side', el)
				
				.height(panelHeight)
				;
				
				$('.ui-split-main1', el)

				.height(panelHeight)
				;
			};
			
			$(document).ready(setSize);
			$(window).resize(setSize);
			
			
			$(this.getElement()).html(@@(xatadoc/tk/APIBrowser.tpl.html));
			var el = this.getElement();
			
			$('.ui-split-side', el)
				.text('')
				.append(packageTree.getElement())

				;
				
				
			$('.ui-split-main1-content', el)
				.text('')
				.append(classDetails.getElement())
				.append(packageDetails.getElement())
				;
			
			$(function(){
				
				
		
				$('div.ui-split-side', el).resizable({
					handles: 'e',
					proxy: 'proxy',
					minWidth: 200
				});
			});
		
			
			
			packageTree.update();
			classDetails.update();
			packageDetails.update();
		}
		
		
		
	}
})();