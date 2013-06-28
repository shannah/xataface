<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */

class Dataface_LanguageTool {
	
	public static function &getInstance($lang=null){
		if ( !isset($lang) ){
			$app =& Dataface_Application::getInstance();
			$lang = $app->_conf['lang'];
		}
		static $instance = 0;
		if ( !is_array($instance) ){
			$instance = array();
		}
		
		if ( !isset($instance[$lang]) ){
			$instance[$lang] = new Dataface_LanguageTool_Instance(array('lang'=>$lang));
		}
		return $instance[$lang];
	}
	
	
	
	
	/**
	 * Like addRealm except that it only adds the realm if the realm
	 * isn't already loaded.
	 */
	public static function loadRealm($name){
		return self::getInstance($this->app->_conf['default_language'])->loadRealm($name);
		
		
	}
	
	public static function addRealm($name, $dictionary=null){
		return self::getInstance()->addRealm($name, $dictionary);
		
	}
	
	public static function removeRealm($name){
		return self::getInstance()->removeRealm($name);
		
	}
	
	
	
	public static function _loadLangINIFile(/*$path*/){

		return self::getInstance()->_loadLangINIFile();

		
	}
	
	public static function translate($__translation_id, $__defaultText=null, $params=array(), $lang=null){
		return self::getInstance($lang)->translate($__translation_id, $__defaultText, $params, $lang);
	}
	
	/**
	 * Returns the HTML for a language selector.  This can be a list of flags, or
	 * names of languages, or a select list of names of languages.
	 *
	 * @param $params An associative array of parameters for this method.
	 *		Keys:
	 *			name : The name of the select widget or id of the ul (if unordered list)
	 *			var  : The GET variable that will be set by selecting one of these languages.
	 *			selected : The code of the language that is considered to be currently selected.
	 *			autosubmit : Whether the select list should auto submit
	 *			type	   : 'select' or 'ul'
	 *			lang	   : language code override
	 *			use_flags  : default true.
	 */
	public static function getLanguageSelectorHTML($params=array()){
		return self::getInstance()->getLanguageSelectorHTML($params);
		
	
	}
	
	
}
 

class Dataface_LanguageTool_Instance {
	/**
	 * Associative array of key/value pairs in the given language.
	 * @var array(string->string)
	 */
	var $dictionary;
	
	/**
	 * Reference to Dataface_Application object
	 * @var Dataface_Application
	 */
	var $app;
	
	/**
	 * Associative array of supplementary dictionaries to check
	 * before checking the main dictionary.
	 * @var array(string->dictionary)
	 */
	var $realms = array();
	
	/**
	 * 2-digit language code of the language that this
	 * language tool is set up for.
	 */
	var $lang = null;
	
	
	/**
	 * Constructor.  Takes optional configuration array as parameter.
	 *
	 * @example
	 * $lt = new Dataface_LanguageTool(array('lang'=>'zh'));
	 */
	function __construct($conf=null){
		if ( is_array($conf) and isset($conf['lang']) ) $this->lang = $conf['lang'];
		$this->_loadLangINIFile();
		$this->app =& Dataface_Application::getInstance();
		
		
	}
	
	/**
	 * Like addRealm except that it only adds the realm if the realm
	 * isn't already loaded.
	 */
	function loadRealm($name){
		if ( !isset($this->realms[$name]) ){
			$this->addRealm($name);
			if ( $this->lang != $this->app->_conf['default_language'] ){
				Dataface_LanguageTool::getInstance($this->app->_conf['default_language'])->loadRealm($name);
			}
		}
	}
	
	function addRealm($name, $dictionary=null){
		if ( !isset($dictionary) ){
			$lang  = $this->lang;
			if ( !$lang ) $lang = $this->app->_conf['lang'];
			if ( file_exists($name.'.'.$lang.'.ini') ){
				$dictionary = parse_ini_file($name.'.'.$lang.'.ini');
			} else {
				$dictionary = array();
			}
		}
		$this->realms[$name] =& $dictionary;
	}
	
	function removeRealm($name){
		unset($this->realms[$name]);
	}
	
	
	
