<?php
//if ( !class_exists('Dataface_JavascriptTool')){
require_once 'Dataface/CSSTool.php';
/**
 * @brief A Class that manages, builds, and maintains dependencies of javacripts.
 */
class Dataface_JavascriptTool {

	/**
	 * Reference to the main instance of the JavascriptTool when run using the 
	 * Singleton design pattern.
	 */
	private static $instance=null;
	
	/**
	 * The associative array of include paths where it will search for scripts
	 * to compile or link.
	 * Key => Value is Filesystem path => Directory URL (throught the web)
	 */
	private $includePath = array();
	
	/**
	 * An associative array of scripts that have been explicitly imported
	 * to be compiled.  This doesn't include any scripts that are referenced
	 * included or required by those scripts.. only the first level.
	 * [Script Relative Path] => [Absolute file system path]
	 */
	private $scripts = array();
	
	/**
	 * An associative array of all scripts that have been included in this build
	 * (not via dependency, but actually compiled into this build).
	 */
	private $included = array();
	
	/**
	 * Reference to the CSSTool that is used for compiling CSS that is encountered
	 * and required during compilation.
	 */
	private $css = null;
	
	/**
	 * Whether to minify the output.
	 */
	private $minify = true;
	
	/**
	 * Whether to use the cache.
	 */
	private $useCache = true;
	
	/**
	 * An associative array of script dependencies.
	 */
	private $dependencies = array();
	
	/**
	 * An associative array of all script dependency contents (so we know what the
	 * dependent scripts have).
	 */
	private $dependencyContents = array();
	
	
	private $cssIncludes = array();
	
	
	/**
	 * An associative array of scripts that can be ignored.  Presumeably
	 * these have been pre-included.  This is handy if there is a large 
	 * library like jQuery that will be included in every page anyways
	 * so that we can tell the Javascript Tool to just ignore requests
	 * for these libraries.
	 */
	private $ignoreScripts = array();
	
	
	public function ignore($script){
		$this->ignoreScripts[$script] = 1;
		if ( isset($this->scripts[$script]) ) unset($this->scripts[$script]);
	}
	
	public function isIgnored($script){
		return @$this->ignoreScripts[$script];
	}
	
	public function ignoreCss($stylesheet){
		$this->css->ignore($stylesheet);
	}
	
	
	/**
	 * Returns a reference to the singleton instance.
	 * @returns Dataface_JavascriptTool
	 */
	public static function getInstance($type = null){
		if ( !isset($type) ){
			if ( isset(self::$instance) ){
				$type = get_class(self::$instance);
			} else {
				$type = 'Dataface_JavascriptTool';
			}
			
			
		}
		if ( !isset(self::$instance) or get_class(self::$instance) != $type ){
			self::$instance = new $type;
		}
		return self::$instance;
	}
	
	/**
	 * Constructor.
	 */
	public function __construct(){
		$this->css = new Dataface_CSSTool();
		
	}
	
	/**
	 * Sets whether to minify the output or not.
	 * @param boolean $minify
	 */
	public function setMinify($minify){
		$this->minify = $minify;
	}
	
	
	/**
	 * Gets whether to minify the output or not.
	 * @returns boolean
	 */
	public function getMinify(){
		return $this->minify;
	}
	
	/**
	 * @param boolean $cache
	 */
	public function setUseCache($cache){
		$this->useCache = $cache;
	}
	
	/** 
	 * @return boolean
	 */
	public function getUseCache(){
		return $this->useCache;
	}
	
	/**
	 * Merges the paths of the internal CSS tool with the paths of the 
	 * singleton CSS tool instance.
	 * @returns void
	 */
	public function mergeCSSPaths(){
		$css = Dataface_CSSTool::getInstance();
		foreach ($this->css->getPaths() as $k=>$v){
			$this->css->removePath($k);
		}
		foreach ($css->getPaths() as $k=>$v){
			$this->css->addPath($k, $v);
		}
	}
	
	/**
	 * Gets the scripts that are currently set to be compiled in this tool.
	 * This doesn't include all subscripts.
	 *
	 * @returns array Associative array of scripts [relative path] => [absolute path]
	 */
	public function getScripts(){
		return $this->scripts;
	}
	
	public function clearScripts(){
		foreach ($this->scripts as $k=>$v){
			unset($this->scripts[$k]);
		}
	}
	
