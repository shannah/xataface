<?php
/**
 * @brief An interface to document the methods that can be implemented in the Application
 * delegate class.  This is not a *real* interface.  It is merely used for documentation
 * purposes.
 *
 * @section application_delegate_class_synopsis Synopsis
 *
 * Every Xataface project may define an Application Delegate class that helps to 
 * customize the behavior of the app.  It may be used to define permissions that
 * are granted to users (e.g. getPermissions()), or to customize parts of the 
 * interface (e.g. block__blockname()), or to affect the flow of control (e.g.
 * beforeHandleRequest()).  Although it is not required to implement an Application
 * Delegate Class for your application, it is hard to imagine a very powerful application
 * not having one.
 *
 * @see <a href="http://xataface.com/wiki/Application_Delegate_Class">The Application Delegage Class Wiki Page</a> for 
 *	legacy documentation and examples.
 *
 * @par Application Delegate Class vs the Table Delegate Class
 * Xataface also supports table delegate classes that include some of the same 
 * methods as the application delegate class.  The difference is that table delegate
 * classes customize only aspects of the application relating to a single table
 * (the table that the delegate class is associated with).  For more information about table delegate classes
 * Check out the following links:
 * @see DelegateClass - API docs for the Table delegate class interface.
 * @see <a href="http://xataface.com/wiki/Delegate_class_methods">Delegate Class Wiki Page</a> for legacy documentation of delegate class methods.
 * @see <a href="http://xataface.com/documentation/tutorial/getting_started/delegate_classes">Delegate classes</a> section of the getting started tutorial for an introduction to delegate classes.
 * 
 *
 * @par Example Basic Application Delegate Class:
 * File %APPLICATION_PATH%/conf/ApplicationDelegate.php:
 * @code
 * <?php
 * class conf_ApplicationDelegate {
 *      public function getPermissions($record){
 *          $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
 *          if ( $user and $user->val('role') == 'ADMIN' ){
 *              return Dataface_PermissionsTool::ALL();
 *          } else {
 *              return Dataface_PermissionsTool::NO_ACCESS();
 *          }
 *      }
 *
 *      // Hook called before the request....
 *
 *      public function beforeHandleRequest(){
 *          $app = Dataface_Application::getInstance();
 *          $query =& $app->getQuery();
 *          if ( $query['-table'] == 'dashboard' and $app->_conf['using_default_action'] ){
 *              // If we're in the dashboard table and no action is explicitly specified
 *              // then we will set the action to 'dashboard'
 *              $query['-action'] = 'dashboard';
 *          }
 *      }
 *  }
 * @endcode
 * @note The above sample demonstrates just two of the available methods that
 * can be implemented in the Application Delegate Class.  There are many more.
 *
 * @see beforeHandleRequest() For details on the beforeHandleRequest method.
 * @see getPermissions() for details of the getPermissions method.
 * 
 * 
 */
interface ApplicationDelegateClass {
	
	
	// @{
	/** @name Permissions */
	
	/**
	 * @brief Returns associative array of permissions that should be granted 
	 *  to the current user on the specified record.
	 * 
	 * @param Dataface_Record $record The record on which we are granting permissions.
	 * @return array Associative array of permissions that are granted.  The keys of 
	 *   this array are the names of permissions (defined in the permissions.ini file)
	 * and values are boolean (0 or 1) to indicate whether or not the permission is granted.  
	 * This method may also return null to indicate that it has no opinion on the permissions
	 * to use - i.e. it will default to the permissions defined in the ApplicationDelegateClass.
	 *
	 * @since 0.5
	 *
	 *
	 * @section Flowchart
	 *
	 *
	 * The following flowchart shows the flow of control Xataface uses to determine the
	 * record-level permissions for a record.  (<a href="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png" target="_blank">click here to enlarge</a>):
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png?max_width=640"/>
	 * 
	 *
	 * @see http://xataface.com/documentation/tutorial/getting_started/permissions
	 * @see DelegateClass::getPermissions()
	 * @see getRoles()
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
	 *  @attention Note that the results of this method are always superceded by
	 *  the results of getPermissions() if defined.
	 *
	 *
	 * @section Flowchart
	 *
	 *
	 * The following flowchart shows the flow of control Xataface uses to determine the
	 * record-level permissions for a record.  (<a href="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png" target="_blank">click here to enlarge</a>):
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png?max_width=640"/>
	 * 
	 * @see getPermissions()
	 * @see Dataface_Record::getRoles()
	 * @see http://xataface.com/documentation/tutorial/getting_started/permissions
	 * @see Dataface_PermissionsTool
	 * @see http://xataface.com/wiki/permissions.ini_file
	 * @see DelegateClass::getRoles()
	 */
	function getRoles(Dataface_Record $record);
	