	function _loadLangINIFile(/*$path*/){

		$app =& Dataface_Application::getInstance();
		$oldLang = $app->_conf['lang'];
		if ( isset($this->lang) ) $app->_conf['lang'] = $this->lang;
		$query =& $app->getQuery();
		import('Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$dictionary = $configTool->loadConfig('lang', null);
		if ( isset($query['-table']) ) {
			$tableDictionary = $configTool->loadConfig('lang', $query['-table']);
			if (is_array($tableDictionary) ){
				$dictionary = array_merge($dictionary, $configTool->loadConfig('lang',$query['-table']));
			}
		}
		$app->_conf['lang'] = $oldLang;
		$this->dictionary =& $dictionary;

		
	}
	
	function translate($__translation_id, $__defaultText=null, $params=array(), $lang=null){
		if ( isset($this) and is_a($this, 'Dataface_LanguageTool')  and $this->lang == $lang ) $tool =& $this;
		else $tool =& Dataface_LanguageTool::getInstance($lang);
		
		$__found_text = null;
		foreach ( array_reverse(array_keys($tool->realms)) as $realmName ){
			if ( isset($tool->realms[$realmName][$__translation_id]) ){
				$__found_text = $tool->realms[$realmName][$__translation_id];
				break;
			}
		}
		if ( !isset($__found_text) and isset($tool->dictionary[$__translation_id]) ){
			$__found_text = $tool->dictionary[$__translation_id];
		}
		if ( isset($__found_text) ) {
			// make sure that there are no conflicting variable names as we are about to extract the params 
			// array into local scope.
			if ( isset($params['__translation_id']) ) unset($params['__translation_id']);
			if ( isset($params['tool']) ) unset($params['tool']);
			if (isset($params['__defaultText']) ) unset($params['__defaultText']);
			if ( isset($params['params'])) unset($params['params']);
			if ( isset($params['__found_text']) ) unset($params['__found_text']);
			
			extract($params);
			@eval('$parsed = <<<END'."\n".$__found_text."\nEND\n;");
			if ( !isset($parsed) ){
				return  $__defaultText;
			}
			return $parsed;
		}
		
		if ( $tool->lang != $tool->app->_conf['default_language'] ){
			return $tool->translate(
				$__translation_id, $__defaultText, $params, $tool->app->_conf['default_language']
			);
		}
		
		return $__defaultText;
	}
	
	/**
	 * Returns the HTML for a language selector.  This can be a list of flags, or
	 * names of languages, or a select list of names of languages.
	 *
	 * @param $params An associative array of parameters for this method.
	 *		Keys:
	 *			name : The name of the select widget or id of the ul (if unordered list)
	 *			var  : The GET variable that will be set by selecting one of these languages.
	 *			selected : The code of the language that is considered to be currently selected.
	 *			autosubmit : Whether the select list should auto submit
	 *			type	   : 'select' or 'ul'
	 *			lang	   : language code override
	 *			use_flags  : default true.
	 */
	function getLanguageSelectorHTML($params=array()){
		if ( !isset($params['use_flags']) ) $params['use_flags'] = true;
		import('I18Nv2/Language.php');
		$langcode = ( isset($params['lang']) ? $params['lang'] : $this->app->_conf['lang']);
		$languageCodes = new I18Nv2_Language($langcode);
		$currentLanguage = $languageCodes->getName( $this->app->_conf['lang']);
		$name = (isset($params['name']) ? $params['name'] : 'language');
		$options = array();
		$var = (isset($params['var']) ? $params['var'] : '-lang');
		$selected = (isset($params['selected']) ? $params['selected'] : $this->app->_conf['lang']);
		$selectedValue = $languageCodes->getName($selected);
		$autosubmit = isset($params['autosubmit']) and $params['autosubmit'];
		$type = ( isset($params['type']) ? $params['type'] : 'select');
		
		if ( isset($params['table']) ){
			$table =& Dataface_Table::loadTable($params['table']);
			$languages = array_keys($table->getTranslations());
		} else {
			$languages = $this->app->_conf['languages'];
		}
		if ( !is_array($languages) ) return '';
		
		if ( $autosubmit) {
			$onchange = 'javascript:window.location=this.options[this.selectedIndex].value;';
			foreach ( $languages as $lang ){
				$curri18n = new I18Nv2_Language($langCode);
				$langname = $curri18n->getName($lang);
				$options[$this->app->url($var.'='.$lang)] = array('code'=>$lang, 'name'=>$langname);
			}
		} else {
			$onchange = '';
			foreach ($languages as $lang ){
				$curri18n = new I18Nv2_Language($langCode);
				$langname = $curri18n->getName($lang);
				$options[$lang] = array('code'=>$lang, 'name'=>$langname);
			}
		}

		if (count($options) <= 1) return '';
		ob_start();
		if ( $type == 'select' ){
		
			echo '<select name="'.$name.'" '.($onchange ? 'onchange="'.$onchange.'"' : '').'>
			';
			foreach ($options as $code => $value ){
				echo '<option value="'.$code.'"'. ( ($value['code'] == $selected) ? ' selected' : '').'>'.$value['name'].'</option>
				';
			}
			echo '</select>';
		} else {
			echo '<ul id="'.$name.'" class="language-selection-list">
			';
			foreach ( $languages as $code  ){
				if ( !isset($params['lang']) and $this->app->_conf['language_labels'][$code] != $code ){
					$languageName = $this->app->_conf['language_labels'][$code];
				} else {
					$languageName = $languageCodes->getName($code);
				}
				//$languageName = $languageCodes->getName($code);
				echo '<li class="language-selection-item '.( ($code == $this->app->_conf['lang']) ? ' selected-language' : '').'">
				<a href="'.$this->app->url($var.'='.$code).'">';
				if ( $params['use_flags'] ){
					echo '<img src="'.DATAFACE_URL.'/images/flags/'.$code.'_small.gif" alt="'.$languageName.'" />';
				} else {
					echo $languageName;
				}
				echo '</a></li>';
			}
			echo "</ul>";
		}
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
		
	
	}
	
	function getLanguageLabel($code){
		import('I18Nv2/Language.php');
		
		$langcode = $this->app->_conf['lang'];
		$languageCodes = new I18Nv2_Language($langcode);
		$languageName = null;
		if ( @$this->app->_conf['language_labels'][$code] and $this->app->_conf['language_labels'][$code] != $code ){
			$languageName = $this->app->_conf['language_labels'][$code];
		} else {
			//echo "Name : $code";
			$languageName = $languageCodes->getName($code);
		}
		return $languageName;

	}
	
	function getLanguageFlag($code){
		return DATAFACE_URL.'/images/flags/'.$code.'_small.gif';
	
	}
	
	
}
