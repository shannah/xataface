<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */

/**
 * @ingroup securityAPI
 */
class Dataface_PermissionsTool {

	/**
	 * Gets singleton instance of permissions tool.
	 * This  is to be called statically.
	 */
	public static function &getInstance(){
		
		static $instance = null;
		if ( $instance === null ){
			$instance = new Dataface_PermissionsTool_Instance();
		}
		return $instance;
	}
	
	public static function setDelegate($del){
		return self::getInstance()->setDelegate($del);
	}
	
	/**
	 * @since 2.0
	 * @see Dataface_PermissionsTool_Instance::addContextMask()
	 */
	public static function addContextMask(Dataface_RelatedRecord $contextRecord){
		$out = self::getInstance()->addContextMask($contextRecord);
		return $out;
	}
	
	/**
	 * @since 2.0
	 * @see Dataface_PermissionsTool_Instance::removeContextMask()
	 */
	public static function removeContextMask(Dataface_RelatedRecord $contextRecord){
		$out = self::getInstance()->removeContextMask($contextRecord);
		return $out;
	}
	/**
	 * @since 2.0
	 * @see Dataface_PermissionsTool_Instance::getContextMasks()
	 */
	public static function &getContextMasks(){
		$out =& self::getInstance()->getContextMasks();
		return $out;
	}
	
	/**
	 * @since 2.0
	 * @see Dataface_PermissionsTool_Instance::getContextMask()
	 */
	public static function &getContextMask($id, $fieldname=null){
		$out =& self::getInstance()->getContextMask($id, $fieldname);
		return $out;
	}
	
	
	/**
	 * @since 2.0
	 * @see Dataface_PermissionsTool_Instance::getPortalRecordPermissions()
	 */
	function getPortalRecordPermissions(Dataface_Record $record, $params=array()){
		return self::getInstance()->getPortalRecordPermissions($record, $params);
		
		
	}
	
	/**
	 * @brief Wrapper around getContextMask() to get the permissions
	 * for a record through the context of a portal.
	 * @returns array($perm:string => $val:boolean)
	 * @since 2.0
	 * @see Dataface_PermissionsTool_Instance::getPortalFieldPermissions()
	 */
	function getPortalFieldPermissions(Dataface_Record $record, $params=array()){
		return self::getInstance()->getPortalFieldPermissions($record, $params);
	}
	 
	
	public static function &getContext(){ return self::getInstance()->getContext(); }
	public static function setContext($context){
		return self::getInstance()->setContext($context);
	}
	
	public static function clearContext(){
		self::getInstance()->clearContext();
	}
	
	public static function &PUBLIC_CONTEXT(){
		return self::getInstance()->PUBLIC_CONTEXT();
	}
	
	/**
	 * Adds permissions as loaded from a configuration file.  Key/Value pairs
	 * are interpreted as being permission Name/Label pairs and key/Array(key/value)
	 * are interpreted as being a role defintion.
	 */
	public static function addPermissions($conf){
		return self::getInstance()->addPermissions($conf);
	}
	
	
	
	/**
	 * Gets the permissions of an object.
	 * @param $obj A Dataface_Table, Dataface_Record, or Dataface_Relationship record we wish to check.
	 * @param #2 Optional field name whose permission we wish to check.
	 */
	public static function getPermissions(&$obj, $params=array()){
		return self::getInstance()->getPermissions($obj, $params);
	}
	
	public static function filterPermissions(&$obj, &$perms, $params=array()){
		return self::getInstance()->filterPermissions($obj, $perms, $params);
	}
	
	/**
	 * Checks to see if a particular permission is granted in an object or permissions array.
	 * @param $permissionName The name of the permission to check (one of {'view','edit','delete'})
	 * @param $perms The object or permissions array to check.  It this is an object it must be of type one of {Dataface_Table, Dataface_Record, or Dataface_Relationship}.
	 * @param $params Optional field name in the case that param #2 is a table or record.
	 */
	public static function checkPermission($permissionName, $perms, $params=array()){
		return self::getInstance()->checkPermission($permissionName, $perms, $params);
	}
	
	/**
	 * Checks to see if an object or permissions array has view permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 * @param $perms Either an object (Table or Record) or a permissions array.
	 * @param #2 Optional name of a field we wish to check (only if $perms is a Table or Record).
	 */
	public static function view(&$perms, $params=array()){
		return self::getInstance()->view($perms, $params);
		
	}
	
