<?php
/**
 * @addtogroup delegateClass 
 * 
 * @brief The API of available methods that can be defined in a table delegate class.
 *
 * @since 0.1
 * @author Steve Hannah <steve@weblite.ca>
 */
interface DelegateClass {

	// @{
	/** @name Initialization */
	
	/**
	 * @brief Called after a table is loaded for the first time each request.
	 *
	 * If you want to adjust table-wide settings, this is a good place to do it.
	 * E.g. for security filters, and modification of the configuration.
	 *
	 * @param Dataface_Table $table The table object that was just loaded.
	 * @return void
	 * @since 0.6
	 *
	 * @see Dataface_Table::loadTable()
	 */
	function init(Dataface_Table $table);
	
	
	/**
	 * @brief Returns an alternate object to be used as the delegate.  This is handy
	 * for module developers to allow application developers to override module-defined
	 * delegates with application-defined delegates.
	 *
	 * @returns mixed Returns null to indicate to just use the default delegate class ($this).
	 *	otherwise returns an object that should be used as the delegate class object for the table.
	 *
	 * @see @ref module_developer_guide_withdata_step5p2 For an example use of this method.
	 */
	function getDelegate();
	
	
	// @}

	
	// @{ 
	/** @name Permissions */
	
	/**
	 * @brief Returns associative array of permissions that should be granted 
	 *  to the current user on this record.
	 * 
	 * @param Dataface_Record $record The record on which we are granting permissions. Note that this parameter may be null if permissions are being checked on the table in general so you must be able to handle null inputs for this value.
	 * @return array Associative array of permissions that are granted.  The keys of 
	 *   this array are the names of permissions (defined in the permissions.ini file)
	 * and values are boolean (0 or 1) to indicate whether or not the permission is granted.  
	 * This method may also return null to indicate that it has no opinion on the permissions
	 * to use - i.e. it will default to the permissions defined in the ApplicationDelegateClass.
	 *
	 * @since 0.5
	 *
	 * @attention Note that the results of this method will always supercede the results
	 * 	of getRoles() if defined.
	 *
	 * @section Flowchart
	 *
	 *
	 * The following flowchart shows the flow of control Xataface uses to determine the
	 * record-level permissions for a record.  (<a href="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png" target="_blank">click here to enlarge</a>):
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png?max_width=640"/>
	 * 
	 * @section Examples
	 *
	 * @subsection example1 All Permissions to Everyone
	 *
	 * @code
	 * function getPermissions($record){
	 *     return Dataface_PermissionsTool::ALL();
	 * }
	 * @endcode
	 *
	 * @subsection example2 No Permissions for Anyone
	 *
	 * @code
	 *
	 * function getPermissions($record){
	 *     return Dataace_PermissionsTool::NO_ACCESS();
	 * }
	 * @endcode
	 *
	 * @subsection example3 Read Only Permissions
	 * @code
	 * function getPermissions($record){
	 *     return Dataface_PermissionsTool::READ_ONLY();
	 * }
	 * @endcode
	 *
	 * @subsection example4 2-Tiered Permissions
	 *
	 * Allow logged in users full permissions, and everyone else no access.
	 *
	 * @code
	 * function getPermissions($record){
	 *     $auth = Dataface_AuthenticationTool::getInstance();
	 *     if ( $auth->isLoggedIn() ){
	 *         return Dataface_PermissionsTool::ALL();
	 *     } else {
	 *         return Dataface_PermissionsTool::NO_ACCESS();
	 *     }
	 * }
	 * @endcode
	 *
	 * @subsection example5 3-Tiered Permissions
	 *
	 * In this example we assume that our 'users' table has a 'role' column to track
	 * a user's role.  In our case if the 'role' is 'ADMIN', then the user is 
	 * considered to be an admin.  Otherwise they are just a regular user.
	 *
	 * @code
	 * function getPermissions($record){
	 *     $auth = Dataface_AuthenticationTool::getInstance();
	 *     $user = $auth->getLoggedInUser();
	 *     if ( $user and $user->val('role') == 'ADMIN' ){
	 *         return Dataface_PermissionsTool::ALL();
	 *     } else if ( $user ){
	 *         // user is logged in but isn't an admin
	 *         return Dataface_PermissionsTool::READ_ONLY();
	 *     } else {
	 *         return Dataface_PermissionsTool::NO_ACCESS();
	 *     }
	 * }
	 * @endcode
	 *
	 * @section bestpractice Best Practice
	 *
	 * It is recommended to define a getPermissions() method in the application delegate class
	 * that is very restrictive (i.e. no access for anyone except super-administrators) and
	 * then explicitly open up access to individual tables as necessary for other users.
	 *
	 * e.g.  In conf/ApplicationDelegate.php
	 * @code
	 * function getPermissions($record){
	 *     $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
	 *     if ( $user and $user->val('role') == 'ADMIN' ){
	 *         return Dataface_PermissionsTool::ALL();
	 *     } else {
	 *         return Dataface_PermissionsTool::NO_ACCESS();
	 *     }
	 * }
	 * @endcode
	 *
	 * Then suppose we want to open up the people table to allow logged in users
	 * to have read only access.
	 *
	 * In tables/tables/people.php
	 * @code
	 * function getPermissions($record){
	 *     $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
	 *     
	 *     // If user is an admin defer to the application delegate class for 
	 *     // permissions
	 *     if ( $user and $user->val('role') == 'ADMIN' ){
	 *         return null;
	 *     }
	 *
	 *     if ( $user ){
	 *         // User is logged in
	 *         return Dataface_PermissionsTool::READ_ONLY();
	 *     }
	 *
	 *     // Defer to the application delegate class for all other users
	 *     return null;
	 * }
	 * @endcode
	 * 
	 *
	 * @see http://xataface.com/documentation/tutorial/getting_started/permissions
	 * @see ApplicationDelegateClass::getPermissions()
	 * @see getRoles()
	 * @see __field__permissions()
	 * @see fieldname__permissions()
	 * @see rel_relationshipname__permissions()
	 * @see Dataface_Record::getPermissions()
	 * @see Dataface_Table::getPermissions()
	 * @see Dataface_PermissionsTool
	 * @see http://xataface.com/wiki/permissions.ini_file
	 *
	 *
	 */
	function getPermissions(Dataface_Record $record);
	