	// @}
	
	// @{
	/** @name Triggers */
	
	/**
	 * @brief Trigger executed after a user account is activated.
	 * @returns void
	 */
	function after_action_activate();
	
	/**
	 * @brief Trigger executed after a user logs in.
	 * @returns void
	 */
	function after_action_login();
	
	/**
	 * @brief Trigger executed after a user logs out.
	 * @returns void
	 */
	function after_action_logout();
	
	/**
	 * @brief Trigger executed after succesfully editing a record.  This is called
	 * after the @p edit action is complete in constrast to the afterSave() method
	 * that is called after a record is saved.  The key difference is that afterSave()
	 * may be called many times per request (as many records may be changed and saved
	 * in a request). after_action_edit(), on the other hand is only called once
	 * after the edit form has been successfully saved.
	 *
	 * @par Typical Uses
	 * This trigger is often used to redirect the user to a page other than the default
	 * after he finishes editing a record.
	 */
	function after_action_edit();
	
	/**
	 * @brief Trigger executed after successfully inserting a new record through the
	 * @p new action.  This is called after the @p new action is complete in contrast to the
	 * afterInsert() method that is called after a record is inserted.  The key difference
	 * is that afterInsert() may be called multiple times per request, since many records
	 * may be inserted in a single request.  The after_action_new() trigger, on the other
	 * hand, is only called once after the new form has been successfully saved.
	 *
	 * @par Typical Uses
	 * This trigger is often used to rediret the user to a page other than the default
	 * after he finishes adding a new record.
	 *
	 * @returns void
	 */
	function after_action_new();
	
	/**
	 * @brief Trigger executed after successfully deleting a record through the UI.
	 * @returns void
	 */
	function after_action_delete();
	
	/**
	 * @brief Trigger executed before the authentication step occurs in each request.
	 * This method may be handy for changing the authentication type at the last minute
	 * depending on various factors.
	 *
	 * @returns void
	 *
	 * @see <a href="http://xataface.com/wiki/before_authenticate">before_authenticate wiki page</a> 
	 * for legacy documentation of this method.
	 *
	 */
	function before_authenticate();
	
	/**
	 * @brief Trigger executed before each request (but after the autehntication step). It
	 * is often useful to implement this method to provide desirable default behavior for 
	 * a request.  
	 *
	 * This may be used for such things as:
	 * - Redirecting the user to a different page if certain requirements aren't met.
	 * - Specifying a default sort for the records of a table.
	 *
	 * @returns void
	 *
	 * @see <a href="http://xataface.com/wiki/beforeHandleRequest">beforeHandleRequest Wiki Page</a> for 
	 *	legacy documentation of this method.
	 */
	function beforeHandleRequest();
	
	/**
	 * @brief Overrides the code to start sessions.  If this is implemented it will
	 * override how xataface starts sessions.  This may be useful if you have your own
	 * custom session handler.
	 * @returns void
	 */
	function startSession();
	// @}
	
	
	// @{
	/** @name Preferences */
	
	/**
	 * @brief Returns the preferences to assign the current user.  This is run after
	 * the authentication step on each request (but before the beforeHandleRequest() 
	 * method).
	 *
	 * @see <a href="http://xataface.com/wiki/preferences">Preferences</a> - for information about
	 * Xataface preferences.
	 *
	 * @returns array(string=>boolean)
	 *
	 * @par Example:
	 * @code
	 * function getPreferences(){
     *      return array('hide_update'=>1, 'hide_posted_by'=>1);
     * }
     * @endcode
	 */
	function getPreferences();
	// @}
	
	
	// @{
	/** @name Registration  */
	
