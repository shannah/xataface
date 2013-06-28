<?php
import('Dataface/TranslationTool.php');
class dataface_actions_submit_translation {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$tt = new Dataface_TranslationTool();
		if ( !Dataface_Table::tableExists('dataface__translation_submissions',false) ){
			$tt->createTranslationSubmissionsTable();
			$app->redirect($app->url(''));
			
		}
		
		if ( !@$_POST['--submit']){

			df_display(array('query'=>$app->getQuery(), 'success'=>@$_REQUEST['--success']), 'Dataface_submit_translation.html');
			return;
		} else {

			if ( @$_POST['subject'] ){
				// This is a dummy field - possible hacking attempt
				$app->redirect($app->url('-action=list'));
				
			}
			if ( @$_POST['--recordid'] ){
				$record = df_get_record_by_id($_POST['--recordid']);
				$values = array(
					'record_id'=>@$_POST['--recordid'],
					'language'=>@$_POST['--language'],
					'url'=>@$_POST['--url'],
					'original_text'=>@$_POST['--original_text'],
					'translated_text'=>@$_POST['--translated_text'],
					'translated_by'=>@$_POST['--translated_by']);
				$trec = new Dataface_Record('dataface__translation_submissions', array());
				$trec->setValues($values);
				$trec->save();
				
				$email = <<<END
 The following translation was submitted to the web site {$app->url('')}:
 
 Translation for record {$record->getTitle()} which can be viewed at {$record->getURL('-action=view')}.
 This translation was submitted by {$_POST['--translated_by']} after viewing the content at {$_POST['--url']}.
 
 The original text that was being translated is as follows:
 
 {$_POST['--original_text']}
 
 The translation proposed by this person is as follows:
 
 {$_POST['--translated_text']}
 
 For more details about this translation, please visit {$trec->getURL('-action=view')}.
END;

				
				if ( @$app->_conf['admin_email'] ){
					mail($app->_conf['admin_email'],
						'New translation submitted',
						$email
					);
						
				}
				
				if ( @$_POST['--redirect'] || @$_POST['--url']){
					$url = @$_POST['--redirect'] ? $_POST['--redirect'] : $_POST['--url'];
					$app->redirect($url.'&--msg='.urlencode('Thank you for your submission.'));

				} else {
					$app->redirect($app->url('').'&--success=1&--msg='.urlencode('Thank you for your submission.'));

				}
			} else {
				throw new Exception("No record id was provided", E_USER_ERROR);
			
			}
		}
		
	
	}
}

?>