	/**
	 * @brief Returns one or more roles that are to be granted to the current user for the specified record.
	 *
	 * @param Dataface_Record $record The record on which the roles are to be granted.
	 * @return mixed Either a string with a single role or an array of strings representing
	 *		roles that are assigned to the current user on the given record.
	 * @since 1.0
	 *
	 * @section Synopsis
	 *
	 *  This method can be implemented as an alternative to getPermissions() for the purpose
	 *  of defining record-level permissions.  A "role" is a set of permissions that are grouped
	 *  together.  This allows us to group together related permissions into easy-to-use sets
	 *  and refer to them by their 'role' name.
	 *
	 *  Roles are defined in the <a href="http://www.xataface.com/wiki/permissions.ini_file">permissions.ini file</a>, and Xataface comes with several 
	 *  pre-defined roles.  These built-in roles are listed in the table below:
	 *
	 * <table>
	 * 	<tr>
	 *		<th>Role Name</th>
	 *		<th>Description</th>
	 *		<th>Definition</th>
	 *		<th>Since</th>
	 *	</tr>
	 *	<tr>
	 *		<td>NO ACCESS</td>
	 *		<td>A contains only the 'register' permission.
	 *		@attention This is NOT the same as Dataface_PermissionsTool::NO_ACCESS().  Dataface_PermissionsTool::NO_ACCESS() explicitly denies all permissions in the system, whereas the 'NO ACCESS' role simply does not explicitly <em>grant</em> any permissions except register.  This is an important distinction when dealing with field-level permissions since field permissions are applied as a mask over record-level permissions.
	 *		</td>
	 *		<td>
	 * @code
	 * [NO ACCESS]
	 *     register=1
	 * @endcode
	 *      </td>
	 *      <td>0.6</td>
	 *        
	 *  </tr>
	 *  <tr>
	 *      <td>READ ONLY</td>
	 *	    <td>Includes all view-ish permissions  - for viewing records.  This includes viewing records in list view, details view, XML export, RSS feeds, etc..</td>
	 *      <td>
	 * @code
	 * [READ ONLY]
	 *   view in rss=1
	 *   view = 1
	 *   link = 1
	 *   list = 1
	 *   calendar = 1
	 *   view xml = 1
	 *   show all = 1
	 *   find = 1
	 *   navigate = 1
	 *   ajax_load = 1
	 *   find_list = 1
	 *   find_multi_table = 1
	 *   rss = 1
	 *   export_csv = 1
	 *   export_xml = 1
	 *   export_json = 1
	 *   view related records=1
	 *   related records feed=1
	 *   expandable=1
	 * @endcode
	 * 		</td>
	 *		<td>0.6</td>
	 *	</tr>
	 *  <tr>
	 *		<td>EDIT</td>
	 *      <td>Includes all permissions for viewing and editing records.</td>
	 *      <td>
	 * @code
	 * [EDIT extends READ ONLY]
	 *	edit = 1
	 *	add new related record = 1
	 *	add existing related record = 1
	 *	add new record = 1
	 *	remove related record = 1
	 *	reorder_related_records = 1
	 *	import = 1
	 *	translate = 1
	 *	new = 1
	 *	ajax_save = 1
	 *	ajax_form = 1
	 *	history = 1
	 *	edit_history = 1
	 *	copy = 1
	 *	update_set = 1
	 *	update_selected=1
	 *	select_rows = 1
     * @endcode
     * 		</td>
     *		<td>0.6</td>
     *	</tr>
     *  <tr>
     *		<td>DELETE</td>
     *		<td>Permission to view, edit, and delete records.</td>
     *		<td>
     * @code
     * [DELETE extends EDIT]
	 *   delete = 1
	 *   delete found = 1
	 *   delete selected = 1
	 * @endcode
	 *		</td>
	 *		<td>0.6</td>
	 *	</tr>
	 * 	<tr>
	 *		<td>MANAGER</td>
	 *		<td>Edit and delete permissions as well as permission to access the control panel and management functions of the application.</td>
	 *		<td>
	 * @code
	 * [MANAGER extends ADMIN]
	 *   manage=1
	 *   manage_output_cache=1
	 *   manage_migrate=1
	 *   manage_build_index=1
	 *   install = 1
	 * @endcode
	 *		</td>
	 *		<td>0.6</td>
	 *	</tr>
	 * </table>
	 *
	 *  Since this method simply provides an alternate way to define permissions, you generally would
	 *  not define both methods in the same delegate class, but if you did implement both the 
	 *  getRoles() method would take priority of over the getPermissions() method.
	 *
	 *  @attention Note that the results of this method are always superceded by
	 *  the results of getPermissions() if defined.
	 *
	 * 
	 *
	 * @section Flowchart
	 *
	 *
	 * The following flowchart shows the flow of control Xataface uses to determine the
	 * record-level permissions for a record.  (<a href="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png" target="_blank">click here to enlarge</a>):
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png?max_width=640"/>
	 * 
	 * @section Examples
	 *
	 * @subsection rolesexample1 getRoles() vs getPermissions()
	 *
	 * Consider the getPermissions() method below:
	 *
	 * @code
	 * function getPermissions($record){
	 *     return Dataface_PermissionsTool::getRolePermissions('READ ONLY');
	 * }
	 * @endcode
	 *
	 * This is equivalent to the getRoles() method:
	 * @code
	 * function getRoles($record){
	 *     return 'READ ONLY';
	 * }
	 * @endcode
	 *
	 * The getRoles() version is much more succinct as it just allows us to return the 
	 * name of the role that is granted, rather than having to resolve the permissions for
	 * that role.
	 * 
	 * @subsection rolesexample2 Granting Multiple Roles
	 * 
	 * It is also possible to grant multiple roles, in which case the 
	 * resulting permissions would be the union of the granted roles (where
	 * the last role listed takes precendent).
	 *
	 * @code
	 * function getRoles($record){
	 *     return array('MY FIRST ROLE', 'MY SECOND ROLE');
	 * }
	 * @endcode
	 * 
	 * @see getPermissions()
	 * @see Dataface_Record::getRoles()
	 * @see http://xataface.com/documentation/tutorial/getting_started/permissions
	 * @see Dataface_PermissionsTool
	 * @see http://xataface.com/wiki/permissions.ini_file
	 * @see ApplicationDelegateClass::getPermissions()
	 */
	function getRoles(Dataface_Record $record);
	