	/**
	 * @brief A trigger executed before the registration form is saved. This can be used to 
	 * perform some custom actions like emailing the administrator.
	 *
	 * @param Dataface_Record $user A Dataface_Record object encapsulating the record 
	 * 	that is being inserted in the users table for this registration.
	 * @returns mixed  If this method returns a PEAR_Error object, then registration
	 *  will fail with an error.
	 *
	 * @par Example:
	 * @code
	 * <?php
     * class conf_ApplicationDelegate {
     *    function beforeRegister(&$record){
     *        // mail the admin to let him know that the registration is occurring.
     *        mail('admin@example.com', 'New registration', 'A new user '.$record->val('username').' has registered);
     *    }
     * }
     * @endcode
     *
     * @see <a href="http://xataface.com/wiki/beforeRegister">beforeRegister wiki page.</a>
     * @see afterRegister()
     *
     */
	function beforeRegister(Dataface_Record $user);
	
	
	/**
	 * @brief A trigger that is executed after the registration form is saved. This can be
	 * used to perform some custom actions like emailing the administrator.
	 *
	 * @param Dataface_Record $user A Dataface_Record object encapsulating the record 
	 * that is being inserted in the users table for this registration.
	 * @returns mixed If this method returns a PEAR_Error object, then registration will fail with an error.
	 *
	 * @par Example:
	 * @code
	 * <?php
     * class conf_ApplicationDelegate {
     *    function afterRegister(&$record){
     *        // mail the admin to let him know that the registration is occurring.
     *        mail('admin@example.com', 'New registration', 'A new user '.$record->val('username').' has registered);
     *    }
     * }
     * @endcode
     *
     * @see <a href="http://xataface.com/wiki/afterRegister">afterRegister Wiki Page</a>
     * @see beforeRegister()
     */
	function afterRegister(Dataface_Record $user);
	
	/**
	 * @brief A hook that validates the input into the user registration form to make sure that the 
	 * input is valid.
	 *
	 * @param array $values An associative array of the input values of the registration form.
	 * @returns mixed  If this method returns a PEAR_Error object then the validation will fail - 
	 *  and the user will be asked to correct his input.
	 * 
	 * @par Example:
	 * @code
	 * <?php
     * class conf_ApplicationDelegate {
     *    function validateRegistrationForm($values){
     *        if ( $values['age'] < 18 ){
     *            return PEAR::raiseError("Sorry you must be at least 18 years old to join this site.");
     *        }
     *        return true;
     *    }
     * }
     * @endcode
     *
     * @par Validation via the Users table Delegate Class
     * Note that since the registration form is just a "new record form" for the users table, 
     * it is also possible (and preferred) to do validation through the users table delegate class.
     *
     * @see <a href="http://xataface.com/wiki/validateRegistrationForm">validateRegistrationForm Wiki Page</a>
     */
	function validateRegistrationForm(array $values);
	
	/**
	 * @brief A hook that can be implemented to override the sending of an activation email to 
	 * the user.
	 *
	 * @param Dataface_Record $user A Dataface_Record object encapsulating the record that is being 
	 * inserted in the users table for this registration.
	 * @param string $activationURL The URL where the user can go to activate their account.
	 * @returns If this method returns a PEAR_Error object, then registration will fail with an error.
	 *
	 * @par Example
	 * @code
	 * <?php
     * class conf_ApplicationDelegate {
     *
     *    function sendRegistrationActivationEmail(&$record, $activationURL){
     *        // mail the admin to let him know that the registration is occurring.
     *        $username = $record->val('username');
     *        $email = $record->val('email');
     *        
     *        mail($email, 'Welcome to the team', 
     *            'Welcome '.$record->val('username').
     *            '.  You have been successfully registered.  
     *             Please visit '.$activationURL.' to activate your account'
     *        );
     *    }
     * }
     * @endcode
     *
     * @see <a href="http://xataface.com/wiki/sendRegistrationActivationEmail">sendRegistrationActivationEmail Wiki Page</a>
     */
	function sendRegistrationActivationEmail(Dataface_Record $user, $activationURL);
	