	/**
	 * Checks to see if an object or permissions array has edit permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.	
	 * @param $perms Either an object (Table or Record) or a permissions array.
	 * @param #2 Optional name of a field we wish to check (only if $perms is a Table or Record).
	 */
	public static function edit(&$perms, $params=array()){
		return self::getInstance()->edit($perms, $params);
		
	}
	
	/**
	 * Checks to see if an object or permissions array has delete permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 * @param $perms Either an object (Table or Record) or a permissions array.
	 * @param #2 Optional name of a field we wish to check (only if $perms is a Table or Record).
	 */
	public static function delete(&$perms, $params=array()){
		return self::getInstance()->delete($perms, $params);
	}
	
	public static function MASK(){
		return self::getInstance()->MASK();
		
	}
	
	public static function _zero(){
		return self::getInstance()->_zero();
	}
	
	public static function _one(){
		return self::getInstance()->_one();
	}
	
	/**
	 * Reference to static NO ACCESS permissions array.
	 */
	public static function NO_ACCESS(){
		return self::getInstance()->NO_ACCESS();
	}
	
	/**
	 * Reference to permissions array that have only view permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 */
	public static function READ_ONLY(){
		return self::getInstance()->READ_ONLY();
	}
	
	/**
	 * Reference to permissions array that has all permissions (view, edit, and delete).
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 */
	public static function ALL(){
		return self::getInstance()->ALL();
	}
	
	/**
	 * Reference to permissions array that has read and edit access (but not delete).
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 */
	public static function &READ_EDIT(){
		return self::getInstance()->READ_EDIT();
	}
	
	
	/**
	 * Returns the permissions that are assigned to a certain role.  This allows a set of permissions
	 * to be grouped together and returned by getPermissions() methods.  A role is essentially just
	 * a list of permissions that are associated with the name of the role.  Roles can be defined in the
	 * permissions.ini files which are located in any table configuration folder, the application folder,
	 * or the dataface folder.  Try to place the roles in the appropriate folder based on what it is 
	 * most closely related to.  For example, if the role is specifically related to one table then place
	 * it in the permissions.ini file for that table, but if it is more general you can place it in the
	 * permissions.ini file for the application.  This will allow for better modularization and re-use
	 * of useful table definitions between applications.  The goal here is to allow you to distribute
	 * your tables to others so that they can be added easily to other applications.  If everything 
	 * relating to the table is located in one folder then this becomes much easier.
	 * @param $roleName The name of the role.
	 *
	 * @returns An array of permissions (the keys are the permission names, and the values are the permission
	 * labels.
	 */
	public static function &getRolePermissions($roleName){
		return self::getInstance()->getRolePermissions($roleName);
		
	
	}
	
	public static function roleExists($roleName){
		return self::getInstance()->roleExists($roleName);
	}
	
	
	/**
	 * Returns a list of names of granted permissions in a given permissions array.
	 */
	public static function namesAsArray($permissions){
		return self::getInstance()->namesAsArray($permissions);
	}
	
	
	/**
	 * Returns comma-delimited list of names of granted permissions in a given permissions
	 * array.
	 */
	public static function namesAsString($permissions){
		return self::getInstance()->namesAsString($permissions);
	}
	
	public static function cachePermissions(&$record, $params, $perms){
		return self::getInstance()->cachePermissions($record, $params, $perms);
		
	}
	
	public static function getCachedPermissions(&$record, $params){
		return self::getInstance()->getCachedPermissions($record, $params);
	}
	

	
	

}


class Dataface_PermissionsTool_PublicSecurityContext {
	function getPermissions(&$record){
		return Dataface_PermissionsTool::ALL();
	}
}


 

class Dataface_PermissionsTool_Instance {

	
	var $_cache = array();
	/**
	 * An associative array of role permissions available.
	 * [Role Name] -> array([Permission Name] -> [Allowed (0 or 1)])
	 */
	var $rolePermissions = array();
	
	/**
	 * Associative array of the loaded permissions. [Permission name] -> [Permission Label].
	 */
	var $permissions = array();
	
	var $context = null;
	
	var $contextMasks = null;
	
	
	var $delegate = null;
	
