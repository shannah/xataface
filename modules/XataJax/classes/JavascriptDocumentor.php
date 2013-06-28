<?php
require 'Dataface/JavascriptTool.php';
class JavascriptDocumentor extends Dataface_JavascriptTool {

	public static $primitives = array(
		'int',
		'float',
		'double',
		'boolean',
		'mixed',
		'void'
	);
	
	private $docImported = false;
	
	public function __construct(){
		parent::__construct();
		$this->setMinify(false);
		//$this->import('xatajax.doc.js');
	}
	
	public function import($path){
		if ( !$this->docImported ){
			$this->docImported = true;
			$this->import('xatajax.doc.js');
		}
		parent::import($path);
	}
	
	public function getContents(){
		$contents = parent::getContents();
		return $this->process($contents);
	}
	
	protected function _compile($scripts, $passthru=false, $onceOnly=true){
		return $this->process(parent::_compile($scripts, $passthru, $onceOnly));
	}
	
	
	public function getConstructor($type){
		if ( !trim($type) or in_array(trim($type), self::$primitives) ){
			return 'null';
		} else {
			
			return $type;
		}
	}

	
	public function process($str){
		$str = preg_replace_callback('#(/\*\*(((?!\*/).)*)@package(.*?)\*/)\W*(var )?([a-zA-Z_$][0-9a-zA-Z_$\.]*)([^;]*)\;#ms', array($this, 'process_package_callback'), $str );
		$str = preg_replace_callback('#(/\*\*(((?!\*/).)*)\*/)\W*function ([a-zA-Z_$][0-9a-zA-Z_$]*)#ms', array($this, 'process_functions_callback'), $str);
		$str = preg_replace_callback('#(/\*\*(((?!\*/).)*)\*/)\W*var ([a-zA-Z_$][0-9a-zA-Z_$]*)#ms', array($this, 'process_variables_callback'), $str );
		
		return $str;
	}
	
	
	protected function decorateContents($contents, $script){
		return preg_replace('#/\*\*(.*?)\*/#ms', '/**\\1'."\n@file $script\n".'*/', $contents);
	}
	
	public function process_package_callback($matches){
		$doc = array();
		$doc['name'] = trim($matches[6]);
		$doc['isPackage'] = true;
		$js = '';
		$str = $matches[2];
		
		
		$str = preg_replace('#^\W*\*#m', '', $str);
		if ( preg_match('#(.*?)(?=@[a-z]{4,}|\z)#s', $str, $matches2)){
			$doc['description'] = $matches2[1];
		}
		$doc['description'] = trim($doc['description']);
		$super = null;
		$parts = explode('.', $doc['name']);
		if ( count($parts)>1 ){
			array_pop($parts);
			$super = implode('.', $parts);
		}
		$jsondoc = json_encode($doc);
		$jsonstr = json_encode($str);
		$jsonName = json_encode($doc['name']);
		
		$js .= <<<END
		
		//alert($jsonstr);
if ( typeof($doc[name].__doc__) == 'undefined' ){
	$doc[name].__doc__ = $jsondoc;
	
	// Now register this package with XataJax
	if ( typeof(XataJax.__packages__) == 'undefined' ){
		XataJax.__packages__ = {};
	}
	if ( typeof(XataJax.__packages__[$jsonName]) == 'undefined' ){
		XataJax.__packages__[$jsonName] = $doc[name];
	}
		
	
}
END;
		return $matches[5].$doc['name'].$matches[7]."\n".$js;
	}
	