	/**
	 * @brief Returns default permissions for all fields of the given record to be granted to the current user.
	 *
	 * @param Dataface_Record $record The record that is subject of these permissions.
	 * @return array Associative array of permissions granted.  Keys are permission names 
	 *  as defined in the <a href="http://xataface.com/wiki/permissions.ini_file">permissions.ini file</a>
	 *  and corresponding values are either 0 or 1 depending on whether the permission is
	 *  granted or not.
	 *
	 * @since 1.0
	 *
	 * @section Synopsis
	 *
	 * Implementing the __field__permissions() method in your delegate class allows you
	 * to define the default permissions that should be applied to each field of the table. 
	 *
	 *
	 * Setting field defaults can be important if you want, for example, only certain fields
	 * to be editable, but all other fields to be read only.  In such a case you would 
	 * need to set the record-level permissions to allow editing (in order for users to
	 * even access the 'edit' action), then set the default field permissions to read only
	 * using the __field__permissions() method, then set the permissions of only the editable
	 * fields to be editable by implementing fieldname__permissions() methods for each of 
	 * those fields.
	 *
	 *
	 * @attention Field level permissions, like those returned by this method are treated
	 *   as permission masks that are overlaid over the record permissions.  Therefore
	 *   if roles returned by this method do not explicitly disallow a permission, then the 
	 *	 record-level permissions will be used for that permission.
	 *
	 * @section Flowchart
	 *
	 * The following flowchart shows the flow of control Xataface uses to determine the field-level permissions for a field in a record.
	 *
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png?max_width=640"/>
	 * <a href="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png">Click here to enlarge</a>
	 *
	 * @section Examples
	 *
	 * Consider the following table 'people':
	 *
	 * @code
	 * CREATE TABLE `people` (
	 *    `person_id` INT(11)  NOT NULL AUTO_INCREMENT PRIMARY KEY,
	 *    `username` VARCHAR(32) NOT NULL,
	 *    `full_name` VARCHAR(100),
	 *    `email` VARCHAR(100),
	 *    `bio` TEXT,
	 *    `admin_comments' TEXT
	 * )
	 * @endcode
	 *
	 * And our application is using the following 'users' table to store our user accounts.
	 * @code
	 * CREATE TABLE `users` (
	 *     `username` VARCHAR(32) NOT NULL PRIMARY_KEY,
	 *     `password` VARCHAR(32),
	 *     `role` ENUM('USER','ADMIN') default 'USER'
	 * )
	 * @endcode
	 *
	 * And let's assume that we intend for the <em>people.username</em> field to represent
	 * the username of the user who "owns" the people record.  (i.e. it has an implicit
	 * foreign key to <em>users.username</em>.
	 *
	 * Now, suppose we want to implement the following permission structure:
	 *
	 * - Admin users have full access
	 * - Regular users can edit the <em>bio</em> field of their own people record, but
	 *  can only view the other fields - Except they cannot view the <em>admin_comments</em>
	 * field.
	 * - Regular users cannot view any information in records they do not own.
	 * 
	 * @subsection step1 Step 1: Record-level Permissions
	 * 
	 * In tables/people/people.php:
	 * @code
	 * function getPermissions($record){
	 *     $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
	 *     if ( $user and $user->val('role') == 'ADMIN' ){
	 *         // ADMIN users get full access
	 *         return Dataface_PermissionsTool::ALL();
	 *     } else if ( $record and $user and $user->val('username') == $record->val('username') ){
	 *         // Regular users get edit permissions to their own people record.
	 *         return Dataface_PermissionsTool::getRolePermissions('EDIT');
	 *     } else {
	 *         // All other users get no access to this record.
	 *         return Dataface_PermissionsTool::NO_ACCESS();
	 *     }
	 * }
	 * @endcode
	 *
	 * In this getPermissions() method we define 3 levels of users:
	 *
	 * -# Administrative users who have full access
	 * -# Non-administrative users who own the current record who have edit access.
	 * -# All other users who have no access.
	 *
	 * Things to notice here:
	 *
	 * -# Before calling any methods on $record, we needed to verify that it was not null, because this
	 *   getPermissions() method will be called with null passed as the context record by Xataface.
	 * -# We make use of the Dataface_PermissionsTool::getRolePermissions()  method to return the edit
	 *   permissions for the owner users.  This makes use of the <a href="http://www.xataface.com/wiki/permissions.ini_file">permissions.ini</a> file which defines
	 *   a role called "EDIT" which includes all permissions relevant to editing records (it also includes
	 *   all permissions necessary for viewing records).
	 *
	 * @subsection step1 Step 2: Default Field-level Permissions
	 *
	 * If we only implemented the getPermissions() method as above, record "owners" would
	 * be able to view and edit all fields in their own people record.  In fact we only want them
	 * to be able to edit the "bio" field, and we don't want them to be able to see the 
	 * admin_comments field.
	 *
	 * We implement the __field__permissions() method to limit their access to these fields:
	 *
	 * Also in the tables/people/people.php file:
	 *
	 * @code
	 * function __field__permissions($record){
	 *     $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
	 *     if ( $user and $user->val('role') == 'ADMIN' ){
	 *         // Defer to record-level permissions for ADMIN users ... we have nothing
	 *         // to add here
	 *         return null;
	 *     } else if ( $user and $record and $user->val('username') == $record->val('username') ){
	 *         // We want to make all fields read only - no editing
	 *         return array('edit'=>0, 'new'=>0);
	 *     } 
	 *
	 *     // For all other situations just defer to the record level permissions
	 *     return null;
	 * }
	 * @endcode
	 *
	 * In this __field__permissions() method we first defer admin users to their normal 
	 * record level permissions because we don't wish to modify their permissions in any way
	 * at the field level (they can already do everything and that's how we like it).
	 *
	 * For users who own a record, we return permissions array that explicitly disallows 
	 * only those permissions that we want to disallow.  Notice that it wouldn't be 
	 * sufficient here to return Dataface_PermissionsTool::READ_ONLY() because that would
	 * return only an array of the permissions that the user is explicitly allowed - and
	 * since the result of this method is simply overlaid on top of the record level permissions
	 * it wouldn't cause any permissions granted at that level to be overridden.  Hence we
	 * need to explicitly disallow the permissions here.
	 *
	 * Another way to do this might be to start with a base of Dataface_PermissionsTool::NO_ACCESS()
	 * and then simply set 'view' = 1.  However this would disallow some other 'view-ish' permissions
	 * that you may have wanted.
	 *
	 * @subsection step3 Step 3: Make the BIO field editable for Record Owner
	 *
	 * In tables/people/people.php:
	 * @code
	 * function bio__permissions($record){
	 *     $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
	 *     if ( $user and $user->val('role') == 'ADMIN' ){
	 *         // Defer to default field-level permissions for ADMIN users ... we have nothing
	 *         // to add here
	 *         return null;
	 *     } else if ( $user and $record and $user->val('username') == $record->val('username') ){
	 *         // We want to make the bio field editable
	 *         return array('edit'=>1);
	 *     } 
	 *
	 *     // For all other situations just defer to the default field-level permissions
	 *     return null;
	 * }
	 * @endcode
	 *
	 * This looks almost the same as our __field__permissions() method except that we
	 * are allowing the edit permission on the field for record owners.
	 *
	 * @subsection step4 Step 4: Hide the admin_comments field from record owners
	 *
	 * At this point we have almost everything the way we want it except that record
	 * owners can still see the admin comments field - but we want this to be private
	 * for administrators.
	 *
	 * So we implement the admin_comments__permissions() method:
	 * @code
	 * function admin_comments__permissions($record){
	 *     $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
	 *     if ( $user and $user->val('role') == 'ADMIN' ){
	 *         // Defer to default field-level permissions for ADMIN users ... we have nothing
	 *         // to add here
	 *         return null;
	 *     } else if ( $user and $record and $user->val('username') == $record->val('username') ){
	 *         // Hide the field from record owners
	 *         return Dataface_PermissionsTool::NO_ACCESS();
	 *     } 
	 *
	 *     // For all other situations just defer to the default field-level permissions
	 *     return null;
	 * }
	 * @endcode
	 *
	 * @see getPermissions()
	 * @see __field__roles()
	 * @see fieldname__roles()
	 * @see http://xataface.com/documentation/tutorial/getting_started/permissions
	 * @see http://xataface.com/wiki/permissions.ini_file
	 * @see ApplicationDelegateClass::__field__permissions()
	 */
	function __field__permissions(Dataface_Record $record);
	
	/**
	 * @brief Returns the roles for all fields of the given record to be granted to the current user.
	 *
	 * @param Dataface_Record $record The record that is subject of this query.
	 *
	 * @return mixed Either a string with a single role or an array of strings representing
	 *		roles that are assigned to the current user on the fields of this record.  May also return 
	 *		null to indicate that the results of this method should be ignored in favor
	 *		of permissions returned higher up the chain.
	 *
	 *
	 * @since 1.0
	 *
	 * @section Flowchart
	 *
	 * The following flowchart shows the flow of control Xataface uses to determine the field-level permissions for a field in a record.
	 *
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png?max_width=640"/>
	 * <a href="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png">Click here to enlarge</a>
	 *
	 *
	 * 
	 *
	 * @see __field__permissions()
	 * @see getPermissions()
	 * @see getRoles()
	 * @see fieldname__roles()
	 * @see fieldname__permissions()
	 * @see Dataface_PermissionTool
	 * @see http://xataface.com/documentation/tutorial/getting_started/permissions
	 * @see http://xataface.com/wiki/permissions.ini_file
	 */
	function __field__roles(Dataface_Record $record);
	
	/**
	 * @brief Returns the permissions for a particular field of the given record.
	 *
	 * @param Dataface_Record $record The record that is subject of this query.
	 * @return mixed Either an associative array of permissions or null to defer permissions to the default field permissions or record-level permissions.
	 * @since 0.7
	 * @section Synopsis
	 *
	 * This method can be implemented to define fine-grained permissions on a particular field
	 * of the database.  It can be helpful if you want to override the record-level permissions (getPermissions() 
	 * or the default field permissions  ( __field__permissions() ) with specific permissions
	 * for this field.  See the flowchart below for details of how field-level permissions
	 * are resolved in Xataface.
	 * 
	 *
	 * @attention Field level permissions are treated as a mask that is laid over
	 * the record permissions for that field.  Therefore, to disallow a permission
	 * on a field that is otherwise allowed at the record level, you must explicitly
	 * return a permission of 0 - i.e. you can't just omit the permission as you can
	 * at the record level.
	 *
	 * @section Flowchart
	 * 
	 * The following flowchart shows the flow of control Xataface uses to determine the field-level permissions for a field in a record.
	 *
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png?max_width=640"/>
	 * <a href="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png">Click here to enlarge</a>
	 *
	 * @section Examples
	 *
	 * Please see the documentation for __field__examples() for a thorough example of
	 * how to define fine-grained field-level permissions using this method.
	 *
	 *
	 * @see __field__permissions()
	 * @see getPermissions()
	 * @see getRoles()
	 * @see fieldname__roles()
	 * @see fieldname__permissions()
	 * @see Dataface_PermissionTool
	 * @see http://xataface.com/documentation/tutorial/getting_started/permissions
	 * @see http://xataface.com/wiki/permissions.ini_file
	 */
	function fieldname__permissions(Dataface_Record $record);
	