	public function copyTo(Dataface_JavascriptTool $target){
		foreach ($this->includePath as $key=>$val){
			$target->addPath($key, $val);
		}
		
		foreach ($this->scripts as $key=>$val){
			$target->import($key);
		}
		$target->setUseCache($this->useCache);
		$target->setMinify($this->minify);
		
	}
	
	
	/**
	 * Adds a path to the list of include paths (where the tool looks for scripts).
	 * @param string $path The Filesystem path to the directory.
	 * @param string $url The corresponding URL to access this path.
	 */
	public function addPath($path, $url){
		$this->includePath[$path] = $url;
	}
	
	
	/**
	 * Removes a path from the list of include paths.
	 * @param string $path The file system path to remove.
	 */
	public function removePath($path){
		unset($this->includePath[$path]);
	}
	
	
	/**
	 * Returns the include paths as an associative array.
	 * 	[filesystem path] => [url]
	 */
	public function getPaths(){
		return $this->includePath;
	}
	
	public function clearPaths(){
		$this->includePath = array();
	}
	
	/**
	 * Adds a script to the list of top-level scripts to be compiled in this bundle.
	 * @param string $path The relative path to the script.
	 */
	public function import($path){
		if ( @$this->ignoreScripts[$path] ) return;
		$this->scripts[$path] = 1;
	}
	
	public function whereis($script){
		$out = array();
		foreach ($this->getPaths() as $path=>$url){
			if ( is_readable($path.DIRECTORY_SEPARATOR.$script) ){
				$out[] = $path.DIRECTORY_SEPARATOR.$script;
			}
		}
		
		return $out;
	}
	
	public function which($script){
		foreach ($this->getPaths() as $path=>$url){
			if ( is_readable($path.DIRECTORY_SEPARATOR.$script) ){
				return $path.DIRECTORY_SEPARATOR.$script;
			}
		}
		return null;
	}
	
	/**
	 * Returns the URL to the resulting script.  This precompiles
	 * the cached script if it isn't already compiled or is dirty.
	 * @returns string The URL to access the generated script.
	 */
	public function getURL(){
		$this->compile();
		return DATAFACE_SITE_HREF.'?-action=js&--id='.$this->generateCacheKeyForScripts(array_keys($this->scripts));
	}
	
	/**
	 * Returns the contents of the compiled script (loads it from the cached file).
	 */
	public function getContents(){
		$this->compile();
		return file_get_contents($this->getJavascriptCachePath(array_keys($this->scripts)));
	}
	
	public function getHtml(){
		$this->compile();
		$out = array();
		//print_r($this->dependencies);
		$clazz = get_class($this);
		$js = new $clazz;
		foreach ($this->dependencies as $script=>$path){
			$js->import($script);
			$out[] = sprintf('<script src="%s"></script>', df_escape($js->getURL()));
			$js->unimport($script);
		}
		$out[] = sprintf('<script src="%s"></script>', df_escape($this->getURL()));
		return implode("\r\n", $out);
	}
	
	public function unimport($script){
		unset($this->scripts[$script]);
	}
	
	/**
	 * Generates a hash key for the given array of scripts.
	 * @returns string The key.
	 */
	private function generateCacheKeyForScripts(){
		//$this->sortScripts();
		$scripts = array_keys($this->scripts);
		$base = basename($scripts[0]);
		$base = substr($base, 0, 10);
		return $base.'-'.md5(implode(PATH_SEPARATOR, $scripts));
	}
	
	/**
	 * Writes the javascript contents of the resulting script.
	 */
	private function writeJavascript($contents){
		$path = $this->getJavascriptCachePath();
		return file_put_contents($path, $contents, LOCK_EX);
	}
	
	/**
	 * Returns the cache path to the compiled javascript file.
	 */
	private function getJavascriptCachePath(){
		return DATAFACE_SITE_PATH.'/templates_c/'.$this->generateCacheKeyForScripts().'.js';
	}
	
	
	/**
	 * Gets the manifest path for the manifest file for these scripts.
	 */
	private function getManifestPath(){
		return DATAFACE_SITE_PATH.'/templates_c/'.$this->generateCacheKeyForScripts().'.manifest.js';
	}
	
	
	/**
	 * Gets the data contained in the manifest file for the given scripts.
	 * A manifest file contains 3 dictionaries:
	 *	1. included
	 *	2. dependencies - The list of script dependencies
	 *	3. depincluded - The list of all scripts that are included in the dependencies
	 */
	private function getManifestData(){
		$path = $this->getManifestPath();
		if ( is_readable($path) ){
			return json_decode(file_get_contents($path), true);
		} else {
			return array();
		}
	}
	
