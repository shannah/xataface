//require <xatajax.ui.tk/Component.js>
//require <xatajax.ui.tk/HTMLComponent.js>
//require <xatajax.ui.tk/layout/AbsoluteLayout.js>
//require <xatajax.ui.tk/layout/ColumnLayout.js>
//require <xatajax.ui.tk/layout/BorderLayout.js>
//require <xatajax.designer/DesignCanvas.js>

(function(){
	var $ = jQuery;
	var Component = XataJax.ui.tk.Component;
	var AbsoluteLayout = XataJax.ui.tk.layout.AbsoluteLayout;
	var ColumnLayout = XataJax.ui.tk.layout.ColumnLayout;
	var HTMLComponent = XataJax.ui.tk.HTMLComponent;
	var BorderLayout = XataJax.ui.tk.layout.BorderLayout;
	var DesignCanvas = XataJax.ui.designer.DesignCanvas;
	
	var c1 = new DesignCanvas();// new Component();
	c1.setLayout(new AbsoluteLayout());
	c1.css('background-color', '#eaeaea');
	c1.css('width', '100%');
	c1.css('height', 300);
	
	var c2 = new Component();
	c2.setLayout(new ColumnLayout());
	
	var b1 = new HTMLComponent();
	b1.css({
		border: '1px solid blue',
		top: 20,
		left: 100,
		right: 100,
		'text-align': 'center'
	});
	b1.setContent('<p>Hello World</p>');
	c1.add(b1);
	
	
	var b3 = new HTMLComponent();
	b3.setContent('Column 1 content');
	c2.add(b3);
	
	var b4 = new HTMLComponent();
	b4.setContent('Column 2 content');
	c2.add(b4);
	
	var c3 = new Component();
	c3.setLayout(new BorderLayout());
	c3.css('height', 800);
	
	//c2.add(c3);
		
	var p1 = new HTMLComponent();
	p1.setContent('<h1>Heading 1</h1>');
	p1.css('height', 100);
	c3.add(p1, 'top');
		
	c2.css({
		top: 10,
		left: 10,
		width: 400
		});
	c1.add(c2);
	$(document).ready(function(){
		$('body').append(c1.getElement());
		//$('body').append(c2.getElement());
		//$('body').append(c3.getElement());
	});
		
	
})();