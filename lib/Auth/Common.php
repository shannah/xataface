<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * A framework for authentication and authorization in PHP applications
 *
 * LiveUser is an authentication/permission framework designed
 * to be flexible and easily extendable.
 *
 * Since it is impossible to have a
 * "one size fits all" it takes a container
 * approach which should enable it to
 * be versatile enough to meet most needs.
 *
 * PHP version 4 and 5 
 *
 * LICENSE: This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public 
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston,
 * MA  02111-1307  USA 
 *
 *
 * @category authentication
 * @package  LiveUser
 * @author  Markus Wolff <wolff@21st.de>
 * @author Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @author Arnaud Limbourg <arnaud@php.net>
 * @author   Pierre-Alain Joye  <pajoye@php.net>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version CVS: $Id: Common.php,v 1.1.1.1 2005/11/29 19:21:53 sjhannah Exp $
 * @link http://pear.php.net/LiveUser
 */

/**
 * This class provides a set of functions for implementing a user
 * authorisation system on live websites. All authorisation
 * backends/containers must be extensions of this base class.
 *
 * Requirements:
 * - When using "DB" backend:
 *   PEAR::DB database abstraction layer
 * - LiveUser admin GUI for easy user administration and setup of
 *   authorisation areas and rights
 *
 * @category authentication
 * @package  LiveUser
 * @author   Markus Wolff <wolff@21st.de>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser
 */
class LiveUser_Auth_Common
{
    /**
     * Has the current user successfully logged in?
     * Default: false
     *
     * @var    boolean
     * @see    LiveUser_Auth_Common::isActive
     */
    var $loggedIn = null;

    /**
     * Timestamp of current login (last to be written)
     *
     * @var    integer
     */
    var $currentLogin = 0;

    /**
     * Number of hours that must pass between two logins
     * to be counted as a new login. Comes in handy in
     * some situations. Default: 12
     *
     * @var    integer
     */
    var $loginTimeout = 12;

    /**
     * Auth lifetime in seconds
     *
     * If this variable is set to 0, auth never expires
     *
     * @var    integer
     */
    var $expireTime = 0;

    /**
     * Maximum time of idleness in seconds
     *
     * Idletime gets refreshed each time, init() is called. If this
     * variable is set to 0, idle time is never checked.
     *
     * @var    integer
     */
    var $idleTime = 0;

    /**
     * Allow multiple users in the database to have the same
     * login handle. Default: false.
     *
     * @var    boolean
     */
    var $allowDuplicateHandles = false;

    /**
     * Allow empty passwords to be passed to LiveUser. Default: false.
     *
     * @var    boolean
     */
    var $allowEmptyPasswords = false;

    /**
     * Set posible encryption modes.
     *
     * @var    array
     */
    var $encryptionModes = array('MD5'   => 'MD5',
                                 'PLAIN' => 'PLAIN',
                                 'RC4'   => 'RC4',
                                 'SHA1'  => 'SHA1');

    /**
     * Defines the algorithm used for encrypting/decrypting
     * passwords. Default: "MD5".
     *
     * @var    string
     */
    var $passwordEncryptionMode = 'MD5';

    /**
     * Defines the secret to use for encryption if needed
     *
     * @var    string
     */
    var $secret;

    /**
     * Defines the array index number of the LoginManager?s "backends" property.
     *
     * @var    integer
     */
    var $backendArrayIndex = 0;

    /**
     * Error stack
     *
     * @var    PEAR_ErrorStack
     */
    var $_stack = null;
/**#@-*/

    /**
    * Property values
    *
    * @var array
    * @access public
    */
    var $propertyValues = array();

    /**
     * The name associated with this auth container. The name is used
     * when adding users from this container to the reference table
     * in the permission container. This way it is possible to see
     * from which auth container the user data is coming from.
     *
     * @var    string
     * @access public
     */
    var $containerName = null;

    /**
     * External values to check (config settings)
     *
     * @var    array
     * @access public
     */
    var $externalValues = array();

    /**
     *
     * @access public
     * @var    array
     */
    var $tables = array();

    /**
     *
     * @access public
     * @var    array
     */
    var $fields = array();

    /**
     *
     * @access public
     * @var    array
     */
    var $alias = array();

    /**
     * Class constructor. Feel free to override in backend subclasses.
     *
     * @var    array     configuration options
     * @return void
     *
     * @access protected
     */
    function LiveUser_Auth_Common()
    {
        $this->_stack = &PEAR_ErrorStack::singleton('LiveUser');
    }

