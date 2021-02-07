<?php
class dataface_actions_xf_grouped_list_content {
    function handle($params) {
		$app = Dataface_Application::getInstance();
		$records = $app->getRecords();
		$rows = [];
		$evenRow = false;

		foreach ($records as $rec) {
			if (!$rec->checkPermission('view')) {
				continue;
			}
			$title = $rec->getTitle();
			if (!$title) continue;
			$imageField = $rec->table()->getFieldWithTag('image,logo');
			$image = null;
			if ($imageField) {
				$image = $rec->display($imageField['name']);
			}
			
			$row = [
				'record' => $rec,
				'title' => $title,
				'recordId' => $rec->getId(),
				'detailsUrl' => $rec->getPublicLink(),
				'description' => $rec->getDescription(),
				'image' => $image
			];
			
			$rowClass = $evenRow ? 'even' : 'odd';
			$evenRow = !$evenRow;
			
			$recperms = $rec->getPermissions();
			
			$rowClass .= ' '.$this->getRowClass($rec);
			$status = $rec->getStatus();
            if ($status) {
                $rowClass .= ' xf-record-status-'.$status;
            }
			$row['class'] = $rowClass;
			$rows[] = $row;
			$evenRow = !$evenRow;
			
		}
		
        df_display(['rows' => $rows], 'xataface/actions/xf_grouped_list_content.html');
    }
	
 	function getRowClass(&$record){
 		$del =& $record->table()->getDelegate();
 		if ( isset($del) and method_exists($del, 'css__tableRowClass') ){
 			return $del->css__tableRowClass($record);
 		}
 		return '';
 	}
}
?>