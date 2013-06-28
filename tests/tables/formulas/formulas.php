<?php
class tables_formulas {

	var $testPermissions = false;
	
	function getPermissions($record){
		if ( $this->testPermissions ){
			return array('view'=>1, 'new'=>1, 'edit'=>1, 'list'=>0, 'link'=>0, 'delete'=>1);
		}
		return null;
	}
	
	function __field__permissions($record){
		if ( !$this->testPermissions ) return null;
		return array('edit'=>0, 'new'=>0, 'view'=>0, 'copy'=>1);
	}
	
	
	function formula_name__permissions($record){
		if ( !$this->testPermissions ) return null;
		return array('view'=>1, 'update'=>1);
	}
	
	function rel_ingredients__amount__permissions($record){
		if ( !$this->testPermissions ) return null;
		return array('view'=>1);
	}
	
	function rel_ingredients__concentration_units__permissions($record){
		if ( !$this->testPermissions ) return null;
		return array('view'=>0);
	}
	
	function rel_ingredients__permissions($record){
		if ( !$this->testPermissions ) return null;
		return array('link'=>1);
	}


	function afterCopy(Dataface_Record $orig, Dataface_Record $copy){
		$rand = md5(rand(0,1000000));
		$copytable = 'copy_'.$rand;
		$res = mysql_query("create temporary table `$copytable` select * from formula_ingredients where formula_id='".addslashes($orig->val('formula_id'))."'", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		$res = mysql_query("update `$copytable` set formula_id='".addslashes($copy->val('formula_id'))."'", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		$res = mysql_query("insert into formula_ingredients select * from `$copytable`", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		$res = mysql_query("drop table `$copytable`", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
	}
	
	
	function buildRelatedSection($record, $relationship){
    	import('Dataface/RelatedList.php');
    	$relatedList = new Dataface_RelatedList($record, $relationship);
		$relatedList->noLinks = true;
		$relatedList->hideActions = true;
		$content = $relatedList->toHtml();
    	
    	
    	$rel = $record->table()->getRelationship($relationship);
    	
    	
    	return array(
    		'class'=>'main',
    		'content'=>$content,
    		'label'=>$rel->getLabel(),
    		'order'=>10
    	);
    
    }
    
    
    
   
    
    function section__ingredients($record){
    	return $this->buildRelatedSection($record, 'ingredients');
    }
}