	/**
	 * @brief A hook that can be implemented to override the default information that is used to send 
	 * the registration activation email (the email that the user receives when they register).
	 *
	 * @param Dataface_Record $user A Dataface_Record object encapsulating the record that is being 
	 * inserted in the users table for this registration.
	 * @param string $activationURL The URL where the user can go to activate their account.
	 * @returns mixed If this method returns a PEAR_Error object, then registration will fail with an error.
	 *
	 * @par Example
	 * @code
	 * <?php
     * class conf_ApplicationDelegate {
     *
     *    function getRegistrationActivationEmailInfo(&$record, $activationURL){
     *        return array(
     *            'subject' => 'Welcome to the site.. Activation required',
     *            'message' => 'Thanks for registering.  Visit '.$activationURL.' to activate your account',
     *            'headers' => 'From: webmaster@example.com' . "\r\n" .
     *                          'Reply-To: webmaster@example.com' . "\r\n" .
     *                          'X-Mailer: PHP/' . phpversion()
     *             );
     *            
     *        
     *       
     *    }
     * }
     * @endcode
     *
     * @par Example 2: Only Override Subject
     * @code
     * <?php
     * class conf_ApplicationDelegate {
     *
     *    function getRegistrationActivationEmailInfo(&$record, $activationURL){
     *        return array(
     *            'subject' => 'Welcome to the site.. Activation required'
     *             ); 
     *    }
     * }
     * @endcode
     *
     * @see <a href="http://xataface.com/wiki/getRegistrationActivationEmailInfo">getRegistrationActivationEmailInfo Wiki Page</a>
     */
	function getRegistrationActivationEmailInfo(Dataface_Record $user, $activationURL);
	
	/**
	 * @brief A hook that can be implemented to override the default registration activation email 
	 * subject line (the email that the user receives when they register).
	 *
	 * @param Dataface_Record $user A Dataface_Record object encapsulating the record that is being inserted in the users table for this registration.
	 * @param string $activationURL The URL where the user can go to activate their account.
	 * @returns mixed If this method returns a PEAR_Error object, then registration will fail with an error.
	 *
	 * @par Example
	 * @code
	 * <?php
     *class conf_ApplicationDelegate {
     *
     *	function getRegistrationActivationEmailInfo(&$record, $activationURL){
     *		reeturn 'Welcome to the site.. Activation required';   
     *	}
     *}
     * @endcode
     *
     * @see <a href="http://xataface.com/wiki/getRegistrationActivationEmailSubject">getRegistrationActivationEmailSubject Wiki Page</a>
     * 
     */
	function getRegistrationActivationEmailSubject(Dataface_Record $user, $activationURL);
	
	
	/**
	 * @brief A hook that can be implemented to override the default registration activation email message body (the email that the user receives when they register).
	 * 
	 * @param Dataface_Record $user A Dataface_Record object encapsulating the record that is being inserted in the users table for this registration.
	 * @param string $activationURL The URL where the user can go to activate their account.
	 * @returns mixed If this method returns a PEAR_Error object, then registration will fail with an error.
	 *
	 * @par Example
	 * @code
	 * <?php
     * class conf_ApplicationDelegate {
     *
     *    function getRegistrationActivationEmailInfo(&$record, $activationURL){
     *        return 'Thanks for registering.  Please visit '.$activationURL.' to activate.';   
     *    }
     * }
     * @endcode
     * 
     * @see <a href="http://xataface.com/wiki/getRegistrationActivationEmailMessage">getRegistrationActivationEmailMessage Wiki Page</a>
     */
	function getRegistrationActivationEmailMessage(Dataface_Record $user, $activationURL);
	
