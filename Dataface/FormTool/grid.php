<?php
//error_reporting(E_ALL);
//ini_set('display_errors','on');
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['grid'] = array('HTML/QuickForm/grid.php', 'HTML_QuickForm_grid');

/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_grid {
	
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		/*
		 *
		 * This field uses a table widget.
		 *
		 */
		//$field['display'] = 'block';
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		$factory =& Dataface_FormTool::factory();
		$widget =& $field['widget'];
		$el =& $factory->addElement('grid',$formFieldName, $widget['label']);
		$el->setProperties($widget);
		if ( !$record->checkPermission('delete related record', array('relationship'=>$field['relationship']))){
			$el->delete=false;
		}
		if ( !$record->checkPermission('add new related record', array('relationship'=>$field['relationship']))){
			//echo "No add new ".$record->_table->tablename;
			$el->addNew=false;
		}
		if ( isset($field['relationship']) ){
			$relationship =& $table->getRelationship($field['relationship']);
			
			if ( !$relationship->supportsAddNew() ){
				$el->addNew=false;
			}
			
			if ( !$relationship->supportsAddExisting() ){
				$el->addExisting=false;
			} else {
				$el->addExistingFilters = $relationship->getAddExistingFilters();
			}
			
			$el->table = $relationship->getDomainTable();
			if ( isset( $widget['columns'] ) ){
				$columns = array_map('trim',explode(',',$widget['columns']));
			} else {
				$columns = $relationship->_schema['short_columns'];
			}
			$count=0;
			$subfactory = new HTML_QuickForm();
			$dummyRelatedRecord = new Dataface_RelatedRecord($record, $relationship->getName());
			//print_r($dummyRelatedRecord->getPermissions());
			foreach ($columns as $column){
				
				$colTable =& $relationship->getTable($column);
				if ( !$colTable ) echo "Could not find table for column $column";
				
				$colPerms = $dummyRelatedRecord->getPermissions(array('field'=>$column));
				
				if ( !@$colPerms['view'] ){
					unset($colTable);
					unset($dummyRecord);
					continue;
				}
				
				// We need to be a bit more refined on this one.  We need to take
				// into account the context being that we are in a relationship.
				$dummyRecord = new Dataface_Record($colTable->tablename, $record->vals());
				/*
				if ( !$dummyRecord->checkPermission('view', 
					array('field'=>$column, 'recordmask'=>array('view'=>1))) ) {
					unset($colTable);
					unset($dummyRecord);
					continue;
				}
				*/
				$colFieldDef =& $colTable->getField($column);
				
				$columnElement =& $formTool->buildWidget($dummyRecord, $colFieldDef, $subfactory, $column, false);
				$defaultValue = $colTable->getDefaultValue($column);
				$columnElement->setValue($defaultValue);
				$el->defaults[$column] = $defaultValue;
				$el->addField($colFieldDef, $columnElement, $colPerms );
				
				$orderCol = $relationship->getOrderColumn();
				if ( PEAR::isError($orderCol) ){ $el->reorder=false;}
				
				unset($columnElement);
				unset($colFieldDef);
				unset($dummyRecord);
				unset($colTable);
				unset($elementFilter);
			}
			
		}

		else if ( isset($widget['fields']) ){
			$widget_fields =& $widget['fields'];
			foreach ($widget_fields as $widget_field){
				$widget_field =& Dataface_Table::getTableField($widget_field, $this->db);

				if ( PEAR::isError($widget_field) ){
					return $widget_field;
				}
				
				$widget_widget = $formTool->buildWidget($record, $widget_field, $factory, $widget_field['name']);
				$defaultValue = $table->getDefaultValue($widget_field['name']);
				
				$widget_widget->setValue($defaultValue);
				$el->addField($widget_widget);
				$el->defaults[$widget_field['name']] = $defaultValue;
			}
		} else if ( isset($field['fields']) ){
			foreach ( array_keys($field['fields']) as $field_key){
				$widget_widget = $formTool->buildWidget($record, $field['fields'][$field_key], $factory, $field['fields'][$field_key]['name']);
				$defaultValue = $table->getDefaultValue($widget_field['name']);
				
				$widget_widget->setValue($defaultValue);
				$el->defaults[$widget_field['name']] = $defaultValue;
				$el->addField($widget_widget);
				unset($widget_widget);
			
			}
		}

		return $el;
	}
	
	function pullValue(&$record, &$field, &$form, &$element, $new=false){
		$val = $record->getValue($field['name']);
		
		$filters = array();
		$subfactory = new HTML_QuickForm();
		foreach ($element->getColumnIds() as $colname){
			
			$colFieldDef = $element->getColumnFieldDef($colname);
			$columnElement = df_clone($element->getColumnElement($colname));
			
			
			
			// We need to be a bit more refined on this one.  We need to take
			// into account the context being that we are in a relationship.
			$dummyRecord = new Dataface_Record($colFieldDef['tablename'], array());
			
			
			//$colFieldDef = $colTable->getField($column);
			
			$filter = new Dataface_FormTool_grid_filter($dummyRecord, $colFieldDef, $subfactory, $columnElement, false);
	
			$filters[$colname] = $filter;
			unset($colFieldDef);
			unset($dummyRecord);
			unset($colFieldDef);
			unset($columnElement);
			
			
		}
		
		if ( !is_array($val) ){
			return $val;
		}
		
		foreach ($val as $key=>$row){
		
			if ( is_array($row) ){
				foreach ($row as $colname=>$colval){
					if ( isset($filters[$colname]) ){
						$val[$key][$colname] = $filters[$colname]->pullValue($colval);
					}
				}
			}
		}
		
		return $val;
	}
	
	function pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		$val = $element->getValue();
		
		
		
		
		$filters = array();
		$subfactory = new HTML_QuickForm();
		foreach ($element->getColumnIds() as $colname){
			
			$colFieldDef = $element->getColumnFieldDef($colname);
			$columnElement = df_clone($element->getColumnElement($colname));
			
			
			
			// We need to be a bit more refined on this one.  We need to take
			// into account the context being that we are in a relationship.
			$dummyRecord = new Dataface_Record($colFieldDef['tablename'], array());
			
			
			//$colFieldDef = $colTable->getField($column);
			
			$filter = new Dataface_FormTool_grid_filter($dummyRecord, $colFieldDef, $subfactory, $columnElement, false);
	
			$filters[$colname] = $filter;
			unset($colFieldDef);
			unset($dummyRecord);
			unset($colFieldDef);
			unset($columnElement);
			
			
		}
		
		$last_id=-1;
		foreach ( $val as $key=>$row ){
			if (is_array($row) and isset($row['__id__']) and ($row['__id__'] == 'new') ){
				$last_id = $key;
			}
			
			if ( is_array($row) ){
				foreach ($row as $colname=>$colval){
					if ( isset($filters[$colname]) ){
						$val[$key][$colname] = $filters[$colname]->pushValue($colval);
					}
				}
			}
			
		}
		
		if ( $last_id != -1 ) unset($val[$last_id]);
		
		return $val;
	}
	
	
	
}

