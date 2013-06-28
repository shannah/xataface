<?php
class Dataface_XMLTool {

	var $ns="df";
	var $perms_ns = "dfp";
	var $atts_ns = "dfa";
	var $http_ns = "http";
	
	var $required_tables_loaded = array();
	
	/**
	 * Timestamps to mark the times for entities after which they would be considered
	 * changed.
	 * Contains keys: tables, valuelists, records
	 */
	var $ifModifiedSince=array();
	
	
	public static function &getOutputCache(){
		static $cache =0;
		if ( !is_object($cache) ){
			import('Dataface/OutputCache.php');
			$cache = new Dataface_OutputCache();
		}
		return $cache;
	}
	
	
	function setIfModifiedSince($type, $name, $timestamp){
		$this->ifModifiedSince[$type][$name] = $timestamp;
	}
	
	function getIfModifiedSince($type, $name){
		if ( isset($this->ifModifiedSince[$type][$name]) ) {
			return $this->ifModifiedSince[$type][$name];
		} else {
			return null;
		}
	}
	
	function isModified($type, $name, $timestamp){
		$since = $this->getIfModifiedSince($type, $name);
		if ( $since < $timestamp ){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Checks to see if the specified table has been modified since the 'IfLastModified'
	 * timestamp of the request.  If this returns true, then the table should be 
	 * output in the footers.
	 */
	function tableIsModified($name){
		$cache =& $this->getOutputCache();
		$time = $this->getIfModifiedSince('tables',$name);
		if ( $time == null ) return true;
		
		return $cache->isModified($time, $name);
	}
	
	function valuelistIsModified($table, $name){
		
	}
	
	
	function toXML(&$arg){
		$class = get_class($arg);
		if ( is_array($arg) ){
			return toXML_array($arg);
		}
		eval('$result = $this->toXML_'.$class.'($arg);');
		return $result;
	}
	
	function toXML_array($arg){
		trigger_error("Not implemented yet.", E_USER_ERROR);
	

	}

	function markRequiredTableLoaded($name){
	
		$this->required_tables_loaded[$name] = true;
	}
	
	function addRequiredTable($name){
		if ( !isset($this->required_tables_loaded[$name]) ) $this->required_tables_loaded[$name] = false;
	}
	
	function requiredTablesToXML(){
		$out = array();
		foreach ( $this->required_tables_loaded as $tablename=>$loaded){
			if (!$loaded){
				$t =& Dataface_Table::loadTable($tablename);
				$out[] = $this->toXML($t);
				unset($t);
			}
		}
		return implode("\n", $out);
	}
	
	function toXML_Dataface_Table(&$table){
		
		$ns = $this->ns;
		$pns = $this->perms_ns;
		$ans = $this->atts_ns;
		list($tablename, $tablelabel) = array_map(array(&$this,'xmlentities'), array($table->tablename, $table->getLabel()));
		$out = array();
		
		if ( !$this->tableIsModified($table->tablename) ){
			$out[] = "<$ns:table id=\"tables.{$tablename}\" name=\"$tablename\" http:status-code=\"304\" http:status-message=\"Not Modified\"></$ns:table>";
			
		} else {
			
			$out[] = <<<END
<$ns:table id="tables.{$tablename}" name="$tablename" label="$tablelabel">
END;
			$perms = $table->getPermissions();
			$patts = array();
			foreach ($perms as $pname=>$pval){
				$pname = str_replace(' ', '_', $pname);
				$patts[] = $pns.':'.$pname.'="'.$pval.'"';
			}
			$patts = implode(' ',$patts);
			$out[] = "\t<$ns:permissions $patts/>";
			foreach ( $table->fields() as $field ){
				$atts = array();
				foreach ($field as $key=>$val){
					if ( is_scalar($val) ){
						$atts[] = $ans.':'.$key.'="'.$this->xmlentities($val).'"';
					}
				}
				$atts = implode(' ', $atts);
				$out[] = "\t<$ns:field id=\"tables.{$tablename}.fields.{$field['name']}\" $atts>";
				
				$fperms = $table->getPermissions(array('field'=>$field['name']));
				$fpatts = array();
				foreach ($fperms as $fpkey=>$fpval){
					$fpkey = str_replace(' ', '_', $fpkey);
					$fpatts[] = $ans.':'.$fpkey.'="'.$fpval.'"';
				}
				$fpatts = implode(' ',$fpatts);
				$out[] = "\t\t<$ns:permissions $fpatts/>";
				
				$widget = $field['widget'];
				$watts = array();
				foreach ($widget as $wkey=>$wval){
					
					if ( is_scalar($wval) ){
						$watts[] = $ans.':'.$wkey.'="'.$this->xmlentities($wval).'"';
					}
					
				}
				$watts = implode(' ',$watts);
				$out[] = "\t\t<$ns:widget $watts>";
				if ( isset($widget['atts']) ){
					$aatts = array();
					foreach ($widget['atts'] as $akey=>$aval){
						if (is_scalar($aval) ){
							$aatts[] = $ans.':'.$akey.'="'.$this->xmlentities($aval).'"';
						}
						
						
					}
					$aatts = implode(' ',$aatts);
					$out[] = "\t\t\t<$ns:html_atts $aatts />";
				}
				$out[] = "\t\t</$ns:widget>";
				$out[] = "\t</$ns:field>";
					
			}
			
			foreach ($table->relationships() as $relationship){
				$out[] = $this->toXML_Dataface_Relationship($relationship);
			}
			$out[] = "</$ns:table>";
		}
		$this->markRequiredTableLoaded($table->tablename);
		return implode("\n", $out);
	}
	
	
	function toXML_Dataface_Relationship(&$relationship){
		$ns = $this->ns;
		$ans = $this->atts_ns;
		$out = array();
		$atts = array();
		$name = $this->xmlentities($relationship->getName());
		foreach ($relationship->_schema as $key=>$val){
			if ( is_scalar($val) ){
				$atts[] = $ans.':'.$key.'="'.$this->xmlentities($val).'"';
			}
		}
		$sourceTable =& $relationship->getSourceTable();
		$tablename = $sourceTable->tablename;
		$atts = implode(' ',$atts);
		$out[] = "\t<$ns:relationship id=\"tables.{$tablename}.relationships.{$name}\" name=\"$name\" $atts />";
		return implode("\n", $out);
	}
	
	function toXML_Dataface_Record(&$record){
	
		$ns = $this->ns;
		$ans = $this->atts_ns;
		$pns = $this->perms_ns;
		
		$out = array();
		$out[] = "\t\t<$ns:record id=\"".$this->xmlentities($record->getId())."\" table=\"".$record->_table->tablename."\">";
		
		$perms = $record->getPermissions();
		$patts = array();
		foreach ($perms as $pkey=>$pval){
			$pkey = str_replace(' ', '_', $pkey);
			$patts[] = "$pns:$pkey=\"".$this->xmlentities($pval)."\"";
		}
		$patts = implode(' ', $patts);
		$out[] = "\t\t\t<$ns:permissions $patts/>";
		
		foreach ( $record->_table->fields() as $field){
			$value = $record->val($field['name']);
			$dispVal = $record->display($field['name']);
			$this->addRequiredTable($field['table']);
				// Make sure that the table definition for this field is loaded.
			$out[] = "\t\t\t<$ns:record_value field=\"".$this->xmlentities($field['name'])."\">";
			$perms = $record->getPermissions(array('field'=>$field['name']));
			$patts = array();
			foreach ($perms as $pkey=>$pval){
				$pkey = str_replace(' ', '_', $pkey);
				$patts[] = "$pns:$pkey=\"".$this->xmlentities($pval)."\"";
			}
			$patts = implode(' ', $patts);
			$out[] = "\t\t\t\t<$ns:permissions $patts/>";
			if ( $record->_table->isDate($field['name']) ){
				$value= $dispVal;
			}
			
			if ( @$field['vocabulary'] ) $valuelist =& $record->_table->getValuelist($field['vocabulary']);
			
			if ( !is_array($value)  ) $value = array($value);

			foreach ($value as $vkey=>$vval){
				// $vkey will be the value that is actually stored in the database.
				// $vval Will be the resulting value after joins and valuelists are factored in.
				// We output both to save from having to publish the entire valuelist to the client.
				$vkey=$vval;	// Only fields that use vocabularies should have different key than value.
				if ( isset($valuelist) and isset($valuelist[$vval]) ){

					$vval = $valuelist[$vval];
				} 
				$out[] = "\t\t\t\t<$ns:value key=\"".$this->xmlentities($vkey)."\">".$this->xmlentities($vval)."</$ns:value>";
			}
			unset($valuelist);
			
			$out[] = "\t\t\t\t<$ns:display_value>".$this->xmlentities($dispVal)."</$ns:display_value>";
			$out[] = "\t\t\t\t<$ns:html_value>".$this->xmlentities( $record->htmlValue($field['name']))."</$ns:html_value>";
			$out[] = "\t\t\t</$ns:record_value>";
			
		
		}
		
		$out[] = "\t\t</$ns:record>";
		$out = implode("\n", $out);
		return $out;
		
		
	}
	
	function toXML_Dataface_QueryTool(&$tool){
		$ns = $this->ns;
		$ans = $this->atts_ns;
		$out = array();
		$tablename = $tool->_tablename;
		$tool->loadSet();
		
		
		
		$out[] = "<$ns:results source=\"".$this->xmlentities($tablename)."\" start=\"".$this->xmlentities($tool->start())."\" end=\"".$this->xmlentities($tool->end())."\" limit=\"".$this->xmlentities($tool->limit())."\" cursor=\"".$this->xmlentities($tool->cursor())."\" cardinality=\"".$this->xmlentities($tool->cardinality())."\" found=\"".$this->xmlentities($tool->found())."\" >";
		$table =& Dataface_Table::loadTable($tablename);
		foreach ($table->fields() as $field){
			if ( Dataface_PermissionsTool::checkPermission('view', $table, array('field'=>$field['name']))){
				$this->addRequiredTable($tablename);
				$out[] = "\t<$ns:column table=\"".$this->xmlentities($tablename)."\">".$this->xmlentities($field['name'])."</$ns:column>";
			}
		}
		
		$it =& $tool->iterator();
		while ($it->hasNext()){
			$nex =& $it->next();
			$out[] = $this->toXML_Dataface_Record($nex);
			unset($nex);
		}
		$out[] = "</$ns:results>";
		return implode("\n", $out);
	}
	
	function getInfo(){
		$ns = $this->ns;
		$app =& Dataface_Application::getInstance();
		
		$allowed_fields = array('_auth/auth_type'=>true);
		if ( isset($app->_conf["xml_public_info"]) ){
			$allowed_fields = array_merge($app->_conf['xml_public_info'], $allowed_fields);
		}
		$af = array();
		foreach ($allowed_fields as $key=>$value){
			$path = explode('/', $key);
			if ( count($path) == 1 ){
				$af[$key] = $value;
			} else {
				$af[$path[0]][$path[1]] = $value;
			}
			
		}
		
		$out = array();
		$out[] = "<$ns:config>";
		foreach ( $app->_conf as $key=>$value){
			if ( !@$af[$key] ) continue;
			$out[] = "\t<$ns:param><$ns:key>".$this->xmlentities($key)."</$ns:key>";
			if ( is_scalar($value) ){
				$out[] = "\t\t<$ns:value>".$this->xmlentities($value)."</$ns:value>";
			} else {
				$out[] = "\t\t<$ns:value>";
				foreach ($value as $vkey=>$vval){
					if ( !@$af[$key][$vkey] ) continue;
					if ( strcasecmp($vkey,'password') === 0 ) continue;
					$out[] = "\t\t\t<$ns:param><$ns:key>".$this->xmlentities($vkey)."</$ns:key><$ns:value>".$this->xmlentities($vval)."</$ns:value></$ns:param>";
				}
				$out[] = "\t\t</$ns:value>";
				
			}

			$out[] = "\t</$ns:param>";
		}
		$out[] = "</$ns:config>";
		return implode("\n", $out);
	}
	
	function header(){
		$ns = $this->ns;
		$ans = $this->atts_ns;
		$pns = $this->perms_ns;
		$out = array();
		$app =& Dataface_Application::getInstance();
		header('Content-type: text/xml; charset='.$app->_conf['oe']);
		$out[] = "<?xml version=\"1.0\"?>";
		$out[] = "<dataface_document xmlns:$ns=\"http://www.weblite.ca/dataface/2007/df\" xmlns:$ans=\"http://www.weblite.ca/dataface/2007/dfatts\" xmlns:$pns=\"http://www.weblite.ca/dataface/2007/dfperms\" xmlns:http=\"http://www.weblite.ca/dataface/2007/http\">";
		return implode("\n", $out);
	
	}
	
	function footer(){
		
		return $this->requiredTablesToXML()."\n</dataface_document>";
	}	
	
	function xmlentities($string) {
		return str_replace ( array ( '&', '"', "'", '<', '>', '' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), $string );
	}
}
