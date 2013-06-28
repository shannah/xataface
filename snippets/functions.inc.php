<?php
/**
 * @file inc/functions.php
 * @brief This file is included at the beginning of the index.php file so it 
 * and its functions should be available to every part of the app.
 */

/**
 * @brief Gets the currently logged in user.
 * @returns {Dataface_Record} Dataface_Record object encapsulating the row from the users
 * table of the currently logged-in user.  If no user is logged in, then this will return
 * null.
 */
function getUser(){
	static $user =-1;
	if (is_int($user) and $user == -1 ){
		$user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
	}
	return $user;

}

/**
 * @brief Gets the role of the currently logged in user.  
 * @returns String The role of the currently logged in user (or null if none).
 */
function getRole(){
	static $role = -1;
	if ( is_int($role) and $role == -1 ){
		$user = getUser();
		if ( !$user ) return null;
		$role = $user->val('role');
	}
	return $role;
}

/**
 * @brief Checks if the currently logged-in user is an administrator.
 * @returns boolean
 */
function isAdmin(){
	return (getRole() == 'ADMIN');
}