	/**
	 * Returns the data that is to be stored in the manifest file.
	 */
	private function prepareManifest(){
		
		return array(
			'included'=> $this->included,
			'dependencies' => $this->dependencies,
			'dependencyContents' => $this->dependencyContents,
			'cssIncludes' => $this->css->getIncluded()
		);
	}
	
	
	/**
	 * Writes the manifest file for the given set of scripts.
	 *
	 * @param array $scripts Array of string names of scripts whose
	 *		manifest we are writing.  The manifest should contain
	 *		such useful information as the scripts that are directly
	 *		included in the cached file, the scripts that this
	 *		cache file depends on directly (not included but referenced)
	 *		and a full list of all scripts that this script depends on
	 *		which is generated by recursively going through all dependencies
	 *		to build a full list of all dependent files.
	 */
	private function writeManifest(){
		$data = $this->prepareManifest();
		$path = $this->getManifestPath();
		return file_put_contents($path, json_encode($data), LOCK_EX);
	}
	
	
	/**
	 * Checks to see if the cached javascript file containing the 
	 * given scripts is out out of date and needs to be regenerated.
	 * It only for the actual contents of the javascript file and
	 * not for any dependent files.
	 */
	private function isCacheDirty(){
		$jspath = $this->getJavascriptCachePath();
		$manifest = $this->getManifestData();
		
		if ( !file_exists($jspath) ) return true;
		if ( !$manifest ) return true;
		if ( !$manifest['dependencyContents'] ) return true;
		$mtime = filemtime($jspath);
		
		$deps = $manifest['dependencyContents'];
		foreach ($deps as $script=>$file){
			$t = @filemtime($file);
			if ( !$t or  $t > $mtime ){
				return true;
			}
		}
		
		
		return false;
		
		
	}
	
	

	
	