	/**
	 * @brief Returns the roles for a particular field of the given record.
	 *
	 * @param Dataface_Record $record The record that is subject of this query.
	 * @return mixed Either an array of string role names, a string with a single role name,
	 *		or null.
	 *
	 * @since 1.0
	 *
	 * @section Synopsis
	 * 
	 * This method is the analog of the fieldname__permissions() method except that it 
	 * returns roles instead of permissions.  The roles returned by this method
	 * are resolved to permissions and laid over the record-level permissions to 
	 * obtain the effective field permissions.
	 *
	 * It is rare to implement both the fieldname__roles() and the fieldname__permissions()
	 * method in the same delegate class.  If this happens, though the fieldname__permissions()
	 * will take precedence.
	 *
	 * @attention This method will always be superceded by fieldname__permissions() if 
	 *	defined.
	 *
	 *
	 * @attention Field level roles are resolved to a permissions array which is treated
	 *	as a mask that is laid over the record permissions in order to establish the 
	 *	effective permissions for a field.  Therefore, to disallow a permission on a field
	 *  you must explicitly set it to 0 (you cannot just omit it).
	 *
	 * @section Flowchart
	 *
	 * The following flowchart shows the flow of control Xataface uses to determine the field-level permissions for a field in a record.
	 *
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png?max_width=640"/>
	 * <a href="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png">Click here to enlarge</a>
	 *
	 * @section Examples
	 *
	 * E.g. To define custom roles for a field named 'invoice_number':
	 *
	 * @code
	 * function invoice_number__roles($record){
	 *     return 'READ ONLY';
	 * }
	 * @endcode
	 *
	 * Or to apply multiple roles:
	 * @code
	 * function invoice_number__roles($record){
	 *     return array('ROLE 1', 'ROLE 2');
	 * }
	 * @endcode
	 *
	 *
	 * @see __field__roles()
	 * @see getPermissions()
	 * @see getRoles()
	 * @see fieldname__roles()
	 * @see fieldname__permissions()
	 * @see Dataface_PermissionTool
	 * @see http://xataface.com/documentation/tutorial/getting_started/permissions
	 * @see http://xataface.com/wiki/permissions.ini_file
	 */
	function fieldname__roles(Dataface_Record $record);
	
	/**
	 * @brief Returns the permissions for a particular relationship of the given 
	 *  record.
	 *
	 * @param Dataface_Record $record The record that is subject of this query.
	 * @return array Associative array of permissions granted for this relationship.
	 * @since 1.0
	 *
	 * @attention Relationship permissions are treated as a mask that are overlaid
	 * 	over the record level permissions to produce the effective permissions for
	 * 	the given relationship.  Therefore, in order to deny a permission for a
	 *	relationship, you must explicitly set it to 0 - it is not sufficient to omit 
	 *	the permission like you do with record level permissions.
	 *
	 * @section Synopsis
	 *
	 * This method, if implemented, returns a permissions mask to apply to a particular
	 * relationship in the given record.  For example if we want to specifically allow
	 * adding related records to one relationship but not other relationships.
	 *
	 * There are only a few specific permissions that pertain to relationships and
	 * these are the only ones that have any effect when returned from this method.
	 *
	 * These permissions include:
	 *
	 * <table>
	 *		<tr>
	 *			<th>Permission Name</th>
	 *			<th>Description</th>
	 *		</tr>
	 *		<tr>
	 *			<td>add new related record</td>
	 *			<td>Permission to add new records to the relationship.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>add existing related record</td>
	 *			<td>Permission to add existing records to the relationship.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>related records feed</td>
	 *			<td>Permission to view the RSS feed for the related records.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>remove related record</td>
	 *			<td>Permission to remove a record from the relationship.  This is only applicable to many-to-many relationships as this doesn't include permission to delete the record from the database, only to sever the relationship.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>delete related record</td>
	 *			<td>Permission to delete a related record (delete it from the database).</td>
	 *		</tr>
	 *		<tr>
	 *			<td>view related records</td>
	 *			<td>Permission to view the related records list.  If this is denied, then the related records tab won't be present, and if users try to go to the URL for the tab directly, they'll receive a permission denied error.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>reorder_related_records</td>
	 *			<td>Permission to reorder related records.  This is only applicable to relationships that have a designated order column (via the metafields:order directive of the <a href="http://www.xataface.com/wiki/relationships.ini_file">relationships.ini file</a> ).</td>
	 *		</tr>
	 *	</table>
	 * 
	 * 
	 * @see rel_relationshipname__roles()
	 * @see getPermissions()
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 */
	function rel_relationshipname__permissions(Dataface_Record $record);
	
	/**
	 * @brief Returns the roles for a particular relationship of the given 
	 *  record.
	 *
	 * @param Dataface_Record $record The record that is subject of this query.
	 * @return mixed Either an array of string role names, a single string role name, or null.
	 * @since 1.0
	 *
	 * @see rel_relationshipname__permissions()
	 */ 
	function rel_relationshipname__roles(Dataface_Record $record);
	
	/**
	 * @brief Returns the link that should be returned by Dataface_Record::getURL() if the 
	 *   user isn't granted the 'link' permission on the given record.
	 *
	 * @param Dataface_Record $record The record that is subject of this query.
	 * @return string The link that should be returned by getURL() if the user doesn't have the 
	 * 	'link' permission.
	 * @since 1.0
	 *
	 * @see Dataface_Record::getURL()
	 * @see no_access_text()
	 * @see http://xataface.com/wiki/permissions.ini_file
	 */
	function no_access_link(Dataface_Record $record, $params=array());
	
	/**
	 * @brief Returns the text that should be returned by Dataface_Record::display() if the user
	 *  doesn't have 'view' permission for the record.
	 *
	 * @param Dataface_Record $record The record that is subject of this query.
	 * @param array $params Associative array of extra parameters.  One such parameter is the 'field' 
	 *  parameter which is the name of the field for which the text is to be displayed.
	 * @return string The string to display if the user doesn't have 'view' permission.
	 * @since 1.0
	 *
	 * @see no_access_link()
	 * @see Dataface_Record::display()
	 * @see Dataface_Record::secureDisplay
	 * @see http://xataface.com/wiki/permissions.ini_file
	 */
	function no_access_text(Dataface_Record $record, $params=array());
	
	// @}
	
	
	// @{ 
	/** @name Record Metadata */
	
	/**
	 * @brief Returns the record's title.  This is used in Xataface to return the title
	 * of a record when the Dataface_Record has been loaded into memory already.  It returns
	 * a string that can be used as the title of a record and takes a Dataface_Record object
	 * as a parameter - hence it can draw on any information in the record to form the
	 * effective title.
	 *
	 * @attention For sets of records (e.g. on the Add Existing Records dropdown list) Xataface
	 * will likely use the titleColumn() method instead for getting the titles so that
	 * it doesn't need to load each record into memory just to get the record titles.
	 *
	 * @param Dataface_Record $record The record whose title we are returning.
	 * @return string The title of the record which would override the value returned
	 *		by Dataface_Record::getTitle()
	 * @since 0.5
	 *
	 * @see Dataface_Record::getTitle()
	 * @see titleColumn() for use when record titles are needed for sets of records.
	 */
	function getTitle(Dataface_Record $record);
	
	
	/**
	 * @brief Returns an SQL expression (that is valid for a column in a select clause)
	 * denoting the column(s) that comprise the title of records in this table.  This
	 * may return a single column name or it may make use of SQL functions like CONCAT()
	 * to compose a title from multiple columns.
	 *
	 * @return string An SQL expression.  Either the column name to use as title or
	 * an SQL expression that evaluates to a column name.
	 *
	 * <h3>Example</h3>
	 *
	 * <p>Using the firstname field as the record title:</p>
	 * @code
	 * function titleColumn(){
	 *     return 'firstname';
	 * }
	 * @endcode
	 *
	 * <h3>Example 2</h3>
	 * <p>Using first name and last name as record title.</p>
	 * @code
	 * function titleColumn(){
	 *     return "concat(firstname,' ',lastname)";
	 * }
	 * @endcode
	 *
	 * @see getTitle() For a method to define a title for a single record.
	 */
	function titleColumn();
	
	/**
	 * @brief Returns the URL of the given record.
	 *
	 * @param Dataface_Record $record The record whose URL we are returning.
	 * @param array $params The query parameters to include in the generated URL.
	 *
	 * @return string The URL to the record.
	 * @since 0.5
	 *
	 * @see Dataface_Record::getURL()
	 * @see Dataface_Record::getPublicLink()
	 * @see getPublicLink()
	 */
	function getURL(Dataface_Record $record, $params=array());
	
	/**
	 * @brief Returns the Unix timestamp representing the last modification time of the given record.
	 *
	 * @param Dataface_Record $record The $record whose lastModified time we are checking.
	 * @return long The unix timestamp marking the last modification date of this record.
	 *
	 * @see Dataface_Record::getLastModified()
	 */
	function getLastModified(Dataface_Record $record);
	
	/**
	 * @brief Returns a brief description of this record for use in lists, RSS feeds, and more.
	 *
	 * @param Dataface_Record $record The record for which the description applies.
	 *
	 * @return string The record description.
	 * @since 0.8
	 *
	 * @see Dataface_Record::getDescription()
	 */
	function getDescription(Dataface_Record $record);
	
