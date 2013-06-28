<?php
class Dataface_SummaryList {
	var $records;
	var $table;
	function Dataface_SummaryList(&$records){
		$this->records =& $records;
		if ( count($this->records) > 0 ) $this->table =& $this->records[0]->_table;
	}
	
	function showSummary(&$record){
		$del =& $record->_table->getDelegate();
		if ( isset($del) and method_exists($del, 'showSummary') ){
			return $del->showSummary($record);
		}
		
		$app =& Dataface_Application::getInstance();
		$adel =& $app->getDelegate();
		if ( isset($adel) and method_exists($adel, 'showSummary') ){
			return $adel->showSummary($record);
		}
		
		// No custom summary defined.  We build our own.
		// See if there is an image of sorts.
		$logoField = $this->getLogoField($record);
		$out = '<div class="Dataface_SummaryList-record-summary">';
		if ( $logoField ){
			if ( isset($app->prefs['SummaryList_logo_width']) ) $width = $apps->prefs['SummaryList_logo_width'];
			else $width = '60';
			$out .= '<div class="Dataface_SummaryList-record-logo"><a href="'.$record->getURL('-action=view').'" title="Record details">
				<img src="'.$record->display($logoField).'" width="'.df_escape($width).'"/>
				</a>
				</div>';
		}
		
		$out .= '<table class="record-view-table">
					<tbody>';
		foreach ( $this->getSummaryColumns($record) as $fieldname){
			$field =& $record->_table->getField($fieldname);
			$out .= '
				<tr><th class="record-view-label">'.df_escape($field['widget']['label']).'</th><td class="record-view-value">'.$record->htmlValue($fieldname).'</td></tr>
			';
		}
		$out .= '		</tbody></table>';
		
		//$out .= '<h5 class="Dataface_SummaryList-record-title"><a href="'.$record->getURL('-action=view').'">'.df_escape($record->callDelegateFunction('summaryTitle',$record->getTitle())).'</a></h5>';
		//$out .= '<div class="Dataface_SummaryList-record-description">'.$record->callDelegateFunction('summaryDescription',$record->getDescription()).'</div>';
		//$out .= ( $record->getLastModified() + $record->getCreated() > 0 ? '<div class="Dataface_SummaryLIst-record-status">'.
		//	( $record->getLastModified() > 0 ? '<span class="Dataface_SummaryList-record-last-modified">
		//	'.df_translate('scripts.GLOBAL.LABEL_LAST_MODIFIED', 'Last updated '.df_offset(date('Y-m-d H:i:s',intval($record->getLastModified()))), array('last_mod'=>df_offset(date('Y-m-d H:i:s',intval($record->getLastModified()))))).'
		//	</span>' : '').
		//	( $record->getCreated() > 0 ? 
		//		'<span class="Dataface_SummaryList-record-created">'.df_translate('scripts.GLOBAL.LABEL_DATE_CREATED','Created '.df_offset(date('Y-m-d H:i:s',intval($record->getCreated()))), array('created'=>df_offset(date('Y-m-d H:i:s',intval($record->getCreated()))))).'</span>':''
		//	).'
		//	</div>': '').'
		$out .='	</div>';
		return $out;

	}
	
	function getSummaryColumns(&$record){
		$cols = array();
		$count= 0;
		foreach ($record->_table->fields(false,true) as $field){
			//print_r($field);
			if ( ( $record->_table->isContainer($field['name']) or
					$record->_table->isBlob($field['name']) or
					$field['widget']['type'] == 'htmlarea' or
					$record->_table->isPassword($field['name']) or
					$record->_table->isMetaField($field['name'])) and (@$field['visibility']['summary'] != 'visible')) continue;
			if ( @$field['visibility']['summary'] == 'hidden' ) continue;
			if ( @$field['visibility']['list'] == 'hidden' and @$field['visibility']['summary'] != 'visible' ) continue;
			//if ( (@$field['visibility']['summary'] == 'visible') or !isset($field['visibility']['summary']) ){
				$count++;
				$cols[] = $field['name'];
			//}
			if ( $count > 5 ) break;
		}
		return $cols;
		
		
	
	}
	
	function toHtml(){
		ob_start();
		df_display(array('records'=>&$this->records, 'list'=>&$this), 'Dataface_Summary_List.html');
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	function getLogoField(&$record){
		static $logoFields = 0;
		if ( $logoFields === 0 ) $logoFields = array();
		if ( !array_key_exists($record->_table->tablename, $logoFields) ){
			$found = false;
			foreach ( $record->_table->fields(false,true) as $field ){
			
				if ( ($record->isImage($field['name']) and @$field['logo'] !== 0 ) or @$field['logo'] ){
					$logoFields[$record->_table->tablename] = $field['name'];
					$found = true;
				}
				
			}
			foreach ( $record->_table->delegateFields(true) as $field ){
			
				if ( ($record->isImage($field['name']) and @$field['logo'] !== 0 ) or @$field['logo'] ){
					$logoFields[$record->_table->tablename] = $field['name'];
					$found = true;
				}
				
			}
			if ( !$found ){
				$logoFields[$record->_table->tablename] = null;
			}
			
		}
		return $logoFields[$record->_table->tablename];
		
	}

}