	public function compile($clean=false){
		if ( !$this->useCache ) $clean = true;
		$scripts = array_keys($this->scripts);
		
		
		if ( $clean or $this->isCacheDirty() ){
			$this->included = array();
			$this->dependencies = array();
			$this->dependencyContents = array();
			$this->cssIncludes = array();
			
			$this->mergeCSSPaths();
			$contents = $this->_compile($scripts);
			
			
			
			$css = $this->css;
			if ( $css->getStylesheets() ){
				
				
				$contents = sprintf("\r\n".'(function(){
					var headtg = document.getElementsByTagName("head")[0];
					if ( !headtg ) return;
					var linktg = document.createElement("link");
					linktg.type = "text/css";
					linktg.rel = "stylesheet";
					linktg.href="%s";
					linktg.title="Styles";
					headtg.appendChild(linktg);
				})();', $css->getURL())
					
					.$contents;
				
			}
			
			
			
			$contents = "if ( typeof(window.console)=='undefined' ){window.console = {log: function(str){}};}if ( typeof(window.__xatajax_included__) != 'object' ){window.__xatajax_included__={};};"
				.$contents.'
				if ( typeof(XataJax) != "undefined"  ) XataJax.ready();
				';
				
			if ( $this->minify ) $contents = JSMin::minify($contents);
			$res = file_put_contents($this->getJavascriptCachePath(), $contents, LOCK_EX);
			if ( $res === false ){
				throw new Exception("JavascriptTool failed cache the request's javascript file.  Please check that your application has a templates_c directory and that it is writable.");
				
			}
			//$res = file_put_contents($this->getManifestPath(), json_encode(array_merge($this->included, $css->getIncluded())), LOCK_EX);
			$res = $this->writeManifest();
			if ( $res === false ){
				throw new Exception("JavascriptTool failed cache the request's manifest file.  Please check that your application has a templates_c directory and that it is writable.");
				
			}
		}
		
		
	}
	

	
	
	private function processDependency($script){
		if ( @$this->ignoreScripts[$script] ) return;
		$scriptPath = $this->which($script);
		if ( !$scriptPath ) throw new Exception(sprintf('Dependency "%s" could not be found in include path.', $script));
		$this->dependencies[$script] = $scriptPath;
		
		$clazz = get_class($this);
		$js = new $clazz;
		$js->clearPaths();
		foreach ($this->getPaths() as $k=>$v){
			$js->addPath($k,$v);
		}
		//echo "PRocessing dependency $script";
		$js->import($script);
		$js->compile();
		$data = $js->getManifestData();
		
		$contents = $data['dependencyContents'];
		foreach ($contents as $k=>$v){
			$this->dependencyContents[$k] = $v;
		}
		foreach ( $js->getScripts() as $k=>$v){
			$this->dependencies[$k] = $v;
		}
		foreach ($data['dependencies'] as $k=>$v){
			$this->dependencies[$k] = $v;
		}
		
	}
	
	protected function decorateContents($contents, $script){
		return $contents;
	}
	
	
	/**
	 * Compiles a set of javascripts specified byt the $scripts array.
	 * This respects the //!require and //!include directives
	 * which includes other javascript files inline.
	 *
	 * @param mixed $scripts  Either a string with a path to a script,
	 *		or an array of paths.
	 * @param array &$included An "out" array to keep track of 
	 * 		which scripts have been included.  This is an associative
	 *		array with key value pairs.  The key is the relative path
	 *		to the script within the include paths.  The value is
	 *		the path to the file on the file system.
	 *		
	 */
	protected function _compile($scripts, $passthru=false, $onceOnly=true){
		//$included = array();
		$out=array();
		if ( !is_array($scripts) ) $scripts = array($scripts);
		$included =& $this->dependencyContents;
		
		// Go through each script
		foreach ($scripts as $script){
			$contents = null;
			if ( $onceOnly and isset($included[$script]) or @$this->ignoreScripts[$script] ) continue;
			
			foreach ($this->includePath as $path=>$url){
				$filepath = $path.DIRECTORY_SEPARATOR.$script;
				//echo "\nChecking $filepath\n";
				if ( is_readable($filepath) ){
					$contents = file_get_contents($filepath);
					if ( !$passthru ){
						$contents = $this->decorateContents($contents, $script);

						if ( preg_match_all('#//load <(.*?)>#', $contents, $matches, PREG_SET_ORDER) ){

							foreach ($matches as $match){

								$this->processDependency($match[1]);
							}
						}
					}
					$included[$script] = $filepath;
					$this->included[$script] = $filepath;
					break;
				}
			}
			
			if ( !isset($contents) ) {
				throw new Exception(sprintf("Could not find script %s", $script));
			}
			
			if ( !$passthru ){
				try {
					$contents = preg_replace_callback('#//(require|include|require-css) <(.*)>#', array($this, '_importCallback'), $contents);
					$contents = preg_replace_callback('#@@\((.*?)\)#', array($this, '_includeStringCallback'), $contents);
				} catch (Exception $ex){
					//die('here');
					error_log($ex->getMessage());
					echo $ex->getMessage();
					throw new Exception(
						'Server-side Javascript directive failed in script "'.$script.'"',
						0
						
					);
					
				}
				$contents = "\r\n//START ".$script."\r\n"
					.sprintf("if ( typeof(window.__xatajax_included__['%s']) == 'undefined'){window.__xatajax_included__['%s'] = true;\r\n", addslashes($script), addslashes($script))
					.$contents."\r\n//END ".$script."\r\n"
					."\r\n}";
			}
			
			$out[] = $contents;
			
			
		}
		return implode("\r\n", $out);
	}
	
	public function _includeStringCallback($matches){
		return json_encode($this->_compile($matches[1], true, false));
	}
	
	public function _importCallback($matches){
		switch ($matches[1]){
			case 'require':
				if ( isset($this->dependencyContents[$matches[2]]) ){
					return '';
				}
				return "\r\n".$this->_compile($matches[2]);
				break;
			case 'include':
				return "\r\n".$this->_compile($matches[2]);
			case 'require-css':
				$css = $this->css;
				$css->import($matches[2]);
				
				return '';
			default:
				throw new Exception("Handling import callback but no valid directive found");
		}
	}
	
	public function clearCache(){
		$files = glob(DATAFACE_SITE_PATH.'/templates_c/*.js');
		foreach($files as $f){
			unlink($f);
		}
		$files = glob(DATAFACE_SITE_PATH.'/templates_c/*.manifest.js');
		foreach($files as $f){
			unlink($f);
		}
	}
	
	
}