	function __construct($conf = null){
	
		if ( $conf === null ){
			import('Dataface/ConfigTool.php');
			$configTool =& Dataface_ConfigTool::getInstance();
			$conf = $configTool->loadConfig('permissions');
		
		}
		
		$this->addPermissions($conf);
		//print_r($this->permissions);
	}
	
	
	/**
	 * @brief Removes a related record's permissions from the permissions mask
	 * for its destination records.
	 *
	 * @since 2.0
	 * @param Dataface_RelatedRecord $contextRecord The related record that is being
	 * removed.
	 * @returns void
	 * @see addContextMask()
	 * @see getContextMasks()
	 * @see getContextMask()
	 * @see getPortalRecordPermissions()
	 * @see getPortalFieldPermissions()
	 */
	function removeContextMask(Dataface_RelatedRecord $contextRecord){
		
		if ( isset($this->contextMasks) ){
			$destRecords = $contextRecord->toRecords();
			$changed = false;
			foreach ($destRecords as $destRecord){
				$id = $destRecord->getId();
				if ( isset($this->contextMasks[$id]) ){
					$changed = true;
					unset($this->contextMasks[$destRecord->getId()]);
				}
			}
			if ( $changed ){
				$this->_cache = array();
			}
		}
	}
	
	/**
	 * @brief Adds as related record's permissions as a permissions mask for
	 * its destination records.  Any call to getPermissions() on the destination 
	 * records will now have their permissions augmented by the relationship
	 * permissions defined in the context record.
	 *
	 * @since 2.0
	 * @param Dataface_RelatedRecord $contextRecord The context record to provide permissions
	 *  for its destination records.
	 * @returns void
	 * @see removeContextMask()
	 * @see getContextMasks()
	 * @see getContextMask()
	 * @see getPortalRecordPermissions()
	 * @see getPortalFieldPermissions()
	 */
	function addContextMask(Dataface_RelatedRecord $contextRecord){
		$app = Dataface_Application::getInstance();
		if ( !isset($this->contextMasks) ) $this->contextMasks = array();
		$parentPerms = $contextRecord->getParent()->getPermissions(array('relationship'=>$contextRecord->_relationshipName));
		$perms = array();
		if ( @$parentPerms['add new related record'] or @$parentPerms['add existing related record'] ){
			$perms['new'] = 1;
		}
		if ( @$parentPerms['delete related record'] ){
			$perms['delete'] = 1;
		} else if ( isset($parentPerms['delete related record']) and !@$parentPerms['delete related record'] ){
			$perms['delete'] = 0;
		} if ( @$parentPerms['edit related records'] ){
			$perms['edit'] = 1;
		} else if ( isset($parentPerms['edit related records']) and !@$parentPerms['edit related records'] ){
			$perms['edit'] = 0;
		}
		if ( @$parentPerms['view related records'] ){
			$perms['view'] = 1;
		} else if ( isset($parentPerms['view related records']) and !@$parentPerms['view related records'] ){
			$perms['view'] = 0;
		}
		if ( @$parentPerms['find related records'] ){
			$perms['find'] = 1;
		} else if ( isset($parentPerms['find related records']) and !@$parentPerms['find related records'] ){
			$perms['find'] = 0;
		}
		if ( @$parentPerms['link related records'] ){
			$perms['link'] = 1;
		} else if ( isset($parentPerms['link related records']) and !@$parentPerms['link related records'] ){
			$perms['link'] = 0;
		}
		
		$recordPerms = $perms;
		unset($perms);
		$domainTable = $contextRecord->_relationship->getDomainTable();
		$destRecords = $contextRecord->toRecords();
		$numDest = count($destRecords);
		$destRecordIndex = array();
		$destRecordIds = array();
		foreach ($destRecords as $destRecord){
			$destRecordIndex[$destRecord->table()->tablename] = $destRecord;
			$id = $destRecord->getId();
			$destRecordIds[$destRecord->table()->tablename] = $id;
			$this->contextMasks[$id] = $recordPerms;
			if ( $numDest > 1 ){
				// This is a many-to-many relationship
				if ( strcmp($destRecord->table()->tablename,$domainTable)===0 ){
					// For many-to-many relationships
					// we don't want the user to be able to edit
					// the domain table.
					if ( !@$parentPerms['add new related record'] ){
						unset($this->contextMasks[$id]['new']);
					}
					unset($this->contextMasks[$id]['edit']);
					unset($this->contextMasks[$id]['link']);
					
				} else {
					// This is a join table
					if ( @$parentPerms['remove related record'] ){
						$this->contextMasks[$id]['delete'] = 1;
						
					} else if ( isset($parentPerms['remove related record']) and !@$parentPerms['remove related record'] ){
						$this->contextMasks[$id]['delete'] = 0;
					}
				}
			}
		}
		$relationship = $contextRecord->_relationship;
		$fields = $relationship->fields(true, true);
		$constrainedFields = array_flip($contextRecord->getConstrainedFields());
		foreach ($fields as $field){
			$fieldTable = $relationship->getTable($field);
			$fieldTableName = $fieldTable->tablename;
			$rec = $destRecordIndex[$fieldTableName];
			$perms = null;
			if ( strpos($field,'.') !== false ) list($junk,$fieldname) = explode('.', $field);
			else $fieldname = $field;
			$perms = $rec->getPermissions(array('field'=>$fieldname, 'nobubble'=>1));
			if ( !$perms ) $perms = array();
			$rfperms = $contextRecord->getParent()->getPermissions(array('relationship'=>$contextRecord->_relationshipName, 'field'=>$fieldname, 'nobubble'=>1));
			//echo "RFPerms: ";print_r($rfperms);
			if ( $rfperms ){
				foreach ($rfperms as $k=>$v){
					$perms[$k] = $v;
				}
			}
			if ( isset($constrainedFields[$fieldTableName.'.'.$fieldname]) ){
				$perms['edit'] = 0;
			}
			
			$id = $destRecordIds[$fieldTableName];
			$this->contextMasks[$id.'#'.$fieldname] = $perms;
			unset($perms);
						
		}
		
		
		$this->_cache = array(); // Clear the cache because
								 // the presence of this mask will
								 // alter the result of permissions queries
		
	
		
	}
	
