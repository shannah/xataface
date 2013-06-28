<?php

class Dataface_Installer {

	var $step = 'checkEnvironment';

	function install($params){
		$pipeline = array(
			'checkEnvironment',
			'installationForm',
			'installFiles',
			'installDB',
			'resultForm'
			);
			
		$res = null;
		foreach ($pipeline as $method){
			$res = $this->$method($params, $res);
			if ( PEAR::isError($res) ) return $res;
		}
		return $res;
		
	}
	
	function checkEnvironment($params){
		return true;
	
	}
	
	function installationForm($params, $env){
		//if ( $this->step != __FUNCTION__ ) return false;
	
		if ( isset($_POST['--process-installationForm']) ){
			$required = array('host','name','user');
			
		}
		
		
		df_display(
			array(
				'pipeline_vars'=>&$env, 
				'params'=>&$params, 
				'validation_result'=>&$res,
				'repository_apps'=>array()
				
				), 
			'installationForm.html');
		return;
		
		
		
	}
	function installDB($params){}
	function installFiles($params){}
	
	

}



?>
