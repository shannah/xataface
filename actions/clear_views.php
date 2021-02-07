<?php
error_reporting(E_ALL);
ini_set('display_errors','on');
class dataface_actions_clear_views {
    var $verbose = false;
	function handle(&$params){
        $this->verbose = true;
		$this->clear_views();
		echo "done";
	}
    
    function clear_views() {
		$res = xf_db_query("show tables like 'dataface__view_%'", df_db());
		$views = array();
		while ( $row = xf_db_fetch_row($res) ){
			$views[] = $row[0];
		}
		if ( $views ) {
			$sql = "drop view `".implode('`,`', $views)."`";
            if ($this->verbose) {
    			echo $sql;
    			echo "<br/>";
            }
			
			$res = xf_db_query("drop view `".implode('`,`', $views)."`", df_db());
			if ( !$res ) throw new Exception(xf_db_error(df_db()));
		}
    }

}