	/**
	 * @brief Returns the name of the user who created this record (i.e. the record author).
	 *
	 * @param Dataface_Record $record
	 * @return string The Author's name.
	 * @since 0.8
	 *
	 * @see Dataface_Record::getCreator()
	 */
	function getCreator(Dataface_Record $record);
	
	/**
	 * @brief Returns the publicly accessible URL for a given record.
	 *
	 * @param Dataface_Record $record The record to which the URL refers.
	 * @return string The URL for the public webpage of the given record.
	 * @since 0.5
	 *
	 * @see getURL()
	 * @see Dataface_Record::getPublicLink()
	 */
	function getPublicLink(Dataface_Record $record);
	
	/**
	 * @brief Returns the breadcrumbs to a given record.
	 * 
	 * @param Dataface_Record $record The record to which the breadcrumbs apply.
	 * @return array Associative array of breadcrumbs with the keys being the breadcrumb title, and 
	 *  the value being the breadcrumb link.
	 *
	 * @since 0.8
	 * @see Dataface_Record::getBreadCrumbs()
	 * @see Dataface_SkinTool::bread_crumbs()
	 */
	function getBreadCrumbs(Dataface_Record $record);
	
	/**
	 * @brief Returns the records that are considered to be children of the given record.
	 * 
	 * @param Dataface_Record $record The parent record for which children are being returned.
	 * @return array An array of Dataface_Record objects that are children of $record.
	 *
	 * @since 0.8
	 * @see Dataface_Record::getChildren()
	 */
	function getChildren(Dataface_Record $record);
	
	// @}
	
	
	// @{ 
	/** @name Field Filters */
	
	/**
	 * @brief Overrides the display of the specified field name for the given record.
	 *
	 * @param Dataface_Record $record The record whose data is to be displayed.
	 * @return string The string field contents prepared for print.
	 * @since 0.5
	 *
	 * @attention Do not return HTML tags in this method, as they will be escaped by Xataface. 
	 *  If you wish to override the HTML output of a record, implement the fieldname__htmlValue()
	 *  method instead.
	 *
	 * @note If your output doesn't depend on anything but the string value of @c fieldname
	 * then it is preferred to implement the fieldname__format() method.
	 *
	 * @see Dataface_Record::display()
	 */
	function fieldname__display(Dataface_Record $record);
	
	/**
	 * @brief Formats the output value of a particular field.  In constrast to fieldname__display()
	 * this does not take the Dataface_Record as a parameter.  As such it can be used to format
	 * arbitrary values in a consistent way.   If you don't need information from the record
	 * when formatting a value for display, it is preferred to use this method since it is
	 * more generic.
	 *
	 * @param string $value The value that is to be formatted.
	 * @return string The formatted value.
	 *
	 * @since 1.4
	 * @see Dataface_Table::format()
	 */
	function fieldname__format($value);
	
	/**
	 * @brief Overrides the string value of specified field for a given record.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return string The string field value
	 * @since 0.5
	 *
	 * @attention This method affects how the field value is loaded into form widgets
	 *  in addition to viewable sections of the site, so take great care when implementing
	 *  this method.  If you only want to override how a field value is displayed in the 
	 *  site but don't want to affect edit forms, them you should use the fieldname__display()
	 *  method instead.
	 *
	 *
	 * @see Dataface_Record::getValueAsString()
	 */
	function fieldname__toString(Dataface_Record $record);
	
	/**
	 * @brief Overrides the HTML display value of a field for a given record.
	 * 
	 * @param Dataface_Record $record The subject record.
	 * @return string The HTML representation of the specified field value.
	 *
	 * @see Dataface_Record::htmlValue()
	 */
	function fieldname__htmlValue(Dataface_Record $record);
	
	/**
	 * @brief Overrides the parsing behavior for normalizing a field value.  This will
	 *  dictate how values are transformed when being added to the record via setValue()
	 * 
	 * @param mixed $value The input value.
	 * @return mixed The normalized value ready to store in the record (not in the database) but in the record.  i.e. this can be a data strucure - it doesn't have to be a string.
	 *
	 * @since 0.5
	 *
	 * @section Synopsis 
	 * 
	 * When Dataface_Record::setValue() is called to set the value in a field, it first
	 * tries to parse and normalize the value.  Xataface has built-in parsers that work
	 * according to the type of column.  However you may wish to write your own 
	 * parser by implementing this method.
	 *
	 * @section Invariants
	 *
	 * @subsection invariants1 1: Idempotent
	 *
	 * Any fieldname__parse() implementations must be idempotent.  I.e. it must be the
	 * case that fieldname__parse(fieldname__parse($val)) == fieldname__parse($val)
	 *
	 * This is because we need to be able to set values in fields without worrying about 
	 * the value being modified by the simple act of setting the field value.
	 *
	 * @subsection invariants2 2: Inverse of fieldname__toString()
	 *
	 * Since the fieldname__toString() method, if implemented, would be used to produce
	 * the value that is displayed in form widgets, the fieldname__parse() method must
	 * be able to handle the result of fieldname__toString() and be able to normalize it
	 * since it is effectively the inverse operation of fieldname__toString().
	 *
	 * @see fieldname__toString()
	 * @see Dataface_Record::setValue()
	 * @see Dataface_Record::getValue()
	 * 
	 */
	function fieldname__parse($value);
	
	/**
	 * @brief Serializes a field value to prepare it for insertion into an SQL query.
	 *
	 * @param mixed $value The field value that is to be serialized.
	 * @return string The serialized value that is ready to be placed in an SQL query (though hasn't been escaped for quotes).
	 *
	 * @since 0.5
	 *
	 * @see Dataface_Serializer::serialize()
	 *
	 */ 
	function fieldname__serialize($value);
	
	/**
	 * @brief Returns the default value for a specified field when new records are inserted.
	 *
	 * @return string The default value.
	 *
	 * @since 0.6
	 */
	function fieldname__default();
	
	/**
	 * @brief Provides a link to obtain more information about a field on the edit form.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return string The link
	 *
	 * @since 0.6
	 * @see Dataface_Record::getLink()
	 */
	function fieldname__link(Dataface_Record $record);
	
	/**
	 * @brief Retrieves the field value from  a form widget in a format that can be
	 *   inserted into a record using the Dataface_Record::setValue() method.
	 *
	 * @return mixed A value obtained from $el in a format that can be set in $record.
	 *
	 * @section Synopsis
	 *
	 * This method is the inverse of fieldname__pullValue().
	 *
	 * @since 0.6
	 *
	 * @see HTML_QuickForm
	 * @see fieldname__pullValue()
	 */
	function fieldname__pushValue(Dataface_Record $record, HTML_QuickForm_element $el);
	
	/**
	 * @brief Retrieves the value for a field from a record in a format that can be set in a form widget.
	 *
	 * @param Dataface_Record $record The record from which the field value is being pulled.
	 * @param HTML_QuickForm_element $el The form element for which the form value should be formatted.
	 * @return mixed The field value ready to be set as the value in $el.
	 *
	 * @since 0.6
	 *
	 * @see HTML_QuickForm
	 * @see fieldname__pushValue()
	 */
	function fieldname__pullValue(Dataface_Record $record, HTML_QuickForm_element $el);
	
	//@}
	
	// @{
	/** @name Calculated Fields */
	
	/**
	 * @brief Defines a calculated field that can be used just like any other field in the table.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return mixed The field value.  This may be any data type, including a data structure.
	 *
	 * @see Dataface_Record::getValue()
	 */
	function field__fieldname(Dataface_Record $record);
	// @}
	
	
	// @{
	/** @name Record Triggers */
	
	/**
	 * @brief Trigger called before a record is saved.
	 * 
	 * @param Dataface_Record $record The record being saved.
	 * @return mixed PEAR_Error object if there is a problem.
	 * @since 0.5
	 *
	 * @see Dataface_IO::write()
	 * @see Dataface_Record::save()
	 * @see afterSave()
	 */
	function beforeSave(Dataface_Record $record);
	
	/**
	 * @brief Trigger called after a record is saved.
	 * @param Dataface_Record $record The record that was saved.
	 * @return mixed PEAR_Error object If there is a problem.
	 * @since 0.5
	 *
	 * @see Dataface_IO::write()
	 * @see Dataface_Record::save()
	 * @see beforeSave()
	 */
	function afterSave(Dataface_Record $record);
	