	/**
	 * @brief a hooke that can be implemented to override the default registration activation email parameters (the email the user receives when they register).
	 * @param Dataface_Record $user The record that is being inserted into the users table.
	 * @param string $activationURL The URL where the user can go to activate their account.
	 * @returns mixed If this method returns a PEAR_Error object, then registration will fail with an error.
	 */
	function getRegistrationActivationEmailParameters(Dataface_Record $user, $activationURL);
	
	/**
	 * @brief A hook that can be implemented to override the default registration activation email headers.
	 * @param Dataface_Record $user The record that is being inserted into the users table.
	 * @param string $activationURL The url where the user can go to activate their account.
	 * @returns mixed If this method returns a PEAR_Error object, then registration will fail with an error.
	 */
	function getRegistrationActivationEmailHeaders(Dataface_Record $user, $activationURL);
	
	// @}
	
	// @{
	/** @name Forgot Password */
	
        /**
         * @brief Optional method to generate a temporary password for a user. This is used
         * by the forgot_password action and will override the default implementation.  It 
         * is important to allow for systems that have password restrictions.
         * 
         * @return String A random temporary password.
         * @since 2.0.2
         * 
         * @par Example
         * @code
         * function generateTemporaryPassword(){
         *     return 'my temp password';
         * }
         * @endcode
         */
        function generateTemporaryPassword();
        
	/**
	 * @brief Optional method to define the settings for the email that is sent to the user upon successful resetting of their password using the password reset function.
	 * @since 1.3
	 *
	 * @param Dataface_Record $user The Dataface_Record of the user whose password has been changed.
	 * @param string $password The new temporary password that has been assigned to the user.
	 * @returns array This method should return an associative array with 0 or more of the following keys:
	 * - @p subject - The subject line of the email.	
	 * - @p message - The message content of the email.
	 * - @p headers - The Email headers (as a string).
	 * - @p parameters - Extra parameters for the mail function.
	 *
	 * @see <a href="http://xataface.com/wiki/getPasswordChangedEmailInfo">getPasswordChangedEmailInfo Wiki Page</a>
	 * @see getResetPasswordEmailInfo()
	 *
	 */
	function getPasswordChangedEmailInfo(Dataface_Record $user, $password);
	
	/**
	 * @brief Optional method to define the settings for the email that is sent to the user when they request to reset their password.
	 * @since 1.3
	 *
	 * @param Dataface_Record $user The Dataface_Record of the user whose password has been changed.
	 * @param string $reset_url The URL where the user should go to reset their password. When they visit this URL they will receive a message saying that their password has been changed and the new password has been emailed to them. That subsequent email can be customized using the getPasswordChangedEmailInfo() method.
	 * @returns array This method should return an associative array with 0 or more of the following keys:
     * - @p subject - The subject line of the email.
     * - @p message - The message content of the email.
     * - @p headers - The Email headers (as a string).
     * - @p parameters - Extra parameters for the mail function.
     *
     * @see <a href="http://xataface.com/wiki/getResetPasswordEmailInfo">getResetPasswordEmailInfo Wiki Page</a>
     * @see getPasswordChangedEmailInfo()
     */
	function getResetPasswordEmailInfo(Dataface_Record $user, $reset_url);
	
	// @}
	
	// @{
	/** @name RSS Feed Customization */
	
