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
import('Dataface/QuickForm.php');
/**
 * @ingroup formsAPI
 */
class Dataface_TranslationForm extends Dataface_QuickForm {
	
	var $sourceLanguage = null;	// The name of the source language (2-digit code)
	var $destinationLanguage = null; // The name of the destination language (2-digit code)
	var $records = array();		// map of language codes to records for that language.
	var $_dest_translatedFields;
	var $translatableLanguages = array();
	
	function getGoogleLanguage($lang){
		if ( $lang == 'zt' ) return 'zh-TW';
		else if ( $lang == 'zh' ) return 'zh-CN';
		else return $lang;
	}
	
	/**
	 * Creates a new translation form.
	 * @param $record Reference to Dataface_Record object to be translated.
	 * @param $source The 2-digit language code of the source language.
	 * @param $dest The 2-digit language code of the destination language.
	 * @param $fieldnames Optional array of fields that are to be translated.
	 *        By default all translatable fields will be translated.
	 */
	function Dataface_TranslationForm(&$record, $source=null, $dest=null, $query='', $fieldnames=null){
		$app =& Dataface_Application::getInstance();
		
		if ( is_string($record) ){
			// $record is just the name of a table.
			$table =& Dataface_Table::loadTable($record);
		} else {
			$table =& $record->_table;
		}
		
		$translations =& $table->getTranslations();
		foreach (array_keys($translations) as $trans){
			$table->getTranslation($trans);
		}
		//print_r($translations);
		if ( !isset($translations) || count($translations) < 2 ){
			// there are no translations to be made
			throw new Exception(
				df_translate(
					'scripts.Dataface.TranslationForm.ERROR_NO_TRANSLATIONS',
					'Attempt to translate a record in a table "'.$table->tablename.'" that contains no translations.',
					array('table'=>$table->tablename)
					)
				, E_USER_ERROR);
		}
		
		$this->translatableLanguages = array_keys($translations);
		
		$source = ( isset($source) ? $source : $this->translatableLanguages[0] );
		$dest = ( isset($dest) ? $dest : $this->translatableLanguages[1] );
		$i=0;
		while ( $dest == $source ){
			if ( $i>count($this->translatableLanguages)-1 ) throw new Exception("Failed to find an eligible language to translate to.");
			$dest = $this->translatableLanguages[$i++];
		}
		
		
		
		$this->sourceLanguage = $source;
		$this->destinationLanguage = $dest;
		
		$this->_dest_translatedFields = $translations[$this->destinationLanguage];
		

		
		$this->Dataface_QuickForm($record, '', $query, 'translation_form', false, $fieldnames, $this->destinationLanguage );
		$this->_renderer->elementTemplate = 'Dataface_TranslationForm_element.html';
		$this->_renderer->groupeElementTemplate = 'Dataface_TranslationForm_groupelement.html';
		
		$this->loadRecords(); // loads all of the translations from the database.
		
		
	}
	