	/**
	 * @brief Trigger called before a record is inserted into the database for the first time.
	 * @param Dataface_Record $record The record that is being inserted.
	 * @return mixed PEAR_Error object if there is a problem.
	 * @since 0.5
	 * 
	 * @see Dataface_IO::insert()
	 * @see Dataface_Record::save()
	 * @see afterInsert()
	 */
	function beforeInsert(Dataface_Record $record);
	
	/**
	 * @brief Trigger called after a record is inserted into the database for the first time.
	 * @param Dataface_Record $record The record that is being inserted.
	 * @return mixed PEAR_Error object if there is a problem.
	 *
	 * @since 0.5
	 * @see beforeInsert()
	 * @see Dataface_IO::insert()
	 * @see Dataface_Record::save()
	 */
	function afterInsert(Dataface_Record $record);
	
	/**
	 * @brief Trigger called before a record is updated.
	 * @param Dataface_Record $record
	 * @return mixed PEAR_Error object if there is a problem.
	 *
	 * @since 0.5
	 * @see afterUpdate()
	 * @see beforeInsert()
	 * @see beforeSave()
	 * @see Dataface_IO::update()
	 * @see Dataface_Record::save()
	 */
	function beforeUpdate(Dataface_Record $record);
	
	/**
	 * @brief Trigger called after a record is updated.
	 * @param Dataface_Record $record
	 * @return mixed PEAR_Error object if there is a problem.
	 * 
	 * @since 0.5
	 * @see beforeUpdate()
	 * @see afterInsert()
	 * @see afterSave()
	 * @see Dataface_Record::save()
	 * @see Dataface_IO::update()
	 */
	function afterUpdate(Dataface_Record $record);
	
	/**
	 * @brief Trigger called before a record is deleted.
	 * @param Dataface_Record $record The record that is being deleted.
	 * @return mixed PEAR_Error object if there is a problem.
	 *
	 * @since 1.0
	 *
	 * @see afterDelete()
	 * @see Dataface_Record::delete()
	 * @see Dataface_IO::delete()
	 *
	 */
	function beforeDelete(Dataface_Record $record);
	
	/**
	 * @brief Trigger called after a record is deleted.
	 *
	 * @param Dataface_Record $record The record that was deleted.
	 * @return mixed PEAR_Error object if there is a problem.
	 * @since 1.0
	 *
	 * @see beforeDelete()
	 * @see Dataface_IO::delete()
	 * @see Dataface_Record::delete()
	 */
	function afterDelete(Dataface_Record $record);
	
	/**
	 * @brief Trigger called before an "existing" related record is added to a relationship.
	 *
	 * @param Dataface_RelatedRecord $record The related record that is being added.
	 * @return mixed PEAR_Error object if there is a problem.
	 *
	 * @since 0.7
	 * @see afterAddExistingRelatedRecord()
	 * @see beforeAddNewRelatedRecord()
	 * @see beforeAddRelatedRecord()
	 * @see Dataface_IO::addExistingRelatedRecord()
	 */
	function beforeAddExistingRelatedRecord(Dataface_RelatedRecord $record);
	
	/**
	 * @brief Trigger called after an "existing" related record is added to a relationship.
	 *
	 * @param Dataface_RelatedRecord $record The related record that was added.
	 * @return mixed PEAR_Error object if there is a problem.
	 * 
	 * @since 0.7
	 * @see beforeAddExistingRelatedRecord()
	 * @see afterAddNewRelatedRecord()
	 * @see afterAddRelatedRecord()
	 * @see Dataface_IO::addExistingRelatedRecord()
	 */
	function afterAddExistingRelatedRecord(Dataface_RelatedRecord $record);
	
	/**
	 * @brief Trigger called before a "new" related record is added to a relationship.
	 * 
	 * @param Dataface_RelatedRecord $record The related record that is being added to a relationship.
	 * @return mixed PEAR_Error object if there is a problem.
	 * 
	 * @since 0.7
	 * @see afterAddNewRelatedRecord()
	 * @see beforeAddExistingRelatedRecord()
	 * @see beforeAddRelatedRecord()
	 * @see Dataface_IO::addRelatedRecord()
	 */
	function beforeAddNewRelatedRecord(Dataface_RelatedRecord $record);
	
	/**
	 * @brief Trigger called after a "new" related record is added to a relationship.
	 *
	 * @param Dataface_RelatedRecord $record The related record that was added.
	 * @return mixed PEAR_Error object if there is a problem.
	 * 
	 * @since 0.7
	 * @see beforeAddNewRelatedRecord()
	 * @see afterAddExistingRelatedRecord()
	 * @see afterAddRelatedRecord()
	 * @see Dataface_IO::addRelatedRecord()
	 */
	function afterAddNewRelatedRecord(Dataface_RelatedRecord $record);
	
	/**
	 * @brief Trigger called before a related record is added to a relationship (either new or existing).
	 * 
	 * @param Dataface_RelatedRecord $record The record that is being added.
	 * @return mixed PEAR_Error object if there is a problem.
	 * 
	 * @since 0.8
	 * @see afterAddRelatedRecord()
	 * @see beforeAddNewRelatedRecord()
	 * @see beforeAddExistingRelatedRecord()
	 * @see Dataface_IO::addRelatedRecord()
	 * @see Dataface_IO::addExistingRelatedRecord()
	 */
	function beforeAddRelatedRecord(Dataface_RelatedRecord $record);
	
	/**
	 * @brief Trigger called after a related record is added to a relationship (either new or existing).
	 * @return mixed PEAR_Error object if there is a problem.
	 *
	 * @since 0.7
	 *
	 * @see beforeAddRelatedRecord()
	 * @see afterAddExistingRelatedRecord()
	 * @see afterAddNewRelatedRecord()
	 * @see Dataface_IO::addRelatedRecord()
	 * @see Dataface_IO::addExistingRelatedRecord()
	 */
	function afterAddRelatedRecord(Dataface_RelatedRecord $record);
	
	/**
	 * @brief Trigger called after a related record is removed from a relationship.
	 *
	 * @param Dataface_RelatedRecord $record The related record that is being removed.
	 * @return mixed PEAR_Error object if there is a problem.
	 *
	 * @since 0.7
	 *
	 * @see afterRemoveRelatedRecord()
	 * @see Dataface_IO::removeRelatedRecord()
	 */
	function beforeRemoveRelatedRecord(Dataface_RelatedRecord $record);
	
	/**
	 * @brief Trigger called after a related record is removed from a relationship.
	 * 
	 * @param Dataface_RelatedRecord $record The related record that has been removed.
	 * @return mixed PEAR_Error object if there is a problem.
	 *
	 * @since 0.7
	 *
	 * @see beforeRemoveRelatedRecord()
	 * @see Dataface_IO::removeRelatedRecord()
	 */
	function afterRemoveRelatedRecord(Dataface_RelatedRecord $record);

	/**
	 * @brief Trigger called after a record is copied.
	 * @param Dataface_Record $original The original record.
	 * @param Dataface_Record $copy The copied record.
	 *
	 * @since 1.3
	 *
	 * @see Dataface_IO::copy()
	 *
	 */
	function afterCopy(Dataface_Record $original, Dataface_Record $copy);
	
	
	
	// @}
	
	
	// @{
	/** @name Action Triggers */
	
	/**
	 * @brief Trigger fired after the 'edit' action has completed successfully.
	 *
	 * @param array $params Some context parameters.  This includes a the key 'record' which refers to the Dataface_Record object that was just successfully edited and saved.
	 * @since 0.8
	 * @see dataface_actions_edit
	 *
	 */
	function after_action_edit($params=array());
	
	/**
	 * @brief Trigger fired after the 'new' action has completed successfully.
	 * @param array Associative array of context variables including 'record' which is the Dataface_Record that was just inserted.
	 *
	 * @since 0.8
	 * @see dataface_actions_new
	 */
	function after_action_new($params=array());
	
	/**
	 * @brief Trigger fired after the 'delete' action has completed successfully.
	 * @param array Associative array of context variables including 'record' which is the Dataface_Record that was just deleted.
	 * @since 1.0
	 * @see dataface_actions_delete
	 */
	function after_action_delete();

	// @}
	
	
	// @{
	/** @name Template Customization */
	
