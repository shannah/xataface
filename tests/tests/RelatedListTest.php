<?php
require_once 'BaseTest.php';

require_once 'Dataface/RelatedList.php';

class RelatedListTest extends BaseTest {



	function RelatedListTest($name = "RelatedListTest"){
		$this->BaseTest($name);
	}
	
	function test_html(){
		
		$s =& $this->table1;
		$s->setValue('id',10);
		
		$rel_list = new Dataface_RelatedList('Profiles', 'addresses');
		
		$out = $rel_list->toHtml();
		echo "here";
		if ( PEAR::isError($out) ){
			
			echo $out->toString();
		}
		echo $out;
	
	}
	
	
	
	
	


}


?>
