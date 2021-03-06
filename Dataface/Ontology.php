<?php
/**
 * Dataface_Ontology is an abstract base class for classes that are meant
 * to sit on top of Records to allow them to be treated as abstract data types.
 *
 * an ontology is a data model that represents a set of concepts within a domain
 * and the relationships between those concepts. It is used to reason about the
 * objects within that domain.
 *
 * @see http://en.wikipedia.org/wiki/Ontology_%28computer_science%29
 */
class Dataface_Ontology {
	var $table;
	var $attributes;
	var $fieldnames;
	var $relationships;

	function __construct($tablename){
		$this->table =& Dataface_Table::loadTable($tablename);
	}
		function Dataface_Ontology($tablename) { self::__construct($tablename); }

	function &getAttributes(){
		if ( !isset($this->attributes) ){
			$this->buildAttributes();
		}
		return $this->attributes;
	}

	function &getAttribute($name){
		$atts =& $this->getAttributes();
		if ( !isset($atts[$name]) ){
			return PEAR::raiseError("No attribute '$name' exists in this Ontology.", DATAFACE_E_ERROR);
		}
		return $atts[$name];
	}

	function &newIndividual(&$record){
		$ind = new Dataface_Ontology_individual($this, $record);
		return $ind;
	}

	static function &newOntology($type, $tablename){
		$ontologies =& self::getRegisteredOntologies();
		if ( !isset($ontologies[$type]) ){
			return PEAR::raiseError("No ontology of type '$type' has been registered.", DATAFACE_E_ERROR);
		}
		if (!class_exists($ontologies[$type]['class'])) {
			import($ontologies[$type]['path']);
		}
		$class = $ontologies[$type]['class'];
		$ont = new $class($tablename);
		return $ont;
	}


	/**
	 * Tries to load an ontology by the type name.
	 * This will run the ApplicationDelegate's loadOntology($type)
	 * method if it is defined.  Then it will fire the 'loadOntology'
	 * event on the Application.  This gives both apps and modules
	 * opportunities to register ontologies.  If, after that, it still
	 * doesn't find the ontology, it will look in common places.  First it looks
	 * for a class named "ontologies_{$type}".  Then it looks for a class named
	 * "Dataface_Ontology_{$type}".  It will also check the corresponding locations
	 * on the file system for matching PHP file names.
	 * @param string $type The ontology name.
	 * @return boolean true if the ontology was found.
	 *
	 */
	public static function loadByName($type) {
		$curr =& self::getRegisteredOntologies();
		if (!@$curr[$type]) {
			$app = Dataface_Application::getInstance();
			$appDel = $app->getDelegate();
			if (isset($appDel) and method_exists($appDel, 'loadOntology')) {
				$appDel->loadOntology($type);
			}
			// Check if this loaded the ontology
			if (!@$curr[$type]) {
				$app->fireEvent('loadOntology', $type);
			}
			if (!@$curr[$type]) {
				// Still didn't find it.  Let's check to see if a "common"
				// class name is already loaded.
				$classNames = array('ontologies_'.basename($type), 'Dataface_Ontology_'.basename($type));
				foreach ($classNames as $className) {
					if (class_exists($className)) {
						self::registerType($type, null, $className);
						break;
					}
				}
			}

			if (!@$curr[$type]) {
				// Still didn't find it... let's check in common locations
				$paths = array(
					DATAFACE_SITE_PATH.'/ontologies/'.basename($type).'.php',
				 	DATAFACE_PATH.'/Dataface/Ontology/'.basename($type).'.php'
				);
				$classNames = array('ontologies_'.basename($type), 'Dataface_Ontology_'.basename($type));
				foreach ($paths as $key=>$path) {
					if (file_exists($path) and xf_is_readable($path)) {
						import($path);
						if (class_exists($classNames[$key])) {
							self::registerType($type, $path, $classNames[$key]);
							break;
						}
					}
				}
			 }
		}
		return isset($curr[$type]);
	}

	/**
	 * Registers an ontology so that it can be used.
	 * @param string $type The name of the ontology.  E.g. 'Person'
	 * @param string $path The path to the PHP file containing the ontology's class.
	 * @param string $class The class name of the ontology.
	 */
	public static function registerType($type, $path, $class){
		$ontologies =& self::getRegisteredOntologies();
		$ontologies[$type] = array('type'=>$type, 'path'=>$path, 'class'=>$class);
		return true;
	}