	/**
	 * @brief Fills a block or slot in a template when operating in the context of the delegate class's table.
	 * @param array $params Associative array of key-value pairs passed to the block containing context information.
	 * @return void This method does not return anything.   It should print or echo content to the output stream.
	 *
	 * @since 0.6
	 * @see Dataface_SkinTool
	 * @see df_display()
	 * @see Dataface_SkinTool::block()
	 * @see Dataface_SkinTool::display()
	 *
	 */
	function block__blockname(array $params=array());
	
	
	/**
	 * @brief Returns the name of an action that should be used as the target action of a search
	 * performed from the current context.  In past releases searches would always go to the list 
	 * action.  This gives you the ability to override this behavior with your own custom action
	 * depending on the circumstances.
	 *
	 * @param array $action The action definition to check (this would be the source action).
	 * @since 2.0
	 * @see Dataface_Application::getSearchTarget()
	 * @see ApplicationDelegateClass:getSearchTarget()
	 */
	function getSearchTarget(array $action);
	
	
	// @}
	
	
	// @{
	/** @name List Tab Cutomization */
	
    /**
     * @brief Returns a CSS class to be added to the table header cell (th tag) for a specified 
     * column of the table in list view.	
     *
     * @param string $colname The name of the table column.
     * @return string CSS classes (separated by spaces).
     * @since 2.0alpha2
     */
	function css__tableHeaderCellClass($colname);
	
	/**
	 * @brief Returns a CSS class to be added to the table row (tr tag) for the specified record
	 *  in list view.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return string CSS classes (separated by spaces).
	 * @since 1.2
	 */
	function css__tableRowClass(Dataface_Record $record);
	
	/**
	 * @brief Overrides the display of a table cell for a field in list view.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return string The string contents of the cell.
	 *
	 * @since 0.8
	 * @see http://xataface.com/documentation/how-to/list_tab
	 * @see renderRow()
	 */
	function fieldname__renderCell(Dataface_Record $record);
	
	/**
	 * @brief Overrides the display of a table row in list view.
	 *
	 * @param Dataface_Record $record The subject record.
	 *
	 * @return string The string row output (i.e. the tr tag and contents).
	 *
	 * @since 0.8
	 * @see http://xataface.com/documentation/how-to/list_tab
	 */
	function renderRow(Dataface_Record $record);
	
	/**
	 * @brief Overrides the headings row for the list view table.
	 *
	 * @return string The string row header (i.e. the tr tag and contents).
	 *
	 * @since 0.8
	 * @see http://xataface.com/documentation/how-to/list_tab
	 *
	 */
	function renderRowHeader(Dataface_Record $record);
	
	
	
	// @}
	
	//@{
	/** @name View Tab Customization */
	
	/**
	 * @brief Defines a new section on the view tab.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return array Data structure containing details about the section.  The data structure is:
	 *	@code
	 *  array(
	 *      name => <string>          // The name of the section
	 *      label => <string>         // The label of the section
	 *      content => <string>       // The section content
	 *      order => <number>         // Integer order of the section
	 *      url => <string>           // The URL to 'see all'
	 *      edit_url => <string>      // URL to edit the content in this section
	 *      records => <array(Dataface_Record)>  // List of records to display
	 *      display => <enum('expanded','collapsed')>   // Whether it should be default collapsed or expanded
	 *      class => <enum('main', 'left')>    // Whether do display in left column or main column.
	 *      fields => <array(string)>  // List of field names to display
	 * )
	 * @endcode
	 *
	 * @since 0.8
	 * @see http://xataface.com/wiki/How_to_Add_Custom_Sections_to_View_Tab
	 */
	function section__sectionname( Dataface_Record $record);
	// @}
	
	// @{
	/** @name Full-text Search */
	
	/**
	 * @brief Returns the indexable text to be used for the full-text site search feature.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return string The searchable text for the record.
	 * @since 1.0
	 */
	function getSearchableText(Dataface_Record $record);
	
	// @}
	
	
	// @{
	/** @name RSS Feed Customization */
	
	/**
	 * @brief Returns a data structure with the contents to be included in the RSS feed.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return array A data structure with details in the following format:
	 * @code
	 * array(
	 *     title => <string>                    // The title of the post
	 *     description => <string>              // The body of the post
	 *     link => <string>                     // The link to the original post
	 *     date => <long>                       // Unix timestamp marking the date of the post
	 *                                          // This is generally the last modified date
	 *     author => <string>                   // The author of this post
	 *     source => <string>                   // The source URL where the feed originated.
	 * )
	 * @endcode
	 *
	 * Note that if keys are omitted from this array, default values will be used by Xataface
	 * when building the RSS feed.  In fact you could even return an empty array from this
	 * method in which case Xataface would fill in all of the values itself.
	 *
	 * @section defaults Default Values
	 *
	 * The following table shows where Xataface will pull default values if keys are omitted
	 * from the array that is returned from this method.
	 *
	 * <table>
	 * 	<tr>
	 *		<th>Key</th>
	 *		<th>Default Value</th>
	 * 	</tr>
	 *	<tr>
	 *		<td>title</td>
	 *		<td>Dataface_Record::getTitle()</td>
	 *	</tr>
	 *	<tr>
	 *		<td>description</td>
	 *		<td>getRSSDescription() if defined.  Otherwise it will display an HTML table showing all data in the record (subject to permissions).</td>
	 *	</tr>
	 *	<tr>
	 *		<td>link</td>
	 *		<td>Dataface_Record::getPublicLink()</td>
	 *	</tr>
	 *	<tr>
	 *		<td>date</td>
	 *		<td>
	 *			<ol>
	 *				<li>Dataface_Record::getLastModified() </li>
	 *				<li>Dataface_Record::getCreated() </li>
	 *			</ol>
	 *		</td>
	 *	</tr>
	 *	<tr>
	 *		<td>author</td>
	 *		<td>
	 *			<ol>
	 *				<li>Dataface_Record::getCreator()</li>
	 *				<li><em>default_author</em> of the <em>[_feed]</em> section of the <a href="http://xataface.com/wiki/conf.ini_file">conf.ini file</a>.</li>
	 *			</ol>
	 *		</td>
	 *	</tr>
	 *	<tr>
	 *		<td>source</td>
	 *		<td>
	 *			<ol>
	 *				<li>getFeedSource()</li>
	 *				<li><em>source</em> of the <em>[_feed]</em> section of the <a href="http://xataface.com/wiki/conf.ini_file">conf.ini file</a>.</li>
	 *			</ol>
	 *		</td>
	 *	</tr>
	 * </table>
	 *
	 * @since 0.8
	 * @see getFeed()
	 * @see http://xataface.com/wiki/getFeed
	 * @see http://xataface.com/wiki/getFeedItem
	 * @see http://xataface.com/wiki/Introduction_to_RSS_Feeds_in_Xataface
	 * @see getRSSDescription()
	 */
	function getFeedItem(Dataface_Record $record);
	
	/**
	 * @brief Returns data structure with settings for the RSS feed as a whole.
	 *
	 * @param array $query The query parameters for the request.
	 * @return array A data structure that is a subset of the following array format:
	 * @code
	 * array(
	 *     title => <string>             // The title of the RSS feed
	 *     description => <string>       // Description of the RSS feed
	 *     link => <string>              // The URL to the original feed content
	 *     syndicationURL => <string>    // Link to source page of RSS feed (same as link)
	 * )
	 * @endcode
	 *
	 * @section defaults Default Values
	 *
	 * If elements are omitted from the data structure that is returned from this method,
	 * Xataface will use default values for each field.  The following table describes the
	 * values that Xataface will use as defaults in this case:
	 *
	 * <table>
	 * 	<tr>
	 *		<th>Key</th>
	 *		<th>Default Value</th>
	 *	</tr>
	 *	<tr>
	 *		<td>title</td>
	 *		<td>
	 *			<ol>
	 *				<li><em>title</em> directive of the <em>[_feed]</em> section of the <a href="http://xataface.com/wiki/conf.ini_file">conf.ini file</a>.</li>
	 *				<li>An auto-generated title based on the current query.</li>
	 *			</ol>
	 *		</td>
	 *	</tr>
	 *	<tr>
	 *		<td>description</td>
	 *		<td><em>description</em> directive of the <em>[_feed]</em> section of the <a href="http://xataface.com/wiki/conf.ini_file">conf.ini file</a>.</td>
	 *	</tr>
	 *	<tr>
	 *		<td>link</td>
	 *		<td>
	 *			<ol>
	 *				<li><em>link</em> directive of the <em>[_feed]</em> section of the <a href="http://xataface.com/wiki/conf.ini_file">conf.ini file</a>.</li>
	 *				<li>The URL to <em>list</em> view of the application with the same result set as the current feed.</li>
	 *			</ol>
	 *		</td>
	 *	</tr>
	 *	<tr>
	 *		<td>syndicationURL</td>
	 *		<td>
	 *			<ol>
	 *				<li><em>syndicationURL</em> directive of the <em>[_feed]</em> section of the <a href="http://xataface.com/wiki/conf.ini_file">conf.ini file</a>.</li>
	 *				<li>The URL to <em>list</em> view of the application with the same result set as the current feed.</li>
	 *			</ol>
	 *		</td>
	 *	</tr>
	 * </table>
	 *
	 * @since 0.8
	 * @see http://xataface.com/wiki/getFeed
	 * @see http://xataface.com/wiki/Introduction_to_RSS_Feeds_in_Xataface
	 */
	function getFeed(array $query);
	