    /**
     * Load the storage container
     *
     * @param  mixed &$conf   Name of array containing the configuration.
     * @param string $containerName name of the container that should be used
     * @return  boolean true on success or false on failure
     *
     * @access  public
     */
    function init($conf, $containerName)
    {
        $this->containerName = $containerName;
        if (is_array($conf)) {
            $keys = array_keys($conf);
            foreach ($keys as $key) {
                if (isset($this->$key)) {
                    $this->$key =& $conf[$key];
                }
            }
        }

        if (array_key_exists('storage', $conf) && is_array($conf['storage'])) {
            $keys = array_keys($conf['storage']);
            foreach ($keys as $key) {
                if (isset($this->$key)) {
                    $this->$key =& $conf['storage'][$key];
                }
            }
        }

        require_once 'LiveUser/Auth/Storage/Globals.php';
        if (empty($this->tables)) {
            $this->tables = $GLOBALS['_LiveUser']['auth']['tables'];
        } else {
            $this->tables = LiveUser::arrayMergeClobber($GLOBALS['_LiveUser']['auth']['tables'], $this->tables);
        }
        if (empty($this->fields)) {
            $this->fields = $GLOBALS['_LiveUser']['auth']['fields'];
        } else {
            $this->fields = LiveUser::arrayMergeClobber($GLOBALS['_LiveUser']['auth']['fields'], $this->fields);
        }
        if (empty($this->alias)) {
            $this->alias = $GLOBALS['_LiveUser']['auth']['alias'];
        } else {
            $this->alias = LiveUser::arrayMergeClobber($GLOBALS['_LiveUser']['auth']['alias'], $this->alias);
        }
    }

    /**
     * store all properties in an array
     *
     * @return  array
     *
     * @access  public
     */
    function freeze()
    {
        // get values from $this->externalValues['values'] and
        // store them into $this->propertyValues['storedExternalValues']
        $this->setExternalValues();

        $propertyValues = array(
            'propertyValues'    => $this->propertyValues,
            'loggedIn'          => $this->loggedIn,
            'currentLogin'      => $this->currentLogin,
        );

        return $propertyValues;
    }

    /**
     * Reinitializes properties
     *
     * @param   array  $propertyValues
     * @return  boolean
     *
     * @access  publi
     */
    function unfreeze($propertyValues)
    {
         foreach ($propertyValues as $key => $value) {
             $this->{$key} = $value;
         }

        return $this->externalValuesMatch();
    } // end func unfreeze

    /**
     * Decrypts a password so that it can be compared with the user
     * input. Uses the algorithm defined in the passwordEncryptionMode
     * property.
     *
     * @param  string the encrypted password
     * @return string The decrypted password
     *
     * @access public
     */
    function decryptPW($encryptedPW)
    {
        $decryptedPW = 'Encryption type not supported.';

        switch (strtoupper($this->passwordEncryptionMode)) {
        case 'PLAIN':
            $decryptedPW = $encryptedPW;
            break;
        case 'MD5':
            // MD5 can't be decoded, so return the string unmodified
            $decryptedPW = $encryptedPW;
            break;
        case 'RC4':
            $decryptedPW = LiveUser::cryptRC4($decryptedPW, $this->secret, false);
            break;
        case 'SHA1':
            // SHA1 can't be decoded, so return the string unmodified
            $decryptedPW = $encryptedPW;
            break;
        }

        return $decryptedPW;
    }

    /**
     * Encrypts a password for storage in a backend container.
     * Uses the algorithm defined in the passwordEncryptionMode
     * property.
     *
     * @param string  encryption type
     * @return string The encrypted password
     *
     * @access public
     */
    function encryptPW($plainPW)
    {
        $encryptedPW = 'Encryption type not supported.';

        switch (strtoupper($this->passwordEncryptionMode)) {
        case 'PLAIN':
            $encryptedPW = $plainPW;
            break;
        case 'MD5':
            $encryptedPW = md5($plainPW);
            break;
        case 'RC4':
            $encryptedPW = LiveUser::cryptRC4($plainPW, $this->secret, true);
            break;
        case 'SHA1':
            if (!function_exists('sha1')) {
                $this->_stack->push(LIVEUSER_ERROR_NOT_SUPPORTED, 'exception', array(),
                    'SHA1 function doesn\'t exist. Upgrade your PHP version');
                return false;
            }
            $encryptedPW = sha1($plainPW);
            break;
        }

        return $encryptedPW;
    }