/**
 * jsmin.php - PHP implementation of Douglas Crockford's JSMin.
 *
 * This is a direct port of jsmin.c to PHP with a few PHP performance tweaks and
 * modifications to preserve some comments (see below). Also, rather than using
 * stdin/stdout, JSMin::minify() accepts a string as input and returns another
 * string as output.
 * 
 * Comments containing IE conditional compilation are preserved, as are multi-line
 * comments that begin with "/*!" (for documentation purposes). In the latter case
 * newlines are inserted around the comment to enhance readability.
 *
 * PHP 5 or higher is required.
 *
 * Permission is hereby granted to use this version of the library under the
 * same terms as jsmin.c, which has the following license:
 *
 * --
 * Copyright (c) 2002 Douglas Crockford  (www.crockford.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * The Software shall be used for Good, not Evil.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * --
 *
 * @package JSMin
 * @author Ryan Grove <ryan@wonko.com> (PHP port)
 * @author Steve Clay <steve@mrclay.org> (modifications + cleanup)
 * @author Andrea Giammarchi <http://www.3site.eu> (spaceBeforeRegExp)
 * @copyright 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * @copyright 2008 Ryan Grove <ryan@wonko.com> (PHP port)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @link http://code.google.com/p/jsmin-php/
 */

class JSMin {
    const ORD_LF            = 10;
    const ORD_SPACE         = 32;
    const ACTION_KEEP_A     = 1;
    const ACTION_DELETE_A   = 2;
    const ACTION_DELETE_A_B = 3;
    
    protected $a           = "\n";
    protected $b           = '';
    protected $input       = '';
    protected $inputIndex  = 0;
    protected $inputLength = 0;
    protected $lookAhead   = null;
    protected $output      = '';
    
    /**
     * Minify Javascript
     *
     * @param string $js Javascript to be minified
     * @return string
     */
    public static function minify($js)
    {
        $jsmin = new JSMin($js);
        return $jsmin->min();
    }
    
    /**
     * Setup process
     */
    public function __construct($input)
    {
        $this->input       = str_replace("\r\n", "\n", $input);
        $this->inputLength = strlen($this->input);
    }
    
    /**
     * Perform minification, return result
     */
    public function min()
    {
        if ($this->output !== '') { // min already run
            return $this->output;
        }
        $this->action(self::ACTION_DELETE_A_B);
        
        while ($this->a !== null) {
            // determine next command
            $command = self::ACTION_KEEP_A; // default
            if ($this->a === ' ') {
                if (! $this->isAlphaNum($this->b)) {
                    $command = self::ACTION_DELETE_A;
                }
            } elseif ($this->a === "\n") {
                if ($this->b === ' ') {
                    $command = self::ACTION_DELETE_A_B;
                } elseif (false === strpos('{[(+-', $this->b) 
                          && ! $this->isAlphaNum($this->b)) {
                    $command = self::ACTION_DELETE_A;
                }
            } elseif (! $this->isAlphaNum($this->a)) {
                if ($this->b === ' '
                    || ($this->b === "\n" 
                        && (false === strpos('}])+-"\'', $this->a)))) {
                    $command = self::ACTION_DELETE_A_B;
                }
            }
            $this->action($command);
        }
        $this->output = trim($this->output);
        return $this->output;
    }
    