	/**
	 * @brief Returns the source URL for the RSS feed that is generated by the given query.
	 *
	 * @param array $query The query parameters that produced the feed.
	 * @return string The URL to the source webpage for the RSS feed.
	 *
	 * @section Synopsis
	 *
	 * This method will override any information returned by the getFeed() method with
	 * respect to the source of the feed.
	 *
	 * @since 0.8
	 * @see http://xataface.com/wiki/Introduction_to_RSS_Feeds_in_Xataface
	 * @see http://xataface.com/wiki/getFeed
	 * @see getFeed()
	 *
	 */ 
	function getFeedSource(array $query);
	
	/**
	 * @brief Returns details about a feed of related records.  This overrides the feed details for related feeds.
	 *
	 * @param Dataface_Record $record The subject record (the parent of the related records).
	 * @param string $relationship The name of the relationship.
	 * @return array A data structure that is a subset of the following array format:
	 * @code
	 * array(
	 *     title => <string>             // The title of the RSS feed
	 *     description => <string>       // Description of the RSS feed
	 *     link => <string>              // The URL to the original feed content
	 *     syndicationURL => <string>    // Link to source page of RSS feed (same as link)
	 * )
	 * @endcode
	 *
	 * @section defaults Default Values
	 *
	 * If elements are omitted from the data structure that is returned from this method,
	 * Xataface will use default values for each field.  The following table describes the
	 * values that Xataface will use as defaults in this case:
	 *
	 * <table>
	 * 	<tr>
	 *		<th>Key</th>
	 *		<th>Default Value</th>
	 *	</tr>
	 *	<tr>
	 *		<td>title</td>
	 *		<td>An auto-generated title.  Generally {relationship_name} of Dataface_Record::getTitle()</td>
	 *	</tr>
	 *	<tr>
	 *		<td>description</td>
	 *		<td>Auto-generated title.  Something like: Related Records of Dataface_Record::getTitel()</td>
	 *	</tr>
	 *	<tr>
	 *		<td>link</td>
	 *		<td>The URL to the related list of this feed.</td>
	 *	</tr>
	 *	<tr>
	 *		<td>syndicationURL</td>
	 *		<td>The URL to the related list of this feed.</td>
	 *	</tr>
	 * </table>
	 *
	 * @since 0.8
	 * @see http://xataface.com/wiki/Introduction_to_RSS_Feeds_in_Xataface
	 * @see getFeed()
	 */
	function getRelatedFeed(Dataface_Record $record, $relationship);
	
	/**
	 * @brief Overrides the description or body of a record as it is displayed in an RSS feed.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return string The HTML content of the RSS feed post.
	 *
	 * @section Synopsis
	 *
	 * The default behavior of the Xataface RSS feed is to layout all permitted fields in
	 * an HTML table and include that table in the RSS feed.  If you want to customize this 
	 * behavior you can implement this method to return exactly what you want to appear
	 * in the body of the RSS feed item.
	 *
	 * @since 1.0
	 * @see getFeedItem()
	 * @see http://xataface.com/wiki/Introduction_to_RSS_Feeds_in_Xataface
	 */
	function getRSSDescription(Dataface_Record $record);
	// @}
	
	
	// @{
	/** @name XML Output Customization */
	
	/**
	 * @brief Overrides the XML output of a record as it would appear in Xataface's export_xml action.
	 *
	 * @param Dataface_Record $record The subject record.
	 * @return string XML representation of the record.
	 *
	 * @since 0.8
	 * @see Dataface_XMLTool
	 * @see Dataface_XMLTool_default
	 * @see dataface_actions_export_xml
	 */
	function toXML(Dataface_Record $record);
	
	/**
	 * @brief Returns XML content to be included at the beginning of the XML representation of this record.
	 * 
	 * @param Dataface_Record $record The subject record.
	 * @return string XML content to be included at the beginning of the XML representation of $record.
	 *
	 * @since 1.3
	 * 
	 * @attention toXML() supercedes this method if both are implemented in the delegate class.
	 * @see Dataface_XMLTool
	 * @see Dataface_XMLTool_default
	 * @see dataface_actions_export_xml
	 */
	function getXMLHead(Dataface_Record $record);
	
	/**
	 * @brief Returns XML content to be included at the end of the XML representation of this record.
	 * 
	 * @param Dataface_Record $record The subject record.
	 * @return string XML content to be included at the end of the XML representation of $record.
	 *
	 * @since 1.3
	 * 
	 * @attention toXML() supercedes this method if both are implemented in the delegate class.
	 * @see Dataface_XMLTool
	 * @see Dataface_XMLTool_default
	 * @see dataface_actions_export_xml
	 */
	function xmlTail(Dataface_Record $record);
	
	
	// @}
	
	
	// @{
	/** @name Valuelist Customization */
	
	/**
	 * @brief Defines a valuelist on the table.
	 *
	 * @return array Associative array serving as a valuelist where the keys are the ids and the values are the values.
	 *
	 * @since 0.6
	 * @see Dataface_ValuelistTool
	 * @see Dataface_Table::getValuelist()
	 * @see http://www.xataface.com/wiki/valuelists.ini_file
	 * @see http://xataface.com/documentation/tutorial/getting_started/valuelists
	 */
	function valuelist__valuelistname();
	
	// @}
	
	// @{
	/** @name Importing Records */
	
	/**
	 * @brief Defines an import filter that can be used to import records into the table.
	 *
	 * @param string $data The input data that the user has uploaded.  This may be in any format - you need to parse it.
	 * @param array $defaults The default values that should be inserted into the fields of the newly inserted records if 
	 *   the import doesn't explicitly specify the values to insert.
	 * @return array An array of Dataface_Record objects to be inserted.
	 *
	 * @since 0.6
	 * @see http://xataface.com/documentation/how-to/import_filters
	 * @see Dataface_IO::importData()
	 */
	function __import__filtername($data, array $defaults);
	
	// @}

	// @{
	/** @name Table Settings */
	
	/**
	 * @brief Defines an SQL query that should be used for loading data from this table.
	 *
	 * @return string The SQL query that should be used for loading data from this table.
	 *
	 * @since 0.8
	 * @see http://www.xataface.com/wiki/sql_delegate_method
	 */
	function __sql__();
	// @}
	
	
	// @{
	/** @name Form Validation */
	
	/**
	 * @brief Validates form input for a field
	 *
	 * @param Dataface_Record $record The record encapsulating the record we are validating. Note that the values of this object correspond with the submitted values from the form, and not necessarily the actual values of the record in the database.
	 * @param string $value The submitted value that is being validated.
	 * @param array &$params An output array to pass meta-information out in case of failure. This allows only one possible key to be set: 'message'.
	 *
	 * @return boolean True if validation succeeds (i.e. $value is a valid input).  False otherwise.
	 *
	 * @section Examples
	 *
	 * @code
	 * function myfield__validate(&$record, $value, &$params){
     *     if ( $value != 'Steve' ){
     *         $params['message'] = 'Sorry you must enter "Steve"';
     *         return false;
     *     }
     *     return true;
     * }
	 * @endcode
	 * @since 0.6
	 * @see http://xataface.com/wiki/fieldname__validate
	 */
	function fieldname__validate(Dataface_Record $record, $value, array &$params);
	// @}


}