	/**
	 * @brief Obtains a mask of permissions to be laid on top of permissions for 
	 *	particular records or their fields when obtaining their permissions.  This 
	 *  enables us to provide modified permissions for records depending on 
	 *  whether it is being accessed via a relationship or not. 
	 *
	 * <p>For example, suppose there is a relationship from table A to table B.  The 
	 * user has full permission to table A and no permission for table B, but the
	 * user has "view related records" permission on the relationship from A to B.
	 * Then a record of B that is related to A should be visible to the user.</p>
	 *
	 * <p>This depends on the @c -portal-context REQUEST parameter in order to work.
	 * That parameter should contain a related record ID that should be used to 
	 * provide a permissions mask.  This mask will only apply to the destination
	 * records of the specified related record, and the mask only contains those 
	 * permissions that can be affected by the parent's relationship.  This includes:</p>
	 *
	 *  -# new (from 'add new related record' and 'add existing related record')
	 *  -# delete (from 'delete related record')
	 *  -# edit (from 'edit related records')
	 *  -# view (from 'view related records')
	 *  -# find (from 'find related records')
	 *  -# link (from 'link related records')
	 *
	 * <h3>Example</h3>
	 *
	 * <p>Suppose we are viewing the record books?book_id=10 but the -portal-context 
	 * REQUEST parameter provided is:</p> @code
	 *	publishers/books?publishers::publisher_id=2&books::book_id=10
	 * @endcode
	 * <p>Further, suppose that the user is granted the 'view related records' and 'edit related records'
	 * permissions on the publishers?publisher_id=2 record's books relationship,
	 * but no permission to the books?book_id=10 record.</p>
	 *
	 * <p>In this example the user will have view and edit permission to the books
	 * record because the -portal-context allows it (i.e. it uses the permissions
	 * of the related record as a wrapper around the actual record to provide permissions).</p>
	 *
	 * <p>In the same way, the context could be used to deny permissions on a record.  The 
	 *  portal context gets the final say (i.e. overrides the record's permissions).</p>
	 *
	 * <h3>Field Permissions</h3>
	 * <p>Field permissions work similarly.  The relationship level permissions specified
	 * by the parent record's relationship will not override permissions explicitly set 
	 * for a field in the target record.  However permissions specified for a particular
	 * field of the relationship will override the field-level permissions of the target.</p>
	 *
	 * <p>For example:</p>
	 *	<ul>
	 *		<li>Parent record grants view related records permission</li>
	 *		<li>Target record denies view</li>
	 *		<li>Target table denies view on field A</li>
	 *	</ul>
	 * <p>Will result in view being denied on field A of the target table.</p>
	 *
	 * <p>But...</p>
	 *
	 * <ul>
	 *		<li>Parent record grants view related records permission</li>
	 *		<li>Target record denies view</li>
	 *		<li>Target table denies view on field A</li>
	 *		<li>Parent record grants view on field A of the relationship</li>
	 *	</ul>
	 * <p>Will result in view being granted on field A of the target table.</p>
	 *
	 *
	 * @returns array( $id:string => array( $perm:string => $val:boolean )) 
	 *
	 *
	 * @since 2.0
	 */
	function &getContextMasks(){
		if ( !isset($this->contextMasks) ){
			$this->contextMasks = array();
			$app = Dataface_Application::getInstance();
			$contextRecord = $app->getRecordContext();
			if ( $contextRecord ){
				
				//$contextRecord = df_get_record_by_id($query['-portal-context']);
				if ( is_a($contextRecord, 'Dataface_RelatedRecord') ){
					$this->addContextMask($contextRecord);
					
					
				}
				
			}
		}
		return $this->contextMasks;
	}
	