	function loadRecords(){
		$keyMissing = true;
		if ( isset( $this->_record ) ){
			$keyMissing = false;
			foreach ( array_keys($this->_table->keys()) as $key ){
				if ( !$this->_record->val($key) ){
					// the current record is missing a primary key.
					// we need to reload the record.
					$keyMissing = true;
					break;
					
				}
			}
		}
		
		if ( $keyMissing ){
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.TranslationForm.ERROR_NO_RECORD_FOUND',
					"No record was found to be translated."
					), E_USER_ERROR);
		}
		
		// Now we want to load all of the translations for the current record for use on this
		// translation form.
		$query = array();
		foreach ( array_keys($this->_table->keys()) as $key ){
			$query[$key] = '='.$this->_record->strval($key);
		}
		$io = new Dataface_IO($this->_table->tablename);
		
		foreach ( $this->translatableLanguages as $lang ){
			$io->lang = $lang;
			$record = new Dataface_Record($this->_table->tablename, array());
			$io->read($query, $record);
			$this->records[$lang] =& $record;
			unset($record);
		}
		
		unset($this->_record);
		$this->_record =& $this->records[$this->destinationLanguage];
	
	}
	
	
	function _buildWidget($field){
		// notice that we pass $field by value here- so we can make changes without changing it
		// throughout the rest of the application.
		
		
		$res =& parent::_buildWidget($field);
		if ( is_a($res, 'HTML_QuickForm_element') and is_array($this->_dest_translatedFields) and !in_array( $field['name'], $this->_dest_translatedFields ) ){
			$res->freeze();
		}
		if ( $field['widget']['type'] != 'hidden' ){
			$res->setProperty('translation', $this->records[$this->sourceLanguage]->display($field['name']));
		}
		return $res;
		
	}
	
	function getFormTemplate(){
		$atts =& $this->_table->attributes();
		
		import('Dataface/TranslationTool.php');
		$tt = new Dataface_TranslationTool();
		
		$status_selector_html = $tt->getHTMLStatusSelector($this->_record, $this->destinationLanguage,'__translation__[status]');
		$trec =& $tt->getTranslationRecord($this->_record, $this->destinationLanguage);
		$strec =& $tt->getTranslationRecord($this->_record, $this->sourceLanguage);
		return "
				<form{attributes}>
					<fieldset>
					<legend>".$atts['label']."</legend>
					<table class=\"translation-form-table\">
					<thead>
						<tr>
							<th width=\"150\" class=\"translation-label-cell-header\"><!-- Field name--></th>
							<th width=\"325\" class=\"source-translation-cell-header\">".df_translate('scripts.Dataface.TranslationForm.LABEL_SOURCE_TRANSLATION','Source Translation')."</th>
							<th width=\"325\" class=\"destination-translation-cell-header\">".df_translate('scripts.Dataface.TranslationForm.LABEL_DESTINATION_TRANSLATION','Destination Translation')."</th>
						</tr>
					</thead>
					<tbody>
						<tr><th>".df_translate('scripts.Dataface.TranslationForm.LABEL_TRANSLATION_STATUS','Translation Status').":</th>
							<td>".df_translate('scripts.Dataface.TranslationForm.LABEL_VERSION','Version').": ".$strec->val('version')."</td>
							<td>$status_selector_html Version: ".$trec->val('version')."</td>
						</tr>
					{content}
					</tbody>
					</table>
					</fieldset>
				</form>";
	
	}
	
	function getGroupTemplate($name){
		$name = $this->_formatFieldName($name);
		$group =& $this->_table->getField($name);
		//print_r($group);
		//if ( isset($this->_fields[$name]['widget']['description'] )){
		//	$description = $this->_fields[$name]['widget']['description'];
		//} else {
		//	$description = '';
		//}
		$context = array( 'group'=>&$group, 'content'=>'{content}');
		$skinTool =& Dataface_SkinTool::getInstance();
		ob_start();
		$skinTool->display($context, 'Dataface_TranslationForm_group.html');
		$o = ob_get_contents();
		ob_end_clean();
		
		return $o;
	}
	
	function getFieldGroupTemplate($name){
		$name = $this->_formatFieldName($name);
		$group =& $this->_table->getFieldgroup($name);
		//print_r($group);
		//if ( isset($this->_fields[$name]['widget']['description'] )){
		//	$description = $this->_fields[$name]['widget']['description'];
		//} else {
		//	$description = '';
		//}
		
		$o = "<tr>
			<th>".$group['label']."</th>
			<td></td>
			<td>
			<table width=\"100%\" border=\"0\">
			{content}
			</table>
			</td>
			
			</tr>";
		return $o;
	}
	
	function save($values){
		$res = parent::save($values);
		
		import('Dataface/TranslationTool.php');
		$tt = new Dataface_TranslationTool();
		$tt->setTranslationStatus($this->_record, $this->destinationLanguage, $_POST['__translation__']['status']);
		return $res;
	}
	
	function display(){
		if ( $this->_resultSet->found()>0 || $this->_new ){
			$res = $this->_build();
			if ( PEAR::isError($res) ){
				return $res;
			}
			else {
				//$this->displayTabs();
				if ( !$this->_new and !Dataface_PermissionsTool::edit($this->_record) ){
					$this->freeze();
				}
				
				if ( $this->_new  and /*!Dataface_PermissionsTool::edit($this->_table)*/!Dataface_PermissionsTool::checkPermission('new',$this->_table) ){
					$this->freeze();
				}
				$this->accept($this->_renderer);
				//$formTool =& Dataface_FormTool::getInstance();
				
				
				if ( $this->_new || Dataface_PermissionsTool::view($this->_record) ){
					echo $this->_renderer->toHtml();
					//echo $formTool->display($this);
				} else {
					echo "<p>".df_translate('scripts.GLOBAL.INSUFFICIENT_PERMISSIONS_TO_VIEW_RECORD','Sorry you have insufficient permissions to view this record.')."</p>";
				}
				//parent::display();
			}
		} else {
			echo "<p>".df_translate('scripts.GLOBAL.NO_RECORDS_MATCHED_REQUEST','No records matched your request.')."</p>";
		}
	}
	
	

}
