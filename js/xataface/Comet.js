//require <jquery.packed.js>
//require <xatajax.form.core.js>
(function(){
	var $ = jQuery;
	var xataface = XataJax.load('xataface');
	var submitForm = XataJax.load('XataJax.form.submitForm');
	
	xataface.Comet = Comet;
	
	/**
	 * @class
	 * @memberOf xataface
	 * @description A class to initiate Comet connections to the server.  A comet connection
	 * is one that is kept alive so that the server can continue to stream responses.  The
	 * server must return an HTML page (not JSON or Javascript), and it can communicate 
	 * back by adding <script>..</script> tags in the stream.  
	 *
	 * <p>The server script is loaded in an iframe so that it doesn't block.</p>
	 *
	 * @property {String} method The request method.  Either 'get' or 'post'.  Default is 'get'.
	 * @property {Object} query The query parameters.  This is used for 'post' requests only.
	 * @property {String} url The URL to the server comet handler.
	 * @property {Object} context An object containing the context that is added to the 
	 * iframe's content window.  Any objects that need to be interacted with from the
	 * comet handler, should be part of this context.
	 *
	 * @param {Object} o The input parameters for the constructor.
	 * @param {String} o.method The request method.  Either 'get' or 'post'.  Default 'get'.
	 * @param {Object} o.query The query parameters used if it is a post request.
	 * @param {String} o.url The URL to the service.
	 * @param {Object} o.context The context that is added to the window object of the iframe.
	 */
	function Comet(o){
		this._iframeName += Math.floor(Math.random()*1000);
		if ( typeof(o.query) == 'object' ) this.method = 'post';
		$.extend(this, o);
	}
	
	(function(){
		$.extend(Comet.prototype, {
			method: 'get',
			_iframeName: 'xataface-Comet-iframe',
			query: {},
			url: DATAFACE_SITE_HREF,
			open: open,
			close: close,
			_iframe: null,
			context: {}
		});
		
		
		/**
		 * @function
		 * @name open
		 * @memberOf xataface.Comet#
		 * @description Opens the comet connection.  This creates a hidden iframe
		 * and makes the call to the iframe.
		 * @returns {void}
		 */
		function open(){
			if ( this._iframe != null ) throw new Exception("Comet port already open");
			this._iframe = $('<iframe>')
				.attr('src', this.url)
				.attr('name', this._iframeName)
				.css('display','none');
			if ( this.method.toLowerCase() == 'get' ){
				this._iframe.attr('src',this.url);
			}
			this._iframe.appendTo($('body'));
			$.extend(this._iframe.get(0).contentWindow, this.context);
			if ( this.method.toLowerCase() == 'post' ){
				submitForm('post', this.query, this._iframeName, this.url);
			}
			
			
			
		}
		
		
		/**
		 * @function
		 * @name close
		 * @memberOf xataface.Comet#
		 * @description Closes the comet connection. This removes the hidden iframe.
		 * @returns {void}
		 */
		function close(){
			if ( this._iframe == null ) throw new Exception("Comet port already closed");
			this._iframe.remove();
		}
		
		
	})();
	
})();