	/**
	 * @brief Returns an associative array of parameters to configure the RSS feed for a particular table . 
	 *
	 * @param array $query The HTTP query. Contains information like the current table, current action, and search parameters. This allows you to customize your RSS feed depending on the user's query parameters.
	 * @returns array Returns an associative array with the components of the RSS feed. This array does not need to contain all possible keys, or even any keys. Any keys that are omitted will simply use default values in the RSS feed. The array may contain the following keys:
	 * - @p title - The title for the RSS feed. If this omitted, it will try to use the title directive of the [_feed] section of the conf.ini file. Failing that, it will try to generate an appropriate title for the feed depending on the current query.  (since 1.0)
	 * - @p description -  A Description for this RSS feed. If this is omitted, it will try to use the description directive of the [_feed] section of the conf.ini file. Since 1.0
	 * - @p link -	 A link to the source page of the RSS feed. If this is omitted, it will try to use the link directive of the [_feed] section of the conf.ini file.	 Since 1.0
	 * - @p syndicationURL- A link to the source page of the RSS feed. If this is omitted, it will try to use the syndicationURL directive of the [_feed] section of the conf.ini file.	Since 1.0
	 *
     * 
     * @par Example
     * @code
     * function getFeed(&$query){
     *    return array(
     *        'title' => "RSS feed for the ".$query['-table']." table.",
     *        'description' => "News and updates for automobiles",
     *        'link' => df_absolute_url(DATAFACE_SITE_HREF),
     *        'syndicationURL' => df_absolute_url(DATAFACE_SITE_HREF)
     *    );
     * }
     * @endcode
     * @note RSS feeds will work perfectly well without defining this method. This just allows you to customize one or more parameters of the RSS feed.
     *
	 * @see DelegateClass::getFeed()
	 * @see DelegateClass:getFeedItem()
	 * @see <a href="http://xataface.com/wiki/getFeed">getFeed Wiki Page</a>
	 * @see <a href="http://xataface.com/wiki/Introduction_to_RSS_Feeds_in_Xataface">Introduction to RSS Feeds in Xataface</a>
	 * 
	 */
	function getFeed(array $query);
	
	/**
	 * @brief Returns an associative array of parameters to configure the RSS feed for the related records of a 
	 * particular parent record.
	 *
	 * @param Dataface_Record $record The parent record whose relationship is being published as an RSS feed.
	 * @param string $relationship The name of the relationship whose records are being published.
	 * * @returns array Returns an associative array with the components of the RSS feed. This array does not need to contain all possible keys, or even any keys. Any keys that are omitted will simply use default values in the RSS feed. The array may contain the following keys:
	 * - @p title - The title for the RSS feed. If this omitted, it will try to use the title directive of the [_feed] section of the conf.ini file. Failing that, it will try to generate an appropriate title for the feed depending on the current query.  (since 1.0)
	 * - @p description -  A Description for this RSS feed. If this is omitted, it will try to use the description directive of the [_feed] section of the conf.ini file. Since 1.0
	 * - @p link -	 A link to the source page of the RSS feed. If this is omitted, it will try to use the link directive of the [_feed] section of the conf.ini file.	 Since 1.0
	 * - @p syndicationURL- A link to the source page of the RSS feed. If this is omitted, it will try to use the syndicationURL directive of the [_feed] section of the conf.ini file.	Since 1.0
	 *
	 * @see DelegateClass::getRelatedFeed() - This method is superceded by the table's delegate 
	 * class implementation of getRelatedFeed() if it is defined.
	 * @see getFeed()
	 */
	function getRelatedFeed(Dataface_Record $record, $relationship);
	
	
	// @}
	
	// @{
	/** @name Template Customization */
	
	/**
	 * @brief Inserts content into a particular block or slot of a template in Xataface.
	 *
	 * @see <a href="http://xataface.com/documentation/tutorial/getting_started/changing-look-and-feel">Changing Xataface Look and Feel</a>
	 *
	 * @par Example
	 * @code
	 * function block__before_header(){
     *   echo "<h1>Hello World</h1>";
     * }
     * @endcode
     * This would print "Hello World" at the top of each page as follows: <img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-28_at_1.01.49_PM.png?max_width=640"/>
     *
     * @par Listing All Blocks
     * You can list the blocks and slots in Xataface by enabling debug mode in your application.  You can do this by adding:
     * @code
     * debug=1
     * @endcode
     * to the beginning of your application's conf.ini file, then loading your application in a web browser.
     * Blocks and slots will be printed out with the interface.  You can then easily insert content
     * into these blocks by imlementing the appropriate named @p block__blockname() method in
     * either your application delegate class or your table delegate class.
     *
     * @see <a href="http://xataface.com/wiki/block__blockname">A Full List of Slots and blocks In Xataface.
     * @see DelegateClass::block__blockname() - For blocks that only affect a particular table.
     * 
     */
	function block__blockname();
	