    /**
     * ACTION_KEEP_A = Output A. Copy B to A. Get the next B.
     * ACTION_DELETE_A = Copy B to A. Get the next B.
     * ACTION_DELETE_A_B = Get the next B.
     */
    protected function action($command)
    {
        switch ($command) {
            case self::ACTION_KEEP_A:
                $this->output .= $this->a;
                // fallthrough
            case self::ACTION_DELETE_A:
                $this->a = $this->b;
                if ($this->a === "'" || $this->a === '"') { // string literal
                    $str = $this->a; // in case needed for exception
                    while (true) {
                        $this->output .= $this->a;
                        $this->a       = $this->get();
                        if ($this->a === $this->b) { // end quote
                            break;
                        }
                        if (ord($this->a) <= self::ORD_LF) {
                            throw new JSMin_UnterminatedStringException(
                                'Unterminated String: ' . var_export($str, true));
                        }
                        $str .= $this->a;
                        if ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a       = $this->get();
                            $str .= $this->a;
                        }
                    }
                }
                // fallthrough
            case self::ACTION_DELETE_A_B:
                $this->b = $this->next();
                if ($this->b === '/' && $this->isRegexpLiteral()) { // RegExp literal
                    $this->output .= $this->a . $this->b;
                    $pattern = '/'; // in case needed for exception
                    while (true) {
                        $this->a = $this->get();
                        $pattern .= $this->a;
                        if ($this->a === '/') { // end pattern
                            break; // while (true)
                        } elseif ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a       = $this->get();
                            $pattern      .= $this->a;
                        } elseif (ord($this->a) <= self::ORD_LF) {
                            throw new JSMin_UnterminatedRegExpException(
                                'Unterminated RegExp: '. var_export($pattern, true));
                        }
                        $this->output .= $this->a;
                    }
                    $this->b = $this->next();
                }
            // end case ACTION_DELETE_A_B
        }
    }
    
    protected function isRegexpLiteral()
    {
        if (false !== strpos("\n{;(,=:[!&|?", $this->a)) { // we aren't dividing
            return true;
        }
        if (' ' === $this->a) {
            $length = strlen($this->output);
            if ($length < 2) { // weird edge case
                return true;
            }
            // you can't divide a keyword
            if (preg_match('/(?:case|else|in|return|typeof)$/', $this->output, $m)) {
                if ($this->output === $m[0]) { // odd but could happen
                    return true;
                }
                // make sure it's a keyword, not end of an identifier
                $charBeforeKeyword = substr($this->output, $length - strlen($m[0]) - 1, 1);
                if (! $this->isAlphaNum($charBeforeKeyword)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Get next char. Convert ctrl char to space.
     */
    protected function get()
    {
        $c = $this->lookAhead;
        $this->lookAhead = null;
        if ($c === null) {
            if ($this->inputIndex < $this->inputLength) {
                $c = $this->input[$this->inputIndex];
                $this->inputIndex += 1;
            } else {
                return null;
            }
        }
        if ($c === "\r" || $c === "\n") {
            return "\n";
        }
        if (ord($c) < self::ORD_SPACE) { // control char
            return ' ';
        }
        return $c;
    }
    
    /**
     * Get next char. If is ctrl character, translate to a space or newline.
     */
    protected function peek()
    {
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }
    
    /**
     * Is $c a letter, digit, underscore, dollar sign, escape, or non-ASCII?
     */
    protected function isAlphaNum($c)
    {
        return (preg_match('/^[0-9a-zA-Z_\\$\\\\]$/', $c) || ord($c) > 126);
    }
    
    protected function singleLineComment()
    {
        $comment = '';
        while (true) {
            $get = $this->get();
            $comment .= $get;
            if (ord($get) <= self::ORD_LF) { // EOL reached
                // if IE conditional comment
                if (preg_match('/^\\/@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                    return "/{$comment}";
                }
                return $get;
            }
        }
    }
    
    protected function multipleLineComment()
    {
        $this->get();
        $comment = '';
        while (true) {
            $get = $this->get();
            if ($get === '*') {
                if ($this->peek() === '/') { // end of comment reached
                    $this->get();
                    // if comment preserved by YUI Compressor
                    if (0 === strpos($comment, '!')) {
                        return "\n/*" . substr($comment, 1) . "*/\n";
                    }
                    // if IE conditional comment
                    if (preg_match('/^@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                        return "/*{$comment}*/";
                    }
                    return ' ';
                }
            } elseif ($get === null) {
                throw new JSMin_UnterminatedCommentException('Unterminated Comment: ' . var_export('/*' . $comment, true));
            }
            $comment .= $get;
        }
    }
    
    /**
     * Get the next character, skipping over comments.
     * Some comments may be preserved.
     */
    protected function next()
    {
        $get = $this->get();
        if ($get !== '/') {
            return $get;
        }
        switch ($this->peek()) {
            case '/': return $this->singleLineComment();
            case '*': return $this->multipleLineComment();
            default: return $get;
        }
    }
}

class JSMin_UnterminatedStringException extends Exception {}
class JSMin_UnterminatedCommentException extends Exception {}
class JSMin_UnterminatedRegExpException extends Exception {}

//}
