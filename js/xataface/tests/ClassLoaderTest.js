//require <xataface/modules/testrunner/TestRunner.js>
//require <xataface/ClassLoader.js>
(function(){
	var $ = jQuery;
	
	var TestRunner = xataface.modules.testrunner.TestRunner;
	var eq = TestRunner.assertEquals;
	var tr = TestRunner.assertTrue;
	var ClassLoader = xataface.ClassLoader;
	
	TestRunner.addTest(testModel);
	
	
	function testModel(){
		
		var _ = 'xataface.tests.ClassLoaderTest: ';
		
		window.TestClassThatNowExists = {};
		var loader = new ClassLoader();
		
		
		// With only one parameter, it runs in synchronous mode
		var testClass = loader.loadClass('TestClassThatNowExists');
		eq(_+'TestClass exists (synchronous)', window.TestClassThatNowExists, testClass);
		
		var nonExistentClass = loader.loadClass('TestClassThatDoesntExist');
		console.log(nonExistentClass);
		eq(_+'Test class doesnt exist (synchronous)', null, nonExistentClass);
		
		// Asynchronous loading should just return self.
		var res = loader.loadClass('TestClassThatNowExists', function(){});
		eq(_+'Return self for async execution', loader, res);
		
		var asyncLoadedClass = null;
		loader.loadClass('TestClassThatNowExists', function(o){
			asyncLoadedClass = o.Class;
		});	
		
		eq(_+'Async loaded class as parameter to callback', 
			window.TestClassThatNowExists,
			asyncLoadedClass
		);
		
		loader.loadClass('TestClassThatDoesntExist', function(o){
			eq(_+'Async loaded class not found as parameter to callback',
				null,
				o.Class
			);
		});
		
		eq(_+'Existent class isnt loaded yet so should be null',
			null,
			XataJax.load('xataface.tests.SampleLoadableClass', false)
		);
		
		loader.loadClass('xataface.tests.SampleLoadableClass', function(o){
			// This should load the class from the server
			tr(_+'Loaded SampleLoadableClass from the server is object', o.Class instanceof Object);
			tr(_+'Loaded SampleLoadableClass from the server is not null', o.Class != null);
			try {
				tr(_+'Loaded SampleLoadableClass from the server is SampleLoadableClass', 
					xataface.tests.SampleLoadableClass instanceof Object
				);
			} catch (e){
				eq(_+'Exception loading SampleLoadableClass', true, false);
			}
		
		});
		
		
		loader.require(
			['xataface.tests.SampleRequiredClass1', 'xataface.tests.SampleRequiredClass2'],
			function(o){
				eq(_+'Required classes parameter', 'object', typeof(o.classes));
				eq(_+'Required classes class 1', 
					'function', 
					typeof(o.classes['xataface.tests.SampleRequiredClass1'])
				);
				eq(_+'Required classes class 2', 
					'function', 
					typeof(o.classes['xataface.tests.SampleRequiredClass2'])
				);
				try {
					tr(_+'Loaded Required Class 1', 
						xataface.tests.SampleRequiredClass1 instanceof Object
					);
				} catch (e){
					eq(_+'Exception loading required class 1', true, false);
				}
				
				try {
					tr(_+'Loaded Required Class 2', 
						xataface.tests.SampleRequiredClass2 instanceof Object
					);
				} catch (e){
					eq(_+'Exception loading required class 2', true, false);
				}
				
			}
		);
		
	
	}
})();