	/**
	 * @brief The getNavItem() method of the application delegate class can be used to override the items that appear in the navigation menu (i.e. the menu that allows users to select the table via either tabs along the top or items along the side). It should return an associative array with characteristics of the navigation item including the href (i.e. link), label, and selected status.
	 *
	 * Using this method it is now possible to have non-table navigation items as well. You would just add these items to the \[_tables\] section of the conf.ini file then override the item using this method.
	 *
	 * @since 1.3
	 *
	 * @section how_the_nav_menu_is_built How the Nav Menu Is Built
	 * 
	 * Xataface builds the navigation menu by looping through each item in the [_tables] section of the conf.ini file, passing it to the getNavItem() method, and adding the resulting navigation item to the menu. If getNavItem() returns null, then that item will be skipped. If getNavItem throws an exception, then the default rendering for the menu item will take place.
	 * 
	 * @param string $key The key of the nav item. In the case of a table, this would be the table name.
	 * @param string $label The label of the nav item (may be overridden).
	 * @returns mixed This method should return either:
	 * - An associative array with the properties of the nav item.
	 * - @p null to indicate that this nav item should be omitted altogether. (e.g. if the user shouldn't have permission for it).
	 * If returning an associative array, it should contain the following keys:
	 * - @p href - (String) The URL where this nav item should point.
	 * - @p label - (String) The label of this nav item.
	 * - @p selected - (Boolean) True if the nav item is currently selected. False otherwise.
	 *
	 * @throws Exception If you want to signal Xataface to just use default rendering for the current navigation item you can just throw an exception. The default rendering will link to the table named @p $key, and the item's label will be the same as @p $label.
	 *
	 * @section getNavItem_examples Examples
	 *
	 * Given the following conf.ini file:
	 * @code
	 * ...
     * [_tables]
     *   people=People
     *   books=Books
     *   accounts=Accounts
     *   reports=Reports
     *
     * ...
     * @endcode
     * 
     * Suppose we want the navigation menu to only show the people and books options for regular users. Admin users can see all options.
     *
     *In addition, the 'reports' option doesn't correspond with a table of the database. Instead we are just going to link it to a custom action named 'reports'.
     *
     *Our getNavItem() method will look something like this:
     * @code 
     * function getNavItem($key, $label){
     *    if (!isAdmin() ){
     *        switch ($key){
     *            case 'people':
     *            case 'books':
     *                // non-admin users can see these
     *                throw new Exception("Use default rendering");
     *        }
     *        // Non-admin users can't see any other table.
     *        return null;
     * 
     *    } else {
     *
     *        //Admin users can see everything..
     *        $query =& Dataface_Application::getInstance()->getQuery();
     *        switch ($key){
     *            case 'reports':
     *                // reports is not a table so we need to return custom properties.
     *                return array(
     *                    'href' => DATAFACE_SITE_HREF.'?-action=reports',
     *                    'label' => $label,
     *                    'selected' => ($query['-action'] == 'reports')
     *                );
     *            
     *        }
     *        
     *
     *        // For other actions we need to make sure that they aren't selected
     *        // if the current action is reports because we want the 'reports'
     *        // tab to be selected only in that case.
     *        return array(
     *            'selected' => ($query['-table'] == $key and $query['-action'] != 'reports')
     *        );
     *    }
     *}
     * @endcode
     *
     * @see isNavItemSelected()
     * @see <a href="http://xataface.com/wiki/getNavItem">getNavItem Wiki Page</a>
     */
	function getNavItem($key, $label);
	function navItemIsSelected($key);
	function getTemplateContext();
	
	/**
	 * @brief Returns the name of an action that should be used as the target action of a search
	 * performed from the current context.  In past releases searches would always go to the list 
	 * action.  This gives you the ability to override this behavior with your own custom action
	 * depending on the circumstances.
	 *
	 * @param array $action The action definition to check (this would be the source action).
	 * @since 2.0
	 * @see Dataface_Application::getSearchTarget()
	 * @see DelegateClass:getSearchTarget()
	 */
	function getSearchTarget(array $action);
	
	// @}
	
	// @{
	/** @name Valuelist Customization */
	
	function valuelist__valuelistname();
	// @}
}
