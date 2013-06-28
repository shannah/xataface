//require <jquery.packed.js>
//require <xatajax.core.js>
(function(){

	var $ = jQuery;

	if (typeof(window.tests)=='undefined' ) window.tests = {};
	window.tests.TestRunner = TestRunner;
	
	function TestRunner(o){
	
		
		var out = document.createElement('div');
		var passed = [];
		var failed = [];
		
		
		XataJax.publicAPI(this, {
		
			assertTrue: assertTrue,
			assertEquals: assertEquals,
			runTests: runTests,
			getTests: getTests
		});
		
		
		function assertTrue(testname, expr){
			if ( expr ){
				passed.push(testname);
			} else {
				failed.push(testname);
			}
		}
		
		function getTests(){
			return [];
		}
		
		function assertEquals(testname, expected, actual){
			if ( expected == actual ){
				passed.push(testname);
			} else {
				failed.push(testname + ' failed [Expected '+expected+' ; Actual '+actual+']');
			}
			appendResults();
		}
		
		function appendResults(){
			
			//$.each(passed, function(){
			while ( passed.length > 0 ){
				var res = passed.shift();
				var row = document.createElement('div');
				$(row).css('background-color', 'green');
				$(row).css('color', 'white');
			
				//$(row).text(this);
				row.appendChild(document.createTextNode(res+ ' Passed'));
				out.appendChild(row);
			}
			
			//$.each(failed, function(){
			while ( failed.length > 0 ){
				var res = failed.shift();
				var row = document.createElement('div');
				$(row).css('background-color', 'red');
				$(row).css('color', 'white');
				row.appendChild(document.createTextNode(res + ' Failed'));
				out.appendChild(row);
			}
	
			
		}
	
		function runTests(outputDiv){
			//alert(this.getTests);
			var tests = this.getTests();
			$.each(tests, function(){
				this();
			});
			
			$(document).ready(function(){
				//alert($(out).html());
				if ( typeof(outputDiv) == 'undefined') outputDiv = $('body').get(0);
				$(outputDiv).append(out);
			});
			
			appendResults();
			
			
		}
	
		
	}
})();