    /**
     * Checks if there's enough time between lastLogin
     * and current login (now) to count as a new login.
     *
     * @return boolean true if it is a new login, false if not
     *
     * @access public
     */
    function isNewLogin()
    {
        if (!array_key_exists('lastlogin', $this->propertyValues)) {
            return true;
        }
        $meantime = $this->loginTimeout * 3600;
        if (time() >= $this->propertyValues['lastlogin'] + $meantime) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Tries to make a login with the given handle and password.
     * A user can't login if he's not active.
     *
     * @param string   user handle
     * @param string   user password
     * @return boolean true on success or false on failure
     *
     * @access public
     */
    function login($handle, $passwd)
    {
        // Init value: Is user logged in?
        $this->loggedIn = false;

        // Read user data from database
        $result = $this->readUserData($handle, $passwd);
        if (!$result) {
            return $result;
        }

        // If login is successful (user data has been read)
        // ...we still need to check if this user is declared active
        if (!array_key_exists('is_active', $this->propertyValues)
            || $this->propertyValues['is_active']
        ) {
            // ...and if so, we have a successful login (hooray)!
            $this->loggedIn = true;
            $this->currentLogin = time();
        }

        // In case Login was successful, check if this can be counted
        // as a _new_ login by definition...
        if ($this->isNewLogin() && $this->loggedIn) {
            $this->_updateUserData();
        }

        return true;
    }

    /**
     * Writes current values for user back to the database.
     * This method does nothing in the base class and is supposed to
     * be overridden in subclasses according to the supported backend.
     *
     * @return boolean true on success or false on failure
     *
     * @access private
     */
    function _updateUserData()
    {
        $this->_stack->push(LIVEUSER_ERROR_NOT_SUPPORTED, 'exception',
            array('feature' => '_updateUserData')
        );
        return false;
    }

    /**
     * Reads auth_user_id, passwd, is_active flag
     * lastlogin timestamp from the database
     * If only $handle is given, it will read the data
     * from the first user with that handle and return
     * true on success.
     * If $handle and $passwd are given, it will try to
     * find the first user with both handle and password
     * matching and return true on success (this allows
     * multiple users having the same handle but different
     * passwords - yep, some people want this).
     * If no match is found, false is being returned.
     *
     * Again, this does nothing in the base class. The
     * described functionality must be implemented in a
     * subclass overriding this method.
     *
     * @param  string $handle user handle
     * @param  boolean $passwd user password
     * @param string $auth_user_id auth user id
     * @return boolean true on success or false on failure
     *
     * @access public
     */
    function readUserData($handle = '', $passwd = '', $auth_user_id = false)
    {
        $this->_stack->push(LIVEUSER_ERROR_NOT_SUPPORTED, 'exception',
            array('feature' => 'readUserData')
        );
        return false;
    }

    /**
     * Function returns the inquired value if it exists in the class.
     *
     * @param  string $what  Name of the property to be returned.
     * @return mixed    null, a value or an array.
     *
     * @access public
     */
    function getProperty($what)
    {
        $that = null;
        if (array_key_exists($what, $this->propertyValues)) {
            $that = $this->propertyValues[$what];
        } elseif (isset($this->$what)) {
            $that = $this->$what;
        }
        return $that;
    }

    /**
     * Creates associative array of values from $externalValues['values'] with $keysToCheck
     *
     * @return void
     *
     * @access public
     */
    function setExternalValues()
    {
        if (isset($this->externalValues['keysToCheck'])
            && is_array($this->externalValues['keysToCheck'])
        ) {
            foreach ($this->externalValues['keysToCheck'] as $keyToCheck) {
                if (isset($this->externalValues['values'][$keyToCheck])) {
                    $this->propertyValues['storedExternalValues'][$keyToCheck] =
                        md5($this->externalValues['values'][$keyToCheck]);
                }
            }
        }
    }

    /**
     * Check if the stored external values match the current external values
     *
     * @return boolean true on success or false on failure
     *
     * @access  public
     */
    function externalValuesMatch()
    {
        if (isset($this->propertyValues['storedExternalValues'])
            && is_array($this->propertyValues['storedExternalValues'])
        ) {
            foreach ($this->propertyValues['storedExternalValues'] as $keyToCheck => $storedValue) {
                // return false if any one of the stored values does not match the current value
                if (!isset($this->externalValues['values'][$keyToCheck])
                    || md5($this->externalValues['values'][$keyToCheck]) != $storedValue
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * properly disconnect from resources
     *
     * @return  void
     *
     * @access  public
     */
    function disconnect()
    {
    }

}
?>
