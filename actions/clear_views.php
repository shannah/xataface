<?php
error_reporting(E_ALL);
ini_set('display_errors','on');
class dataface_actions_clear_views {
	function handle(&$params){
		$res = mysql_query("show tables like 'dataface__view_%'", df_db());
		$views = array();
		while ( $row = mysql_fetch_row($res) ){
			$views[] = $row[0];
		}
		if ( $views ) {
			$sql = "drop view `".implode('`,`', $views)."`";
			echo $sql;
			echo "<br/>";
			$res = mysql_query("drop view `".implode('`,`', $views)."`", df_db());
			if ( !$res ) throw new Exception(mysql_error(df_db()));
		}
		echo "done";
	}

}
