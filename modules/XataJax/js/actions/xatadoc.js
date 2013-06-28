//require <xatadoc/tk/ClassDetails.js>
//require <xatadoc/tk/APIBrowser.js>
//require <xatajax.doc.js>
//require <json/cycle.js>
//require <jquery.address.min.js>
(function(){
	
	var PackageInfo = XataJax.doc.PackageInfo;
	var ClassDetails = xatadoc.tk.ClassDetails;
	var ClassInfo = XataJax.doc.ClassInfo;
	var Component = XataJax.ui.tk.Component;
	var APIBrowser = xatadoc.tk.APIBrowser;
	
	PackageInfo.buildPackageIndex();
	//alert(XataJax.__packages__['XataJax.ui.tk']);
	
	
	
	//$('body').append(details.getElement());
	//details.update();
	//var c = new Component();
	//alert(c.get.__variants__);
	var ci = new ClassInfo(XataJax.ui.tk.Component)
	//alert(JSON.stringify(ci.getMethods().get.getMethod().__variants__));
	//alert(JSON.stringify(ci.getMethods().get.getVariants()[1].toString()));
	
	var browser = new APIBrowser();
	browser.showClass(ci);
	$('body').append(browser.getElement());
	browser.update();
	
	
	$.address.change(function(event){
		
		//alert(event.path);
		var parts = event.path.split('/');
		parts.shift();
		
		var type = parts.shift();
		var name = parts.shift();
		
		if ( type == 'packages'){
			try {
				var pkg = PackageInfo.getPackageByName(name);
				browser.showPackage(pkg);
			} catch (ex){}
			//break;
				
		} else if  (type == 'classes'){
			
			try {
				var pkgName = name.split('.');
				var clsName = pkgName.pop();
				
				pkgName = pkgName.join('.');
				//alert(pkgName);
				var pkg = PackageInfo.getPackageByName(pkgName);
				var cls = pkg.getClassInfos()[clsName];
				browser.showClass(cls);
			} catch (ex){}
		}	
		
		
	});

	
})();