	/**
	 * @brief Gets the context mask for a particular record id.
	 *
	 * @param string $id The record id of the record to get the mask for.
	 * @param string $fieldname The optional field name to get the mask for.
	 * @returns array($perm:string=>$val:boolean) A permissions mask
	 *
	 * @see getContextMasks()
	 * @since 2.0
	 */
	function getContextMask($id, $fieldname=null){
		if ( $fieldname ) $id .= '#'.$fieldname;
		$masks =& $this->getContextMasks();
		if ( isset($masks[$id]) ) return $masks[$id];
		else {
			$out = array();
			return $out;
		}
	}
	
	/**
	 * @brief Wrapper around getContextMask() to get the permissions
	 * for a record through the context of a portal.
	 * @returns array($perm:string => $val:boolean)
	 * @since 2.0
	 */
	function getPortalRecordPermissions(Dataface_Record $record, $params=array()){
		return $this->getContextMask($record->getId());
		
		
	}
	
	/**
	 * @brief Wrapper around getContextMask() to get the permissions
	 * for a record through the context of a portal.
	 * @returns array($perm:string => $val:boolean)
	 * @since 2.0
	 */
	function getPortalFieldPermissions(Dataface_Record $record, $params=array()){
		return $this->getContextMask($record->getId(), @$params['field']);
	}
	
	function setDelegate($del){
		$this->delegate = $del;
	}
	
	function &getContext(){ return $this->context; }
	function setContext($context){
		if ( isset($this->context) ) unset($this->context);
		$this->context =& $context;
	}
	
	function clearContext(){
		$this->context = null;
	}
	
	function &PUBLIC_CONTEXT(){
		static $pcontext = 0;
		if ( !is_object($pcontext) ){
			$pcontext = new Dataface_PermissionsTool_PublicSecurityContext();
		}
		return $pcontext;
	}
	
	/**
	 * Adds permissions as loaded from a configuration file.  Key/Value pairs
	 * are interpreted as being permission Name/Label pairs and key/Array(key/value)
	 * are interpreted as being a role defintion.
	 */
	function addPermissions($conf){
		$this->_cache = array();
		foreach ( array_keys($conf) as $key ){
			// iterate through the config options
			if ( is_array($conf[$key]) ){
				//$out =& $conf[$key];
				/*
				foreach ($out as $okey=>$oval){
					$out[$okey] = intval(trim($oval));
				}
				*/
				$this->rolePermissions[$key] =& $conf[$key];//$out;
				//unset($out);
				
				
				
			} else {
				$this->permissions[$key] = $conf[$key];
			}
		}
	}
	
	
	
	/**
	 * Gets the permissions of an object.
	 * @param $obj A Dataface_Table, Dataface_Record, or Dataface_Relationship record we wish to check.
	 * @param #2 Optional field name whose permission we wish to check.
	 */
	function getPermissions(&$obj, $params=array()){
		$me =& $this;
		if ( isset($me->context) ){
			return $me->context->getPermissions($obj, $params);
		}
		if (
			is_a($obj, 'Dataface_Table') or 
			is_a($obj, 'Dataface_Record') or
			is_a($obj, 'Dataface_RelatedRecord') or
			is_a($obj, 'Dataface_Relationship') ){
			//echo "Getting permissions: "; print_r($params);
			$perms = $obj->getPermissions($params);
			$me->filterPermissions($obj, $perms, $params);
			return $perms;
		}
		throw new Exception(
			df_translate(
				'scripts.Dataface.PermissionsTool.getPermissions.ERROR_PARAMETER_1',
				'In Dataface_PermissionsTool, expected first argument to be Dataface_Table, Dataface_Record, or Dataface_Relationship, but received '.get_class($obj)."\n<br>",
				array('class'=>get_class($obj))
				),E_USER_ERROR);
	}
	
