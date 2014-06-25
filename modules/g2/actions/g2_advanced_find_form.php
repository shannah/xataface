<?php
class actions_g2_advanced_find_form {
	function handle($params){
		session_write_close();
		header('Connection: close');
		$app = Dataface_Application::getInstance();
		
		$query = $app->getQuery();
		$table = Dataface_Table::loadTable($query['-table']);
		
		$customPath = Dataface_Table::getBasePath($table->tablename);
		$findPath = $customPath.DIRECTORY_SEPARATOR.basename($table->tablename).DIRECTORY_SEPARATOR.'find.html';
		if ( file_exists($findPath) ){
			$html = file_get_contents($findPath);
		} else {
			$fields = array_keys($table->fields(false,true));
			$temp = $fields;
			foreach ($temp as $k=>$v){
				$fperms = $table->getPermissions(array('field'=>$v));
				if ( !@$fperms['find'] ){
					unset($fields[$k]);
				}
			}
				
			foreach ($table->relationships() as $relationship){
				if ( @$relationship->_schema['visibility'] and @$relationship->_schema['visibility']['find'] == 'hidden' ){
					continue;
				}
				$rperms = $table->getPermissions(array('relationship'=>$relationship->getName()));
				if ( !@$rperms['find'] ) continue;
				$rfields = $relationship->fields(true);
				$fkeys = $relationship->getForeignKeyValues();
				$removedKeys = array();
				foreach($fkeys as $fkeyTable => $fkey){
					foreach (array_keys($fkey) as $fkeyKey){
						$removedKeys[] = $fkeyTable.'.'.$fkeyKey;
					}
				}
	
				$rfields = array_diff($rfields, $removedKeys);
	
				
				foreach ($rfields as $rfield){
					list($rtable,$rfield) = explode('.',$rfield);
					//$rfperms = $table->getPermissions(array('relationship' => $relationship->getName(), 'field'=>$rfield));
					$rfperms = $relationship->getPermissions(array('field'=>$rfield));
					if ( @$rfperms['find'] ){
				
					
						$fields[] = $relationship->getName().'.'.$rfield;
					}
				}
				unset($rfields);
				unset($relationship);
				
			}
			
			$finalFields = array();
			foreach ($fields as $fieldname){
				$finalFields[$fieldname] =& $table->getField($fieldname);
				if ( @$finalFields[$fieldname]['visibility']['find'] == 'hidden' ){
					unset($finalFields[$fieldname]);
					continue;
				}
				$tbl = Dataface_Table::loadTable($finalFields[$fieldname]['tablename']);
				if ( $tbl->isDate($finalFields[$fieldname]['name']) ){
					$finalFields[$fieldname]['find']['type'] = 'date';
				}
			}
			
			$context['fields'] = array();
			$context['relatedFields'] = array();
			
			foreach ( $finalFields as $k=>$fld ){
				if ( strpos($k,'.') !== false ){
					list($rel,$fldname) = explode('.', $k);
					$relationship = $table->getRelationship($rel);
					$context['relatedFields'][$relationship->getLabel()][$k] = $fld;
					
				} else {
					$context['fields'][$k] = $fld;
				}
			}
			
			
			ob_start();
			df_display($context, 'xataface/modules/g2/advanced_find_form.html');
			$html = ob_get_contents();
			ob_end_clean();
		}
		
		$mod = Dataface_ModuleTool::getInstance()->loadModule('modules_g2');
		require_once 'modules/g2/inc/simple_html_dom.php';
		
		$dom = str_get_html($html);
		$els = $dom->find('select');
		foreach ($els as $el){
			
			$vocab = $el->{'data-xf-vocabulary'};
			if ( !$vocab ) continue;
			$options = array(''=>'', '='=>df_translate('g2_advanced_find_form.empty_list_label',"<Empty>"));
			
			$fieldTableName = $el->{'data-xf-table'};
			if ( $fieldTableName ){
				$fieldTable = Dataface_Table::loadTable($fieldTableName);
			} else {
				$fieldTable = null;
			}
			if ( !$fieldTable ){
				$fieldTable = $table;
			}
			$o2 = $fieldTable->getValuelist($vocab);
			if ( $o2 ){
				foreach ($o2 as $k=>$v){
					$options[$k] = $v;
				}
			}
			$opts = array();
			foreach ($options as $k=>$v){
				$opts[] = '<option value="'.htmlspecialchars($k).'">'.htmlspecialchars($v).'</option>';
				
			}
			$el->innertext = implode("\n", $opts);
			
		}
		
		
		header('Content-type: text/html; charset="'.$app->_conf['oe'].'"');
		$out = $dom->save();
		header('Content-length: '.strlen($out));
		echo $out;
		flush();
		
		
		
		
	}
}