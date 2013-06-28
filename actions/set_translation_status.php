<?php
import( 'HTML/QuickForm.php');
import('Dataface/TranslationTool.php');
import( 'I18Nv2/Language.php');
class dataface_actions_set_translation_status {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$this->table =& Dataface_Table::loadTable($query['-table']);
		
		
		
		$translations =& $this->table->getTranslations();
		foreach (array_keys($translations) as $trans){
			$this->table->getTranslation($trans);
		}
		//print_r($translations);
		if ( !isset($translations) || count($translations) < 2 ){
			// there are no translations to be made
			throw new Exception('Attempt to translate a record in a table "'.$this->table->tablename.'" that contains no translations.', E_USER_ERROR);
		}
		
		$this->translatableLanguages = array_keys($translations);
		$translatableLanguages =& $this->translatableLanguages;
		$this->languageCodes = new I18Nv2_Language($app->_conf['lang']);
		$languageCodes =& $this->languageCodes;
		$currentLanguage = $languageCodes->getName( $app->_conf['lang']);
		
		if ( count($translatableLanguages) < 2 ){
			return PEAR::raiseError(
				df_translate('Not enough languages to translate',
							'There aren\'t enough languages available to translate.'), DATAFACE_E_ERROR);
		
		}
		
		//$defaultSource = $translatableLanguages[0];
		//$defaultDest = $translatableLanguages[1];
		
		$options = array();
		foreach ($translatableLanguages as $lang){
			$options[$lang] = $languageCodes->getName($lang);
		}
		unset($options[$app->_conf['default_language']]);
		

		$tt = new Dataface_TranslationTool();
		
		
		$form = new HTML_QuickForm('StatusForm', 'POST');
		$form->addElement('select', '--language', 'Translation', $options);
		$form->addElement('select','--status', 'Status', $tt->translation_status_codes);
		//$form->setDefaults( array('-sourceLanguage'=>$defaultSource, '-destinationLanguage'=>$defaultDest));
		$form->addElement('submit','--set_status','Set Status');
		foreach ( $query as $key=>$value ){
			$form->addElement('hidden', $key);

			$form->setDefaults(array($key=>$value));

			
		}
		
		if ( $form->validate() ){
			$res = $form->process( array(&$this, 'processForm'));
			if ( PEAR::isError($res) ) return $res;
			else {
				$app->redirect($app->url('-action=list&-sourceLanguage=&-destinationLanguage=&-translate=').'&--msg='.urlencode('Translation status successfully set.'));
			}
		}
		
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		$records =& $this->getRecords();
		df_display(array('form'=>$out, 'translationTool'=>&$tt, 'records'=>&$records,'translations'=>&$options, 'context'=>&$this), 'Dataface_set_translation_status.html');
	}
	
	function printTranslationStatus(&$record, $language, &$translationTool){
		$trec =& $translationTool->getTranslationRecord($record, $language);
		
		return $translationTool->translation_status_codes[$trec->val('translation_status')];
	}
	
	
	function processForm($values){
		
		$records =& $this->getRecords();
		$tt = new Dataface_TranslationTool();
		foreach ($records as $record){
			$tt->setTranslationStatus($record, $values['--language'], $values['--status']);
		}	
		
		
	}
	
	function &getRecords(){
		$app =& Dataface_Application::getInstance();
		$query = $app->getQuery();
		$query['-skip'] = 0;
		$query['-limit'] = 500;
		$records =& df_get_records_array($query['-table'], $query);
		return $records;
	}
}

?>