	function filterPermissions(&$obj, &$perms, $params=array()){
		if ( isset($this->delegate) and method_exists($this->delegate, 'filterPermissions') ) $this->delegate->filterPermissions($obj, $perms, $params);
	}
	
	/**
	 * Checks to see if a particular permission is granted in an object or permissions array.
	 * @param $permissionName The name of the permission to check (one of {'view','edit','delete'})
	 * @param $perms The object or permissions array to check.  It this is an object it must be of type one of {Dataface_Table, Dataface_Record, or Dataface_Relationship}.
	 * @param $params Optional field name in the case that param #2 is a table or record.
	 */
	function checkPermission($permissionName, $perms, $params=array()){
		$me =& $this;
		
		
		if ( is_array($perms) ){
			
			return  (isset( $perms[$permissionName]) and $perms[$permissionName]);
		}
		
		if ( PEAR::isError($perms) ){
			throw new Exception($perms->toString(), E_USER_ERROR);
		}
		
		if ( !is_object($perms) ){
			return  array();
			
		}
		
		// If we are this far, then $perms must be an object.. so we must get the object's 
		// permissions array and recall this method on it.
		return $me->checkPermission($permissionName, $me->getPermissions($perms, $params) );
	}
	
	/**
	 * Checks to see if an object or permissions array has view permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 * @param $perms Either an object (Table or Record) or a permissions array.
	 * @param #2 Optional name of a field we wish to check (only if $perms is a Table or Record).
	 */
	function view(&$perms, $params=array()){
		$me =& $this;
		return $me->checkPermission('view', $perms, $params);
		
	}
	
	/**
	 * Checks to see if an object or permissions array has edit permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.	
	 * @param $perms Either an object (Table or Record) or a permissions array.
	 * @param #2 Optional name of a field we wish to check (only if $perms is a Table or Record).
	 */
	function edit(&$perms, $params=array()){
		$me =& $this;
		return $me->checkPermission('edit', $perms, $params);
		
	}
	
	/**
	 * Checks to see if an object or permissions array has delete permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 * @param $perms Either an object (Table or Record) or a permissions array.
	 * @param #2 Optional name of a field we wish to check (only if $perms is a Table or Record).
	 */
	function delete(&$perms, $params=array()){
		$me =& $this;
		
		return $me->checkPermission('delete',$perms,$params);
	}
	
	function MASK(){
		$me =& $this;
		if ( isset($me->_cache['mask'] ) ) return $me->_cache['mask'];
		else {
			
			//$perms = array_flip($me->permissions);
			//$perms = array_map(array(&$me, '_zero'), $me->permissions);
			$perms = $me->permissions;
			foreach (array_keys($perms) as $key){
				$perms[$key] = 0;
			}
			$me->_cache['mask'] = $perms;
			return $perms;
		}
		
	}
	
	function _zero(){
		return 0;
	}
	
	function _one(){
		return 1;
	}
	
	/**
	 * Reference to static NO ACCESS permissions array.
	 */
	function NO_ACCESS(){
		static $no_access = 0;
		if ( $no_access === 0 ){
			$no_access = Dataface_PermissionsTool::MASK();
		}
		return $no_access;
	}
	
	/**
	 * Reference to permissions array that have only view permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 */
	function READ_ONLY(){
		$me =& $this;
		if ( isset($me->_cache['read_only']) ) return $me->_cache['read_only'];

		
		$read_only = /*array_merge($me->MASK(),*/ $me->getRolePermissions('READ ONLY')/*)*/;
		$read_only = array_map('intval', $read_only);
		$me->_cache['read_only'] = $read_only;
		
		return $read_only;
	}
	
