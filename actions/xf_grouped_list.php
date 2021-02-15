<?php
class dataface_actions_xf_grouped_list {
	function handle($params) {
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		$tableName = $query['-table'];
		$table = Dataface_Table::loadTable($tableName);
		if (PEAR::isError($table)) {
			throw new Exception($table->getMessage());
		}
		
		$groupBy = @$query['-group-by'];
		if (!$groupBy) {
			$f = $table->getFieldWithTag('group.by');
			if ($f) {
				$groupBy = $f['name'];
			}
		}
		if (!$groupBy) {
			
			$f = $table->getFieldWithTag('filter');
			if ($f) {
				$groupBy = $f['name'];
			}
		}
		
		if (!$groupBy) {
			// No fields found to group by.  Just show list view.
			$url = $app->url('-action=list');
			$app->redirect($url);
			exit;
		}
		$groupByField = $table->getField($groupBy);
		if (!$groupByField) {
			throw new Exception('Field '.$groupBy.' not found');
		}
		
		$groupByFields = [$groupByField['name']];
		if (@$groupByField['displayField']) {
			$groupByFields[] = $groupByField['displayField'];
		}
		import(XFROOT.'Dataface/QueryBuilder.php');
		$qb = new Dataface_QueryBuilder($tableName, $query);
		
		$sql = $qb->select_groups($groupByFields);
		$res = xf_db_query($sql, df_db());
		$rows = [];
		while ($row = xf_db_fetch_assoc($res)) {
			$row['contentUrl'] = $app->url('-limit=30&-skip=0&-action=xf_grouped_list_content&'.urlencode($groupBy).'='.urlencode($row[$groupBy]));
			$row['allUrl'] = $app->url('-action=list&'.urlencode($groupBy).'='.urlencode($row[$groupBy]));
			$row['key'] = $row[$groupBy];
			$rows[] = $row;
		}
		xf_db_free_result($res);
		$vocabulary = [];
		if (@$groupByField['vocabulary']) {
			$vocabulary = $table->getValuelist($groupByField['vocabulary']);
			foreach ($rows as $k=>$row) {
				$label = @$vocabulary[$row[$groupBy]];
				if (!$label and @$groupByField['displayField']) {
					$label = $row[$groupFieldField['displayField']];
				}
				if (!$label) $label = $row[$groupBy];
				if (!$label) {
					unset($rows[$k]);
					continue;
				}
				$rows[$k]['label'] = $label;
				
			}
		} else {
			foreach ($rows as $k=>$row) {
				if (@$groupByField['displayField']) {
					$rows[$k]['label'] = $row[$groupByField['displayField']];
				} else {
					$rows[$k]['label'] = $row[$groupBy];
				}
				
			}
		}
		
		xf_script('xataface/actions/xf_grouped_list.js');
		df_display(['rows' => $rows], 'xataface/actions/xf_grouped_list.html');
		
		
		
	}
}
?>