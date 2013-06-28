<?php
/**
 * @brief Interface for action handlers in Xataface application.  An action handler
 * serves as an HTTP access point for an application.  Actions may be located
 * in the following locations:
 *
 * -# %DATAFACE_SITE_PATH%/tables/%tablename%/actions/%actionname%.php
 * -# %DATAFACE_SITE_PATH%/actions/%actionname%.php
 * -# %DATAFACE_SITE_PATH%/modules/%modulename%/actions/%actionname%.php
 * -# %DATAFACE_PATH/actions/%actionname%.php
 *
 * The action file should contain a class with a name that roughly matches
 * the path to the file.
 *
 * E.g. An action in the tables/%tablename%/actions/%actionname%.php file should
 * be named @p tables_%tablename%_actions_%actionname% . Similarly an action
 * in the actions/%actionname%.php file should be named 
 *  @p actions_%actionname% etc...
 *
 * This interface serves to document the available methods that may be defined in 
 * an action handler and how they are expected to work.
 *
 * @par Example Action
 *
 * Consider a sample action located in the @p %DATAFACE_SITE_PATH%/actions/myaction.php
 * file:
 *
 * @code
 * class actions_myaction {
 *     function handle($params){
 *         echo "Hello World";
 *     }
 * }
 * @endcode
 *
 * @section action_config_with_actions_ini Configuration With The actions.ini file
 *
 * An action handler is the PHP portion of an action that is defined in the 
 * <a href="http://xataface.com/wiki/actions.ini_file">actions.ini file</a>.  The actions.ini
 * file allows you to decide where the action appears in the application's user interface
 * as well as such things as permissions.  You can also define permissions programmatically
 * inside your action.
 *
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created Nov. 26, 2011
 */
interface ActionHandler {

	/**
	 * @brief The entry point for this action. 
	 *
	 * @param array $params Parameters passed to the action to determine how the
	 * action should be executed.  The only key that is currently set to be passed
	 * is @p relationship but this can equivalently be obtained from the @p $query['-relationship']
	 * Query parameter.
	 *
	 * @returns mixed If this method returns a PEAR_Error object, then Xataface will
	 * do some additional processing depending on the type of error.
	 *
	 * @par Dealing with Permission Denied Errors
	 *
	 * If during the course of execution the current user is deemed to lack sufficient
	 * permission to perform this action, then handle() may return a PEAR_Error
	 * with the code @p DATAFACE_E_PERMISSION_DENIED.  Equivalently it may return
	 * the result of the Dataface_Error::permissionDenied() method:
	 *
	 * @code
	 * if ( !$record->checkPermission('view') ){
	 *     return Dataface_Error::permissionDenied("You don't have permission for this.");
	 * }
	 * @endcode
	 *
	 * @par Request Not Handled Errors
	 *
	 * It is also possible to signal to Xataface that the action opted not to handle
	 * the request at all, so that Xataface will pass the opportunity to handle the
	 * request onto the next elligible action handler.  This allows you to define
	 * handlers specific to a table that may override default actions or choose to just
	 * allow the default action to handle it.
	 *
	 * @code
	 *
	 * class tables_mytable_actions_list {
	 *     function handle($params){
	 *         $query = Dataface_Application::getInstance()->getQuery();
	 *         if ( !@$query['foo'] ) return PEAR::raiseError(
	 *             'WE only handle requests when the foo parameter is provided.  Use the default list action.',
	 *             DATAFACE_E_REQUEST_NOT_HANDLED
	 *          );
	 *          echo "WE are handling this request ourselves.";
	 *     }
	 * }
	 * @endcode
	 */
	public function handle($params);
	
	/**
	 * @brief An optional method to override the permissions granted to the current user
	 * when this action is requested.  This option is not often used and it is questionable
	 * whether it is of any value since permissions can be amply defined in the table
	 * or application delegate classes.
	 *
	 * @params array $params The parameters.
	 * @see Dataface_Table::getPermissions() for available parameters.
	 */
	public function getPermissions($params);
}
