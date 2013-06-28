<?php
import('Dataface/GlanceList.php');
class Dataface_RecordView {
	var $record;
	
	var $fieldgroups;
	var $sidebars;
	var $sections;
	var $logos;
	var $description;
	var $status;
	var $showLogo = false; // Whether or not to show the logo spot
	
	function Dataface_RecordView(&$record){

		$this->record =& $record;
		$tablename = $this->record->_table->tablename;
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$collapseAll = false;
		$expandAll = false;
		$collapsedSections = array();
		$expandedSections = array();
		if ( @$query['--collapse-all'] ) $collapseAll = true;
		if ( @$query['--expand-all'] ) $expandAll=true;
		if ( @$query['--collapsed-sections'] ) $collapsedSections = array_flip(explode(',', $query['--collapsed-sections']));
		if ( @$query['--expanded-sections'] ) $expandedSections = array_flip(explode(',', $query['--expanded-sections']));
		
		
		$fields = $this->record->_table->fields(false,true);

		// Now get defined sidebars in the delegate class.
		$this->sidebars = array();
		$this->sections = array();
		
		$this->description = $record->getDescription();
		if ( intval($record->getLastModified()) > 0 ){
			$this->status = sprintf(
                                df_translate('Last updated date', 'Last updated %s'),
                                df_offset(date('Y-m-d H:i:s',intval($record->getLastModified())))
                        );
		} else {
			$this->status = '';
		}
		
		import('Dataface/PreferencesTool.php');
		$pt =& Dataface_PreferencesTool::getInstance();

		$prefs =& $pt->getPreferences($record->getId());
		
		$delegate =& $this->record->_table->getDelegate();
		if ( isset($delegate) ){
			$delegate_methods = get_class_methods(get_class($delegate));
			$delegate_sidebars = preg_grep('/^sidebar__/', $delegate_methods);
			$delegate_fields = preg_grep('/^field__/', $delegate_methods);
			$delegate_sections = preg_grep('/^section__/', $delegate_methods);
			
			
			foreach ($delegate_fields as $dfield){
				$dfieldname = substr($dfield,7);
				$fields[$dfieldname] = $this->record->_table->_newSchema('varchar(32)', $dfieldname);
				$fields[$dfieldname]['visibility']['browse'] = 'hidden';
				if ( isset($this->record->_table->_atts[$dfieldname]) and 
					 is_array($this->record->_table->_atts[$dfieldname]) ){
					$this->record->_table->_parseINISection($this->record->_table->_atts[$dfieldname], $fields[$dfieldname]);
					
				}
				
				
			}
			
			foreach ( $delegate_sidebars as $sb ){
				$this->sidebars[] = $delegate->$sb($this->record);
			}
			
			foreach ( $delegate_sections as $sec ){
				$secobj = $delegate->$sec($this->record);
				if ( !isset($secobj['name']) ){
					$secobj['name'] = substr($sec,9);
				}	
				
				if ( isset($prefs['tables.'.$tablename.'.sections.'.$secobj['name'].'.order']) ){
					$secobj['order'] = intval($prefs['tables.'.$tablename.'.sections.'.$secobj['name'].'.order']);
				}
				
				
				if ( isset($prefs['tables.'.$tablename.'.sections.'.$secobj['name'].'.display']) ){
			
					$secobj['display'] = $prefs['tables.'.$tablename.'.sections.'.$secobj['name'].'.display'];
				} else {
					$secobj['display'] = 'expanded';
				}
				
				if ( $expandAll ) $secobj['display'] = 'expanded';
				if ( $collapseAll ) $secobj['display'] = 'collapsed';
				if ( isset($collapsedSections[$secobj['name']]) ) $secobj['display'] = 'collapsed';
				if ( isset($expandedSections[$secobj['name']]) ) $secobj['display'] = 'expanded';
				
				
				$this->sections[] =& $secobj;
				unset($secobj);
			}
			
		}
		
		
		
		// build the field groups
		$this->fieldgroups = array();
		$this->logos = array();

		
						  
		foreach ( $fields as $field ){
			
			if ( !$record->checkPermission('view', array('field'=>$field['name']))){
				continue;
			}
			if ( $record->_table->isMetaField($field['name']) ) continue;
			
			if ( !@$app->prefs['hide_record_view_logo'] ){
				if ( ($record->isImage($field['name']) and @$field['logo'] !== '0') or @$field['logo']) {
					$this->showLogo = true;
					
					if ( !isset($field['width']) ) {
						if ( isset($this->record->_table->_fields[$field['name']]) ){
							$this->record->_table->_fields[$field['name']]['width'] = 225;
						} else {
							$this->record->_table->_atts[$field['name']]['width'] = 225;
						}
							
					}
					$this->logos[] = $field;
					continue;
					
				} else if ( @$field['image'] ){
					$this->logos[] = $field;
					$this->showLogo = true;
				}
			}
			if ( $field['visibility']['browse'] == 'hidden' ) continue;
			
			if ( isset($field['viewgroup']) ){
				$group = $field['viewgroup'];
				
			} else if ( isset($field['group']) ){
				$group = $field['group'];
			} else {
				$group = '__main__';
			}
			if ( !isset($this->fieldgroups[$group]) ){
				$this->fieldgroups[$group][$field['name']] = $field;
				$fldgrp =& $this->record->_table->getFieldGroup($group);
				$class = 'main';
				if ( PEAR::isError($fldgrp) ){
					$label = ucwords(str_replace('_',' ',$group));
					if ( $group == '__main__' ){
						$label = df_translate('Dataface_RecordView_Details_Label',"Details");
						if ( @$app->prefs['RecordView.showLastModifiedOnDetails'] ){
							$label .= ' <span style="color: #666; font-weight: normal; text-style:italic"> - Last modified '.df_offset(date('Y-m-d',$this->record->getLastModified())).'</span>';
							
						}
						
					}
					$order = 0;
					$class = 'main';
				} else {
					if ( isset($fldgrp['condition']) and !$app->testCondition($fldgrp['condition'], array('record'=>&$this->record))){
						continue;
					}
					
					if ( isset($fldgrp['permission']) and !$record->checkPermission($fldgrp['permission']) ){
						continue;
					}
					$label = ucwords(str_replace('_',' ',$fldgrp['label']));
					if ( isset($fldgrp['section']['order']) ) $order = $fldgrp['section']['order'];
					else $order = 0;
					if ( isset($fldgrp['section']['class']) ) $class = $fldgrp['section']['class']; 
				}
				$sec = array(
					'name'=>$group.'__fieldgroup',
					'label'=>$label,
					'url'=>null,
					'content'=>null,
					'fields'=>&$this->fieldgroups[$group],
					'order'=> $order,
					'class'=>$class,
					'display'=>'expanded'
					);
				
				if ( isset($prefs['tables.'.$tablename.'.sections.'.$sec['name'].'.order']) ){
					$sec['order'] = intval($prefs['tables.'.$tablename.'.sections.'.$sec['name'].'.order']);
				}
				if ( isset($prefs['tables.'.$tablename.'.sections.'.$sec['name'].'.display']) ){
					$sec['display'] = $prefs['tables.'.$tablename.'.sections.'.$sec['name'].'.display'];
				}
				
				if ( $expandAll ) $sec['display'] = 'expanded';
				if ( $collapseAll ) $sec['display'] = 'collapsed';
				if ( isset($collapsedSections[$sec['name']]) ) $sec['display'] = 'collapsed';
				if ( isset($expandedSections[$sec['name']]) ) $sec['display'] = 'expanded';
				
				
				$this->sections[] =& $sec;
				unset($sec);	
					
				unset($fldgrp);
			} else {
				$this->fieldgroups[$group][$field['name']] = $field;
			}
			
				
		}
		if ( count($this->logos)>1 ){
			$theLogo = $this->logos[0];
			if ( !@$theLogo['logo'] ){
				foreach ($this->logos as $logofield){
					if ( @$logoField['logo'] ){
						$theLogo = $logoField;
						break;
					}
				}
			}
			$this->logos = array($theLogo);
		}
		
		if ( !@$app->prefs['hide_related_sections'] ){
			// Create the relationship sections
			foreach ( $this->record->_table->relationships() as $relname=>$relationship ){
				$schema =& $relationship->_schema;
				
				if ( isset($schema['section']['visible']) and !$schema['section']['visible'] ) continue;
				if ( isset($schema['section']['condition']) and !$app->testCondition($schema['section']['condition'], array('record'=>&$this->record,'relationship'=>&$relationship))){
					continue;
				}
				
				if ( isset($schema['action']['condition']) and !$app->testCondition($schema['action']['condition'], array('record'=>&$this->record,'relationship'=>&$relationship))){
					continue;
				}
				
				if ( isset($schema['action']['permission']) and !$record->checkPermission($schema['action']['permission']) ){
					continue;
				}
				
				if ( isset($schema['section']['permission']) and !$record->checkPermission($schema['section']['permission']) ){
					continue;	
				}
				
				if ( isset($schema['section']['label']) ) $label = $schema['section']['label'];
				else if ( isset($schema['action']['label'] )) $label = $schema['action']['label'];
				else $label = $relname;
				
				if ( isset($schema['section']['order']) ) $order = $schema['section']['order'];
				else if ( isset($schema['action']['order']) ) $order = $schema['action']['order'];
				else $order = 0;
				
				if ( isset($schema['section']['limit']) ) $limit = $schema['section']['limit'];
				else $limit = 5;
				
				if ( isset($schema['section']['sort']) ) $sort = $schema['section']['sort'];
				else $sort = 0;
				
				if ( isset($schema['section']['filter']) ) $filter = $schema['section']['filter'];
				else $filter = 0;
				
				$rrecords = $this->record->getRelatedRecordObjects($relname,0,$limit,$filter,$sort);
				if ( count($rrecords) == 0 ) continue;
				$glanceList = new Dataface_GlanceList($rrecords);
				
				if ( isset($schema['section']['class']) ) $class = $schema['section']['class'];
				else $class = 'left';
				
				
				$sec = array(
					'name'=>$relname.'__relationship',
					'label'=>$label, 
					'url'=>$this->record->getURL('-action=related_records_list&-relationship='.$relname), 
					'content'=>$glanceList->toHtml(),
					'order'=>$order,
					'class'=>$class,
					'display'=>'expanded'
				);
				
				if ( isset($prefs['tables.'.$tablename.'.sections.'.$sec['name'].'.order']) ){
					$sec['order'] = intval($prefs['tables.'.$tablename.'.sections.'.$sec['name'].'.order']);
				}
				
				if ( isset($prefs['tables.'.$tablename.'.sections.'.$sec['name'].'.display']) ){
					$sec['display'] = $prefs['tables.'.$tablename.'.sections.'.$sec['name'].'.display'];
				}
				
				if ( $expandAll ) $sec['display'] = 'expanded';
				if ( $collapseAll ) $sec['display'] = 'collapsed';
				if ( isset($collapsedSections[$sec['name']]) ) $secj['display'] = 'collapsed';
				if ( isset($expandedSections[$sec['name']]) ) $sec['display'] = 'expanded';
				
				
				$this->sections[] =& $sec;
				
				unset($sec);
				unset($schema);
				unset($relationship);
			
			}
		}
		

		usort($this->sections,array(&$this,'section_cmp'));
		
		
		
		
	}
	
	
	
	function section_cmp($a,$b){
		$ao = (isset($a['order']) ? $a['order'] : 0);
		$bo = (isset($b['order']) ? $b['order'] : 0 );
		return ($ao < $bo) ? -1:1;
	}
	
	
}
