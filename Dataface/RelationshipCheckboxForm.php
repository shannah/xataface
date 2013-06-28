<?php
import('HTML/QuickForm.php');

class Dataface_RelationshipCheckboxForm extends HTML_QuickForm {
	var $record;
	var $relationship;
	
	var $_isBuilt = false;

	function Dataface_RelationshipCheckboxForm(&$record, $relationshipName){
		$this->record =& $record;
		$this->relationship =& $record->_table->getRelationship($relationshipName);
		$this->HTML_QuickForm('Dataface_RelationshipCheckboxForm__'.$relationshipName, 'post');
		$this->build();
		
		if ( $this->validate() ){
			$this->process(array(&$this, 'save'), true);
		}
		$this->display();
		
	}
	
	function getCheckedRecordsDefaults(){
		// Now go through related records to see which boxes should be checked
		$rrecords =& $this->record->getRelatedRecordObjects($this->relationship->_name, 'all');
		$defaults = array();
		if ( count($rrecords) > 0 ){
			$refRecord =& $rrecords[0]->toRecord();
			$refTable =& $refRecord->_table;
			
			
			foreach ( $rrecords as $rrecord ){
				$keyvals = array();
				foreach ( array_keys($refTable->keys()) as $key ){
					$keyvals[] = urlencode($key).'='.urlencode($rrecord->strval($key));
				}
				$keystr = implode('&',$keyvals);
				$defaults[$keystr] = 1;
			}
			
			
		}
		return $defaults;
	}
	
	function build(){
		if ( $this->_isBuilt ) return;
		$this->isBuilt = true;
		
		$options = $this->relationship->getAddableValues($this->record);
		
		$boxes = array();
		foreach ($options as $opt_val=>$opt_text){
			if ( !$opt_val ) continue;
			$boxes[] =& HTML_QuickForm::createElement('checkbox',$opt_val , null, $opt_text, array('class'=>'relationship-checkbox-of-'.$opt_val.' '.@$options__classes[$opt_val]));
		}
		$el =& $this->addGroup($boxes, '--related-checkboxes', df_translate('scripts.Dataface_RelationshipCheckboxForm.LABEL_'.$this->relationship->_name.'_CHECKBOXES', 'Related '.$this->relationship->_name.' Records'));
		
		
		
		$defaults = $this->getCheckedRecordsDefaults();
		
		$this->setDefaults(array(
				'--related-checkboxes' => $defaults
				)
			);
		
		// Now let's add hidden fields for the keys of the current record
		// to the form.
		$factory = new HTML_QuickForm('factory');
			// a dummy quickform object to be used tgo create elements.
		$keyEls = array();
			// 
		$keyDefaults = array();
		foreach ( array_keys($this->record->_table->keys()) as $key ){
			$keyEls[] = $factory->addElement('hidden', $key);
			$keyDefaults[$key] = $this->record->strval($key);
			
		}
		$this->addGroup($keyEls,'--__keys__');
		$this->setConstants(array('--__keys__'=>$keyDefaults));
		
		// Now let's add a trail that will allow us to get back to here
		$app =& Dataface_Application::getInstance();
		$q =& $app->getQuery();
		$this->addElement('hidden','--query');
		if ( isset($_POST['--query']) ){
			$this->setDefaults(array('--query'=>$_POST['--query']));
		} else {
			$this->setDefaults(array('--query'=>$app->url('')));
		}
		
		$this->addElement('hidden','-table');
		$this->addElement('hidden','-action');
		$this->addElement('hidden','-relationship');
		$this->setDefaults(array('-table'=>$q['-table'], '-action'=>$q['-action'], '-relationship'=>$q['-relationship']));
		
		$this->addElement('submit','save',df_translate('scripts.Dataface_RelationshipCheckboxForm.LABEL_SUBMIT', 'Save'));
		
	
	}
	
	function save($values){
	
		// Which ones were checked
		$checked = array_keys($values['--related-checkboxes']);
		
		// Which ones are currently part of the relationship
		$default = array_keys($this->getCheckedRecordsDefaults());
		
		// Which ones need to be added?
		$toAdd = array_diff($checked, $default);
		
		// Which ones need to be removed?
		$toRemove = array_diff($default, $checked);
		
		
		// Now we go through and remove the ones that need to be removed.
		$io = new Dataface_IO($this->record->_table->tablename);
		$messages = array();
		$successfulRemovals = 0;
		foreach ( $toRemove as $id ){
			$res = $io->removeRelatedRecord($this->id2record($id));
			if ( PEAR::isError($res) ) $messages[] = $res->getMessage();
			else $sucessfulRemovals++;
			
		}
		
		// Now we go through and add the ones that need to be added.
		foreach ( $toAdd as $id ){
			$res = $io->addExistingRelatedRecord($this->id2record($id));
			if ( PEAR::isError($res) ) $messages[] = $res->getMessage();
			else $successfulAdditions++;
		}
		
		array_unshift($messages, 
			df_translate('scripts.Dataface_RelationshipCheckboxForm.MESSAGE_NUM_RECORDS_ADDED',
				$successfulAdditions.' records were successfully added to the relationship.',
				array('num_added'=>$successfulAdditions)
				),
			df_translate('scripts.Dataface_RelationshipCheckboxForm.MESSAGE_NUM_RECORDS_REMOVED',
				$successfulRemovals.' records were successfully removed from the relationship.',
				array('num_removed'=>$successfulRemovals)
				)
			);
		$_SESSION['msg'] = '<ul><li>'.implode('</li><li>', $messages).'</li></ul>';
		$url = $values['--query'];
		$urlparts = parse_url($url);
		if ( $urlparts and $urlparts['host'] and $urlparts['host'] != $_SERVER['HTTP_HOST'] ){
			throw new Exception('Failed to redirect after action due to an invalid query parameter.', E_USER_ERROR);
			
		}
		$app->redirect($values['--query']);

		
		
		
	}
	
	function id2record($idstring){
		
		$pairs = explode('&',$idstring);
		foreach ($pairs as $pair){
			list($attname, $attval) = explode('=',$pair);
			$attname = urldecode($attname);
			$attval = urldecode($attval);
			$colVals[$attname] = $attval;
		}
		
		$rrecord = new Dataface_RelatedRecord($this->record, $this->relationship->_name);
		$rrecord->setValues($colVals);
		return $rrecord;
		
		
	}
	
}
