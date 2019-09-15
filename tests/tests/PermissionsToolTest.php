<?php
require_once 'BaseTest.php';
require_once 'Dataface/PermissionsTool.php';

class PermissionsToolTest extends BaseTest {
	function PermissionsToolTest($name='PermissionsToolTest'){
		$this->BaseTest($name);
	}
	
	
	function test_static_vars(){
		$pt =& Dataface_PermissionsTool::getInstance();
		$read_only = $pt->READ_ONLY();
		$this->assertEquals(
			array(1,1,1,0),
			array($read_only['view'], $read_only['show all'], $read_only['find'], $read_only['edit']) 
		
		);
		
		$edit = $pt->READ_EDIT();
		$this->assertEquals(
			array(1,1,1,1,0),
			array($edit['view'],$edit['show all'], $edit['find'], $edit['edit'], $edit['delete']) 
		);
		
		$all = $pt->ALL();
		$this->assertEquals(
			array(1,1,1,1,1),
			array($all['view'],$all['show all'], $all['find'], $all['edit'], $all['delete'])
		);
		
	
	}
	
	
	function test_basic_check_array(){
		$pt =& Dataface_PermissionsTool::getInstance();
		
		$this->assertTrue( $pt->checkPermission('view', array('view'=>'View')));
		$this->assertTrue( Dataface_PermissionsTool::checkPermission('view', array('view'=>'View')));
		$this->assertTrue( !$pt->checkPermission('view', array() ));
		$this->assertTrue( !Dataface_PermissionsTool::checkPermission('view', array()));
		$this->assertTrue( $pt->checkPermission('edit', array('view'=>'View', 'edit'=>'Edit')));
		$perms = array('view'=>'View');
		$this->assertTrue( $pt->view($perms));
		$perms = array('view'=>'View');
		$this->assertTrue( Dataface_PermissionsTool::view($perms));
		$perms = array('edit'=>'Edit');
		$this->assertTrue( !$pt->view($perms));
		$perms = array('edit'=>'Edit');
		$this->assertTrue( !Dataface_PermissionsTool::view($perms));
		$this->assertTrue( $pt->edit($perms));
		$this->assertTrue( Dataface_PermissionsTool::edit($perms));
		$perms = array('delete'=>'Delete');
		$this->assertTrue( !$pt->edit($perms));
		$this->assertTrue( !Dataface_PermissionsTool::edit($perms));
		$this->assertTrue( $pt->delete($perms));
		$this->assertTrue( Dataface_PermissionsTool::delete( $perms));
		
	}
	
	
	function test_table_permissions(){
		$pt =& Dataface_PermissionsTool::getInstance();
		
		$perms = $pt->getPermissions(Dataface_Table::loadTable('Profiles'));
		$this->assertEquals(
			array(1,1,1),
			array($perms['view'], $perms['edit'], $perms['delete'])
			
		);
		
		$perms = $pt->getPermissions(Dataface_Table::loadTable('Profiles'), array('field'=>'fname'));
		$this->assertEquals(
			array(1,1,1),
			array($perms['view'], $perms['edit'], $perms['delete'])
		);
		
		// varcharfield_checkboxes has view disabled in the fields.ini file
		$this->assertTrue(
			!$pt->view(Dataface_Table::loadTable('Test'), array('field'=>'varcharfield_checkboxes'))
		);
		
		$this->assertTrue(
			$pt->edit(Dataface_Table::loadTable('Test'), array('field'=>'varcharfield_checkboxes'))
		);
	}
	
	
	
	
	

}



?>