	/**
	 * Gets associative array of registered ontologies.
	 */
	public static function &getRegisteredOntologies(){
		static $ontologies = 0;
		if ( $ontologies === 0 ) $ontologies = array();
		return $ontologies;
	}

	function buildAttributes(){
		trigger_error("Please implement the ".__FUNCTION__." method", E_USER_ERROR);
	}

	function getFieldname($attname){
		if ( !isset($this->fieldnames) ){
			// If the fieldNames map hasn't been created yet, we need to
			// tell the subclass to create it.  We can do this by calling
			// getAttributes, which, in turn, calls buildAttributes()
			// which should build both the attributes array and the
			// fieldNames array
			$this->getAttributes();
		}

		if ( !isset($this->fieldnames) ){
			throw new Exception("The fieldnames array has not been set so there is a problem with this Ontology.  An ontology should populate the fieldNames array inside its buildAttributes() method.  If it does not, then there is a problem.", DATAFACE_E_ERROR);
		}
		return @$this->fieldnames[$attname];

	}

	/**
	 * A catch-all function that is used by isDate(), isBlob(), etc..
	 * to cut down on repetitive programming.
	 *
	 * @param string $method The name of the method to call.
	 * @param string $attname The name of the attribute that the method
	 * 		should act upon.
	 */
	function _is($method, $attname){
		return $this->table->$method(
			$this->getFieldname($attname)
			);
	}

	/**
	 * Validates a particular value to see if it is a valid value for that attribute.
	 * This will call the validate_$attname method of this ontology if it exists,
	 * otherwise it will just return true.
	 *
	 * @param string $attname The name of the attribute against which the value
	 *				 should be validated.
	 * @param mixed $value The value that is being validated.
	 * @param boolean $allowBlanks True if the validator should allow blank values.
	 * @returns boolean True if it is ok.. false otherwise.
	 */
	function validate($attname, $value, $allowBlanks=true){
		if ( method_exists($this, 'validate_'.$attname) ){
			$method = 'validate_'.$attname;
			return $this->$method($value, $allowBlanks);
		} else {
			if ( !$allowBlanks and !trim($value) ) return false;
			else return true;
		}
	}

	function getType($attname){ return $this->_is('getType', $attname);}
	function isDate($attname){ return $this->_is('isDate', $attname);}
	function isBlob($attname){ return $this->_is(__FUNCTION__, $attname);}
	function isContainer($attname){ return $this->_is(__FUNCTION__, $attname);}
	function isPassword($attname){ return $this->_is(__FUNCTION__, $attname);}
	function isText($attname){ return $this->_is(__FUNCTION__, $attname);}
	function isXML($attname){ return $this->_is(__FUNCTION__, $attname);}
	function isChar($attname){ return $this->_is(__FUNCTION__, $attname);}
	function isInt($attname){ return $this->_is(__FUNCTION__, $attname);}
	function isFloat($attname){ return $this->_is(__FUNCTION__, $attname);}


}

class Dataface_Ontology_individual {
	var $record;
	var $ontology;

	function __construct(&$ontology, &$record){
		$this->record =& $record;
		$this->ontology =& $ontology;
	}
		function Dataface_Ontology_individual(&$ontology, &$record) { self::__construct($ontology, $record); }

	function _get($method, $attname){
		return $this->record->$method(
			$this->ontology->getFieldname($attname)
			);
	}
	function getValue($attname){return $this->_get('getValue',$attname);}
	function val($attname){ return $this->getValue($attname);}

	function display($attname){ return $this->_get('display',$attname);}
	function q($attname){ return $this->_get('q', $attname);}
	function qq($attname){ return $this->_get('qq', $attname);}
	function strval($attname){ return $this->_get('strval', $attname);}
	function getValueAsString($attname){ return $this->_get('getValueAsString', $attname);}
	function htmlValue($attname){ return $this->_get('htmlValue', $attname);}
	function checkPermission($perm, $params=null) {
		if (is_array($params) and isset($params['field'])) {
			$params['field'] = $this->ontology->getFieldname($params['field']);
		}
		return $this->record->checkPermission(
			$perm,
			$params
		);
	}


}

?>
