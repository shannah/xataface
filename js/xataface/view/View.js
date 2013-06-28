//require <jquery.packed.js>
//require <xataface/model/Model.js>
(function(){
	var $ = jQuery;
	var view = XataJax.load('xataface.view');
	var model = XataJax.load('xataface.model');
	var Model = model.Model;
	var $m = Model.wrap;
	view.View = View;
	
	
	function View(/*Object*/ o){
		$.extend(this, o);
		var self = this;
		this.onChangeHandler = function(){
			self.update();
		}
		if ( this.model != null ){
			var m = this.model;
			this.model = null;
			this.setModel(m);
			//$(this.model).bind('changed', this.onChangeHandler);
		}
		
		if ( this.el == null ){
			this.el = this.createElement();
		} else {
			this.el = $(this.el);
		}
		
		this.decorate();
	}
	
	
	(function(){
		$.extend(View.prototype, {
			update : update,
			_update : _update,
			decorate : decorate,
			_decorate : _decorate,
			undecorate : undecorate,
			_undecorate : _undecorate,
			model : null,
			setModel : setModel,
			createElement : createElement,
			el : null
		});
		
		
		function createElement(){
			return $('<div>').get(0);
		}
		
		function setModel(/*Model*/ model){
			if ( model != this.model ){
				var oldModel = this.model;
				if ( this.model != null ){
					$(this.model).unbind('changed', this.onChangeHandler);
				}
				this.model = model;
				if ( this.model != null ){
					$(this.model).bind('changed', this.onChangeHandler);
				}
				$(this).trigger('modelChanged', {
					oldModel : oldModel,
					newModel : this.model
				});
			}
			return this;
		}
		
		
		function update(){
			$(this).trigger('beforeUpdate');
			
			this._update();
			$(this).trigger('afterUpdate');
			return this;	
		}
		
		function _update(){
			var self = this;
			var model = $m(this.model);
                        $('[data-kvc]:not(.subview [data-kvc])', this.el).add(this.el).each(function(){
				if ( !$(this).attr('data-kvc') ){
					return;
				}
				var el = this;
				var $this = $(el);
				var kvc = $this.attr('data-kvc');
				kvc = kvc.split(';');
				$.each(kvc, function(k,v){
					var parts = v.split(':');
					$.each(parts, function(k,v){
						parts[k] = $.trim(v);
					});
					if ( parts.length === 2 ){
						$this.attr(parts[0], model.get(parts[1]));
						
					} else {
						
						var setFunc = $this.text;
						if ( $this.is(':input') ){
							setFunc = $this.val;
						}
                                                var val = model.get(parts[0]);
                                                if ( val === undefined ){
                                                    val = '';
                                                }
                                                setFunc.call($this, val);
                                                
                                            
					}
				});
				
				
			});
		}
		
		function decorate(){
			$(this).trigger('beforeDecorate');
			this._decorate();
			$(this).trigger('afterDecorate');
			return this;
		}
		
		function _decorate(){
			return this;
		}
		
		function undecorate(){
			$(this).trigger('beforeUndecorate');
			this._undecorate();
			$(this).trigger('afterUndecorate');
			return this;
		}
		
		
		function _undecorate(){
			return this;
		}
		
	})();
})();