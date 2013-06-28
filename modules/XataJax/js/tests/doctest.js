//require <xatajax.core.js>
/**
 * A package to serve as top level package.
 * @package
 */
var ca = {};

(function(){

	var $ = jQuery;

	 
	 /**
	  * A Sub package for web lite solutions.
	  * @package
	  */
	 ca.weblite = {};
	 ca.weblite.TestClass = TestClass;
	 ca.weblite.TestSubClass = TestSubClass;
	 
	 
	 /**
	  * A test class to try out the documentation feature.
	  * @constructor
	  */
	 function TestClass(o){
	 	
	 	XataJax.publicAPI(this, {
	 		startCar: startCar,
	 		stopCar: stopCar,
	 		reverseCar: reverseCar
	 	});
	 	
	 	
	 	/**
	 	 * This method starts the car.
	 	 * @returns {int} Number of seconds it took to start the car.
	 	 */
	 	function startCar(){
	 	
	 	}
	 	
	 	
	 	/**
	 	 * This method stops the car.
	 	 * @returns {mixed} Either the number of seconds to stop the car or null if it failed.
	 	 */
	 	function stopCar(){
	 		
	 	}
	 	
	 	
	 	/**
	 	 * Reverses the car.
	 	 * @param 0 {TestClass} gear The gear that we are pushing into.
	 	 * @param 1 {String} message The message to display on the dashboard.
	 	 * @returns {dict Object} An object with a bunch of properties
	 	 */
	 	function reverseCar(gear, message){
	 		
	 	}
	 
	 }
	 
	 /**
	  * A subclass of TestClass.
	  * @constructor
	  */
	 function TestSubClass(o){
	 	
	 	XataJax.extend(this, new TestClass(o));
	 	
	 	
	 
	 }
	 
	 
	 
	 
})();


XataJax.ready(function(){
	var PackageInfo = XataJax.doc.PackageInfo;
	var ClassInfo = XataJax.doc.ClassInfo;
	var passed = [];
	var failed = [];
	var tests = [];
	
	function assertTrue(testname, expr){
		if ( expr ){
			passed.push(testname);
		} else {
			failed.push(testname);
		}
	}
	
	function assertEquals(testname, expected, actual){
		if ( expected == actual ){
			passed.push(testname);
		} else {
			failed.push(testname + ' failed [Expected '+expected+' ; Actual '+actual+']');
		}
	}
	
	
	function runTests(){
		$.each(tests, function(){
			this();
		});
		
		var out = document.createElement('div');
		$.each(passed, function(){
			var row = document.createElement('div');
			$(row).css('background-color', 'green');
			$(row).css('color', 'white');
		
			//$(row).text(this);
			row.appendChild(document.createTextNode(this+ ' Passed'));
			out.appendChild(row);
		});
		
		$.each(failed, function(){
			var row = document.createElement('div');
			$(row).css('background-color', 'red');
			$(row).css('color', 'white');
			row.appendChild(document.createTextNode(this + ' Failed'));
			out.appendChild(row);
		});

		$(document).ready(function(){
			//alert($(out).html());
			$('body').append(out);
		});
		
	}
	var pkgInfo = new PackageInfo(ca);
	var weblitePkgInfo = new PackageInfo(ca.weblite);
	
	function testPackageName(){
		
		assertTrue('Package Name', pkgInfo.getName() == 'ca');
		assertTrue('Package Name Weblite', weblitePkgInfo.getName() == 'ca.weblite');
	}
	
	tests.push(testPackageName);
	
	function testPackageDescription(){
		assertTrue('Package Description', pkgInfo.getDescription() == 'A package to serve as top level package.');
		assertEquals('Package Description Weblite', 'A Sub package for web lite solutions.', weblitePkgInfo.getDescription());
	}
	tests.push(testPackageDescription);
	
	
	function testTestclass(){
		var classInfo = new ClassInfo(ca.weblite.TestClass);
		assertEquals('TestClass name', 'TestClass', classInfo.getName());
		var count = 0;
		for ( var i in classInfo.getMethods()){
			count++;
		}
		
		assertEquals('TestClass number of methods', 3, count);
		
		var startCarInfo = classInfo.getMethods().startCar;
		assertEquals('TestClass::startCar getName', 'startCar', startCarInfo.getName());
		assertEquals('TestClass::startCar getDescription', 'This method starts the car.', startCarInfo.getDescription());
		
		var returns = startCarInfo.getReturns();
		assertEquals('TestClass::startCar num returns', 1, returns.length);
		var returnInfo = returns[0];
		
		assertEquals('TestClass::startCar return type', 'int', returnInfo.getType().getName());
		assertEquals('TestClass::startCar return description', 'Number of seconds it took to start the car.', returnInfo.getDescription());
		assertEquals('TestClass::startCar return get MethodInfo', startCarInfo, returnInfo.getMethodInfo());
		assertEquals('TestClass::startCar return type constructor', null, returnInfo.getType().getConstructor());
		assertEquals('TestClass::startCar return type isArray', false, returnInfo.getType().isArray());
		assertEquals('TestClass::startCar return type isDictionary', false, returnInfo.getType().isDictionary());
	
	
		var reverseCarInfo = classInfo.getMethods().reverseCar;
		assertEquals('TestClass::reverseCar getName', 'reverseCar', reverseCarInfo.getName());
		assertEquals('TestClass::reverseCar getDescription', 'Reverses the car.', reverseCarInfo.getDescription());
		assertEquals('TestClass::reverseCar num parameters', 2, reverseCarInfo.getParameters().length);
		
		var param1 = reverseCarInfo.getParameters()[0];
		assertEquals('TestClass::reverseCar param1 name', 'gear', param1.getName());
		assertEquals('TestClass::reverseCar param1 desc', 'The gear that we are pushing into.', param1.getDescription());
		assertEquals('TestClass::reverseCar param1 type', 'TestClass', param1.getType().getName());
		assertEquals('TestClass::reverseCar param1 index', 0, param1.getIndex());
		assertEquals('TestClass::reverseCar param1 type constructor', ca.weblite.TestClass, param1.getType().getConstructor());
		
		
		assertEquals('TestClass num subclasses', 1, classInfo.getSubClasses().length);
		assertEquals('TestClass subclass', ca.weblite.TestSubClass, classInfo.getSubClasses()[0].getConstructor());
		assertEquals('TestClass subclass superclass',
			ca.weblite.TestClass,
			classInfo.getSubClasses()[0].getSuperClasses()[0].getConstructor()
		);
	}
	
	tests.push(testTestclass);
	
	
	$(document).ready(function(){
		runTests();
	});
	
})();