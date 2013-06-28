<?php
class Dataface_GlanceList {

	var $records;
	var $origRecords;
	function Dataface_GlanceList(array $records){
		$this->records = $records;
		$this->origRecords = array();
		foreach ( array_keys($this->records) as $key){
			$r = $this->records[$key];
			if ( is_a($this->records[$key], 'Dataface_RelatedRecord') ){
				$this->records[$key] = $this->records[$key]->toRecord();
			}
			$this->origRecords[$this->records[$key]->getId()] = $r;
		}
	
	}
	
	function toHtml(){
	
		ob_start();
		df_display(array('records'=>&$this->records, 'list'=>&$this), 'Dataface_GlanceList.html');
		$out  = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	function oneLineDescription(&$record){
		$del =& $record->_table->getDelegate();
		$origRecord = $this->origRecords[$record->getId()];
		if ( !$origRecord ) $origRecord = $record;
		
		if ( is_a($origRecord, 'Dataface_RelatedRecord') ){
			$origDel = $origRecord->_record->table()->getDelegate();
			$method = 'rel_'.$origRecord->_relationshipName.'__'.oneLineDescription;
			if ( isset($origDel) and method_exists($origDel, $method) ){
				return $del->$method($origRecord);
			}
		}
		if ( isset($del) and method_exists($del, 'oneLineDescription') ){
			return $del->oneLineDescription($record);
		}
		
		$app =& Dataface_Application::getInstance();
		$adel =& $app->getDelegate();
		if ( isset($adel) and method_exists($adel, 'oneLineDescription') ){
			return $adel->oneLineDescription($record);
		}
		$out = '<span class="Dataface_GlanceList-oneLineDescription">
			<span class="Dataface_GlanceList-oneLineDescription-title"><a href="'.df_escape($record->getURL('-action=view')).'" title="View this record">'.df_escape($origRecord->getTitle()).'</a></span> ';
		if ( $creator = $record->getCreator()  ){
			$show = true;
			if ( isset($app->prefs['hide_posted_by']) and $app->prefs['hide_posted_by'] ) $show = false;
			if ( isset($record->_table->_atts['__prefs__']['hide_posted_by']) and $record->_table->_atts['__prefs__']['hide_posted_by'] ) $show = false;
			if ( $show ){
				$out .=
				'<span class="Dataface_GlanceList-oneLineDescription-posted-by">Posted by '.df_escape($creator).'.</span> ';
			}
		}
		
		if ( $modified = $record->getLastModified() ){
			$show = true;
			if ( isset($app->prefs['hide_updated']) and $app->prefs['hide_updated'] ) $show = false;
			if ( isset($record->_table->_atts['__prefs__']['hide_updated']) and $record->_table->_atts['__prefs__']['hide_updated'] ) $show = false;
			if ( $show ){
				$out .= '<span class="Dataface_GlanceList-oneLineDescription-updated">Updated '.df_escape(df_offset(date('Y-m-d H:i:s', $modified))).'</span>';
			}
		}
		$out .= '
			</span>';
		return $out;
		
		
	}
}