class Dataface_FormTool_grid_filter {
	var $element;
	var $record;
	var $field;
	var $new;
	var $form;
	
	function __construct(&$record, &$field, &$form, &$element, $new=false){
		$this->element = $element;
		$this->record = $record;
		$this->field =& $field;
		$this->{'new'} = $new;
		$this->form =& $form;
		
	}
	
	function pullValue($val){
		$delegate = $this->record->_table->getDelegate();
		$this->record->setValue($this->field['name'], $val);
		$widgetHandler = Dataface_FormTool::getInstance()->getWidgetHandler($this->field['widget']['type']);
		$filterValue = true;
		if ( $delegate !== null and method_exists($delegate, $this->field['name']."__pullValue") ){
			/*
			 *
			 * The delegate defines a conversion method that should be used.
			 *
			 */
			 //echo "here";exit;
			$method = $this->field['name'].'__pullValue';
			$val = $delegate->$method($this->record, $this->element);
			$filterValue = false;
			
		} else if ( isset($widgetHandler) and method_exists($widgetHandler, 'pullValue') ){
			$val = $widgetHandler->pullValue($this->record, $this->field, $this->form, $this->element, $this->new);
			
		}
		if ( $filterValue ){
			$evt = new stdClass;
			$evt->record = $this->record;
			$evt->field =& $this->field;
			$evt->form = $this->form;
			$evt->{'new'} = $this->{'new'};
			$evt->element = $this->element;
			$evt->value = $val;
			Dataface_Application::getInstance()->fireEvent('FormTool::pullValue', $evt);
			$val = $evt->value;
		}
		
		return $val;
	}
	
	function pushValue($val){
		$this->element->setValue($val);
		$delegate = $this->record->_table->getDelegate();
		$widgetHandler = Dataface_FormTool::getInstance()->getWidgetHandler($this->field['widget']['type']);
		$filterValue=true;
		if ( $delegate !== null and method_exists($delegate, $this->field['name']."__pushValue") ){
			/*
			 *
			 * The delegate defines a conversion method that should be used.
			 *
			 */
			$method = $this->field['name'].'__pushValue';
			$val = $delegate->$method($this->record, $this->element);
			$filterValue = false;
			
		} else if ( isset($widgetHandler) and method_exists($widgetHandler, 'pushValue') ){
			$val = $widgetHandler->pushValue($this->record, $this->field, $this->form, $this->element, $this->new);
			
		}
		
		if ( $filterValue ){
			$evt = new stdClass;
			$evt->record = $this->record;
			$evt->field =& $this->field;
			$evt->form = $this->form;
			$evt->{'new'} = $this->{'new'};
			$evt->element = $this->element;
			$evt->metaValues = array();
			$evt->value = $val;
			Dataface_Application::getInstance()->fireEvent('FormTool::pushValue', $evt);
			$val = $evt->value;
		}
		return $val;
	}
}