	/**
	 * Reference to permissions array that has all permissions (view, edit, and delete).
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 */
	function ALL(){
		$me =& $this;
		if ( isset($me->_cache['all']) ) return $me->_cache['all'];
		$perms = array();
		foreach ( array_keys($me->permissions) as $key){
			$perms[$key] = 1;
		}
		$me->_cache['all'] = $perms;
		return $perms;
	}
	
	/**
	 * Reference to permissions array that has read and edit access (but not delete).
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 */
	function &READ_EDIT(){
		$me =& $this;
		if ( isset($me->_cache['read_edit']) ) return $me->_cache['read_edit'];
		$read_and_edit = /*array_merge($me->MASK(),*/ $me->getRolePermissions('EDIT')/*)*/;
		$read_and_edit = array_map('intval', $read_and_edit);
		$me->_cache['read_edit'] = $read_and_edit;
		return $read_and_edit;
	}
	
	
	/**
	 * Returns the permissions that are assigned to a certain role.  This allows a set of permissions
	 * to be grouped together and returned by getPermissions() methods.  A role is essentially just
	 * a list of permissions that are associated with the name of the role.  Roles can be defined in the
	 * permissions.ini files which are located in any table configuration folder, the application folder,
	 * or the dataface folder.  Try to place the roles in the appropriate folder based on what it is 
	 * most closely related to.  For example, if the role is specifically related to one table then place
	 * it in the permissions.ini file for that table, but if it is more general you can place it in the
	 * permissions.ini file for the application.  This will allow for better modularization and re-use
	 * of useful table definitions between applications.  The goal here is to allow you to distribute
	 * your tables to others so that they can be added easily to other applications.  If everything 
	 * relating to the table is located in one folder then this becomes much easier.
	 * @param $roleName The name of the role.
	 *
	 * @returns An array of permissions (the keys are the permission names, and the values are the permission
	 * labels.
	 */
	function &getRolePermissions($roleName){
		$me =& $this;
		if ( !isset($me->rolePermissions[$roleName]) ){
			// it looks like the role has not been defined
			throw new Exception(
				Dataface_LanguageTool::translate(
					'Role not found',
					'The role "'.$roleName.'" is not a registered role.',
					array('role'=>$roleName)
				), E_USER_ERROR
			);
		}
		
		return $me->rolePermissions[$roleName];
		
	
	}
	
	function roleExists($roleName){
		return isset($this->rolePermissions[$roleName]);
	}
	
	
	/**
	 * Returns a list of names of granted permissions in a given permissions array.
	 */
	function namesAsArray($permissions){
		if ( !is_array($permissions) ) throw new Exception("namesAsArray expects array.");
		$names = array();
		foreach ( $permissions as $key=>$value){
			if ( $value ){
				$names[] = $key;
			}
		}
		
		return $names;
	}
	
	
	/**
	 * Returns comma-delimited list of names of granted permissions in a given permissions
	 * array.
	 */
	function namesAsString($permissions){
		return implode(',', Dataface_PermissionsTool::namesAsArray($permissions));
	}
	
	function cachePermissions(&$record, $params, $perms){
		if (!isset($record) ){
			if ( isset($params['table']) ){
				$record_id = $params['table'];
			} else {
				$record_id='__null__';
			}
		}
		else $record_id = $record->getId();
		
		if ( count($params) > 0 ){
			$qstr = array();
			foreach ( $params as $key=>$value ){
				if ( is_object($value) or is_array($value) ) return null;
				$qstr[] = urlencode($key).'='.urlencode($value);
			}
			$qstr = implode('&', $qstr);
		} else {
			$qstr = '0';
		}
		
		$this->_cache['__permissions'][$record_id][$qstr] = $perms;
		
	}
	
	function getCachedPermissions(&$record, $params){
		if (!isset($record) ){
			if ( isset($params['table']) ){
				$record_id = $params['table'];
			} else {
				$record_id='__null__';
			}
		}
		else $record_id = $record->getId();
		
		if ( count($params) > 0 ){
			$qstr = array();
			foreach ( $params as $key=>$value ){
				if ( is_object($value) or is_array($value) ) return null;
				$qstr[] = urlencode($key).'='.urlencode($value);
			}
			$qstr = implode('&', $qstr);
		} else {
			$qstr = '0';
		}
		
		if (isset($this->_cache['__permissions'][$record_id][$qstr]) ){
			return $this->_cache['__permissions'][$record_id][$qstr];
		} else {
			return null;
		}
	}
	

	
	

}