	public function process_variables_callback($matches){
		$doc = array();
		$doc['name'] = trim($matches[4]);
		$js = '';
		$str = $matches[2];
		
		$str = preg_replace('#^\W*\*#m', '', $str);
		if ( preg_match('#(.*?)(?=@[a-z]{4,}|\z)#s', $str, $matches2)){
			$doc['description'] = $matches2[1];
		}
		
		
		
		$jsondoc = json_encode($doc);
		$js .= <<<END
		
		if ( typeof(arguments) != 'undefined' ){
if ( typeof(arguments.callee.__doc__) == 'undefined' ){
	arguments.callee.__doc__ = {};
}
if ( typeof(arguments.callee.__doc__._private) == 'undefined' ){
	arguments.callee.__doc__._private = {};
}
arguments.callee.__doc__._private.$doc[name] = $jsondoc;
END;

		$matches2 = null;
		if ( !preg_match('/@type \{([^\}]+)\}/', $str, $matches2) ){
			preg_match('/@type (.*)$/', $str, $matches2);
		}
		if ( $matches2 ){
			$type = trim(preg_replace('/(.*?)([^ ]+)$/', '\\2', $matches2[1]));
			$constructor = $this->getConstructor($type);
			
			$info = array(
				'typename' => preg_replace('/(.*?)([^ ]+)$/', '\\2', $matches2[1]),
				'isArray' => preg_match('/^array /', $matches2[1]),
				'isDictionary' => preg_match('/^dict /', $matches2[1])
			);
			$jsoninfo = json_encode($info);
			$js .= <<<END
			
			arguments.callee.__doc__._private.$doc[name].type = $jsoninfo;
			\$.each([arguments.callee.__doc__._private.$doc[name].type], function(){
				this._constructor = $constructor;
				this.name = this.typename;

			});
			
			
END;
			
			
		}
		
		return $js."}\nvar ".$matches[4];
	}
	
	public function process_functions_callback($matches){
		//print_r($matches);exit;
		$doc = array();
		$doc['name'] = $matches[4];
		$js = '';
		
		$str = $matches[2];
		$str = preg_replace('#^\W*\*#m', '', $str);
		
		
		if ( preg_match('/@file (.*)/', $str, $matches2)){
			$doc['file'] = trim($matches2[1]);
		}
		
		
		$isConstructor = false;
		if ( strpos($str, '@constructor') !== false ){
			$isConstructor = true;
			
			$js .= "\n".'new '.$doc['name'].";\n";
			
			
		}
		
		if ( preg_match('#(.*?)(?=@[a-z]{4,}|\z)#s', $str, $matches2)){
			$doc['description'] = trim($matches2[1]);
		}
		$doc['isConstructor'] = $isConstructor;
		
		$jsondoc = json_encode($doc);
		
		$js .= <<<END
	
$doc[name].__doc__ = $jsondoc;
END;
		
		
		$fullstr = $str;
		
		if ( strpos($str, '@variant') === false ){
			$str = '@variant __default__ '.$str;
		}

		while ( ($vindex = strrpos($str, '@variant')) !== false ){
			$str1 = substr($str, 0, $vindex);
			//echo $str1;exit;
			$str2 = substr($str, $vindex);
			$str = $str1;
			
			$js .= <<<END
			
			(function(){
				var ___parameters___ = [];
				var ___returns___ = [];
				var ___events___ = {};
END;

			if ( preg_match('/@variant (\w+)( .*?)?(?=(@[a-z]{4,})|\z)/s', $str2, $matches2)){
				$variantname = json_encode(trim($doc['name']));
				$variantdesc = json_encode(trim($matches2[2]));
				
				if ( trim($matches2[1]) == '__default__' ){
					$js .= "\n$doc[name].__parameters__ = ___parameters___;\n$doc[name].__returns__ = ___returns___;\n$doc[name].__events__ = ___events___;";
				} else {
					//die("Vname: [".$variantname.']');
					$js .= <<<END
					
					if ( typeof($doc[name].__variants__) == 'undefined' ){
						$doc[name].__variants__ = [];
					}
					
					$doc[name].__variants__.push({
						name: $variantname,
						description: $variantdesc,
						__parameters__ : ___parameters___,
						__returns__ : ___returns___,
						__events__ : ___events___
					});
END;
				}
			} 
			
			if ( $isConstructor ){
				if ( preg_match('#@override-params (.*)@[a-z]{4,}#', $str2, $matches2)){
					$params = $matches2[1];
					if ( $params == 'any' ){
						$js .= <<<END
						
						for ( var i in $doc[name].__properties__ ){
							$doc[name].__parameters__.push({
								name: $doc[name].__properties__[i].name,
								type: \$.extend({}, $doc[name].__properties__[i].type),
								optional: true,
								description: $doc[name].__properties__[i].description
							});
						}
END;
					} else {
						$params = json_encode(array_map('trim', explode(',', $params)));
						$js .= <<<END
						
						\$.each($params, function(){
							$doc[name].__parameters__.push({
								name: $doc[name].__properties__[this].name,
								type: \$.extend({}, $doc[name].__properties__[this].type),
								optional: true,
								description: $doc[name].__properties__[this].description
							});
						});
									
END;
					}
				}
			}

			
			if ( preg_match_all('#@param(\W+(\d*))?(\W+optional)?(\W+\{([^\}]+)\})?(\W+(\w+))?( .+?)?(?=@[a-z]{4,}|\z)#s', $str2, $matches2, PREG_SET_ORDER)){
				//print_r($matches2);exit;
				foreach ($matches2 as $m){
					$m[5] = trim($m[5]);
					$param = array(
						'index'=> intval(trim($m[2])),
						'name'=> trim($m[6]),
						'description'=> trim($m[8]),
						'optional' => trim($m[3])?true:false,
						'typename' => preg_replace('/(.*?)([^ ]+)$/', '\\2', $m[5]),
						'isArray' => preg_match('/^array /', $m[5]),
						'isDictionary' => preg_match('/^dict /', $m[5])
						
					);
					$jsonparam = json_encode($param);
					//$doc['__parameters__'][] = $param;
					$constructor = $this->getConstructor(trim($param['typename']));
					
					$js .= <<<END
					
					\$.each([$jsonparam], function(){
						var type = {
							isArray: this.isArray,
							isDictionary: this.isDictionary,
							_constructor: $constructor,
							name: this.typename
						};
						this.type = type;
						___parameters___.push(this);
						if ( this.name ){
							___parameters___[this.name] = this;
						}
						
					});
END;
				
				
				
				}
			
			}
			
			if ( preg_match_all('#@event(\W+(\d*))?(\W+optional)?(\W+\{([^\}]+)\})?(\W+(\w+))?( .+?)?(?=@[a-z]{4,}|\z)#s', $str2, $matches2, PREG_SET_ORDER)){
				//print_r($matches2);exit;
				foreach ($matches2 as $m){
					$m[5] = trim($m[5]);
					$param = array(
						'index'=> intval(trim($m[2])),
						'name'=> trim($m[6]),
						'description'=> trim($m[8]),
						'optional' => trim($m[3])?true:false,
						'typename' => preg_replace('/(.*?)([^ ]+)$/', '\\2', $m[5]),
						'isArray' => preg_match('/^array /', $m[5]),
						'isDictionary' => preg_match('/^dict /', $m[5])
						
					);
					$jsonparam = json_encode($param);
					//$doc['__parameters__'][] = $param;
					$constructor = $this->getConstructor(trim($param['typename']));
					
					$js .= <<<END
					
					\$.each([$jsonparam], function(){
						var type = {
							isArray: this.isArray,
							isDictionary: this.isDictionary,
							_constructor: $constructor,
							name: this.typename
						};
						this.type = type;
						//___events___.push(this);
						if ( this.name ){
							___events___[this.name] = this;
						}
						
					});
END;
				
				
				
				}
			
			}
			
			
			if ( preg_match_all('#@option(\W+(\d*))?(\W+optional)?(\W+\{([^\}]+)\})?(\W+([a-zA-Z_$][0-9a-zA-Z_$\.]+))?( .+?)?(?=@[a-z]{4,}|\z)#s', $str2, $matches2, PREG_SET_ORDER)){
				//print_r($matches2);exit;
				foreach ($matches2 as $m){
					$m[5] = trim($m[5]);
					//die("Found option ".$m[6]);
					$optname = trim($m[6]);
					if ( strpos($optname, '.') === false ){
						continue;
					}
					
					list($pname, $optname) = explode('.', $optname);
					
					
					$param = array(
						'index'=> intval(trim($m[2])),
						'name'=> trim($m[6]),
						'optname' => $optname,
						'paramname' => $pname,
						'description'=> trim($m[8]),
						'optional' => trim($m[3])?true:false,
						'typename' => preg_replace('/(.*?)([^ ]+)$/', '\\2', $m[5]),
						'isArray' => preg_match('/^array /', $m[5]),
						'isDictionary' => preg_match('/^dict /', $m[5])
						
					);
					$jsonparam = json_encode($param);
					//$doc['__parameters__'][] = $param;
					$constructor = $this->getConstructor(trim($param['typename']));
					
					$js .= <<<END
					
					\$.each([$jsonparam], function(){
						var type = {
							isArray: this.isArray,
							isDictionary: this.isDictionary,
							_constructor: $constructor,
							name: this.typename
						};
						this.type = type;
						if ( typeof(___parameters___[this.paramname]) == 'undefined' ){
							___parameters___[this.paramname] = {
								name: this.paramname,
								description: '',
								optional: true,
								index: 0,
								isArray: false,
								isDictionary: true
							};
						}
						
						if ( typeof(___parameters___[this.paramname].options) == 'undefined' ){
							___parameters___[this.paramname].options = [];
						}
						
						___parameters___[this.paramname].options.push(this);
						if ( typeof(this.optname) != 'undefined' ){
							___parameters___[this.paramname].options[this.optname] = this;
						}
						
					});
END;
				
				
				
				}
			
			}
			
			
			if ( preg_match_all('#@returns(\W+(\w*))?( \{([^\}]+)\})\W*( .+?)?(?=@[a-z]{4,}|\z)#s', $str2, $matches2, PREG_SET_ORDER)){
				//print_r($matches2);exit;
				foreach ($matches2 as $m){
					
					$m[5] = trim($m[5]);
					$m[4] = trim($m[4]);
					$param = array(
						'name'=> $m[2],
						'description'=> $m[5],
						'typename' => preg_replace('/(.*?)([^ ]+)$/', '\\2', $m[4]),
						'isArray' => preg_match('/^array /', $m[4]),
						'isDictionary' => preg_match('/^dict /', $m[4])
						
					);
					$jsonparam = json_encode($param);
					//$doc['__parameters__'][] = $param;
					$constructor = $this->getConstructor(trim($param['typename']));
					
					$js .= <<<END
					
					\$.each([$jsonparam], function(){
						var type = {
							isArray: this.isArray,
							isDictionary: this.isDictionary,
							_constructor: $constructor,
							name: this.typename
						};
						this.type = type;
						___returns___.push(this);
						
					});
END;
				
				
				
				}
			
			}
			
			$js .= "\n})();\n";
			
			
		}
		
		
		
		
		
		
		return $js ."\nfunction $doc[name]";
		
		
	}
	
	
}
