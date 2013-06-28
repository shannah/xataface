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
 * @version CVS: $Id: Simple.php,v 1.1.1.1 2005/11/29 19:21:56 sjhannah Exp $
 * @link http://pear.php.net/LiveUser
 */

/**
 * Base class for permission handling
 *
 * This class provides a set of functions for implementing a user
 * permission management system on live websites. All authorisation
 * backends/containers must be extensions of this base class.
 *
 * @category authentication
 * @package  LiveUser
 * @author  Markus Wolff <wolff@21st.de>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser
 */
class LiveUser_Perm_Simple
{
    /**
     * Unique user ID, used to identify users from the auth container.
     *
     * @var string
     * @access public
     */
    var $perm_user_id = '';

    /**
     * One-dimensional array containing current user's rights.
     * This already includes grouprights and possible overrides by
     * individual right settings.
     *
     * Format: "RightId" => "Level"
     *
     * @var mixed
     * @access public
     */
    var $rights = false;

    /**
     * One-dimensional array containing only the individual
     * rights for the actual user.
     *
     * Format: "RightId" => "Level"
     *
     * @var array
     * @access public
     */
    var $user_rights = array();

    /**
     * Defines the user type.
     *
     * @var integer
     * @access public
     */
    var $perm_type = LIVEUSER_ANONYMOUS_TYPE_ID;

    /**
     * Error stack
     *
     * @var PEAR_ErrorStack
     * @access public
     */
    var $_stack = null;

    /**
     * Storage Container
     *
     * @var object
     * @access public
     */
    var $_storage = null;

    /**
     * Class constructor. Feel free to override in backend subclasses.
     */
    function LiveUser_Perm_Simple()
    {
        $this->_stack = &PEAR_ErrorStack::singleton('LiveUser');
    }

    /**
     * Load the storage container
     *
     * @param  mixed &$conf   Name of array containing the configuration.
     * @return  boolean true on success or false on failure
     *
     * @access  public
     */
    function init(&$conf)
    {
        if (!array_key_exists('storage', $conf)) {
            $this->_stack->push(LIVEUSER_ERROR, 'exception',
                array('msg' => 'Missing storage configuration array'));
            return false;
        }

        if (is_array($conf)) {
            $keys = array_keys($conf);
            foreach ($keys as $key) {
                if (isset($this->$key)) {
                    $this->$key =& $conf[$key];
                }
            }
        }

        $this->_storage =& LiveUser::storageFactory($conf['storage']);
        if ($this->_storage === false) {
            $this->_stack->push(LIVEUSER_ERROR, 'exception',
                array('msg' => 'Could not instanciate storage container'));
            return false;
        }

        return true;
    }

    /**
     * Tries to find the user with the given user ID in the permissions
     * container. Will read all permission data and return true on success.
     *
     * @param   string $auth_user_id  user identifier
     * @param   string $containerName  name of the auth container
     * @return  boolean true on success or false on failure
     *
     * @access  public
     */
    function mapUser($auth_user_id = null, $containerName = null)
    {
        $result = $this->_storage->mapUser($auth_user_id, $containerName);
        if ($result === false) {
            return false;
        }

        if (is_null($result)) {
            return false;
        }

        $this->perm_user_id = $result['perm_user_id'];
        $this->perm_type    = $result['perm_type'];

        $this->readRights();

        return true;
    }

    /**
     * Reads all rights of current user into a
     * two-dimensional associative array, having the
     * area names as the key of the 1st dimension.
     * Group rights and invididual rights are being merged
     * in the process.
     *
     * @return mixed array or false on failure
     *
     * @access public
     */
    function readRights()
    {
        $this->rights = array();
        $result = $this->readUserRights($this->perm_user_id);
        if ($result === false) {
            return false;
        }
        $this->rights = $result;
        return $this->rights;
    }

    /**
     *
     *
     * @param int $perm_user_id
     * @return mixed array or false on failure
     *
     * @access public
     */
    function readUserRights($perm_user_id)
    {
        $this->user_rights = array();
        $result = $this->_storage->readUserRights($perm_user_id);
        if ($result === false) {
            return false;
        }
        $this->user_rights = $result;
        return $this->user_rights;
    }

    /**
     * Checks if the current user has a certain right in a
     * given area.
     * If $this->ondemand and $ondemand is true, the rights will be loaded on
     * the fly.
     *
     * @param   integer $right_id  Id of the right to check for.
     * @return  integer Level of the right.
     *
     * @access  public
     */
    function checkRight($right_id)
    {
        // check if the user is above areaadmin
        if (!$right_id || $this->perm_type > LIVEUSER_AREAADMIN_TYPE_ID) {
            return LIVEUSER_MAX_LEVEL;
        // If he does, look for the right in question.
        } elseif (is_array($this->rights) && isset($this->rights[$right_id])) {
            // We know the user has the right so the right level will be returned.
            return $this->rights[$right_id];
        }
        return false;
    } // end func checkRight

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
        if (isset($this->$what)) {
            $that = $this->$what;
        }
        return $that;
    }

    /**
     * store all properties in an array
     *
     * @param string $sessionName name of the session in use.
     * @return  array containing the property values
     *
     * @access  public
     */
    function freeze($sessionName)
    {
        $propertyValues = array(
            'perm_user_id' => $this->perm_user_id,
            'rights'       => $this->rights,
            'user_rights'  => $this->user_rights,
            'group_rights' => $this->group_rights,
            'perm_type'    => $this->perm_type,
            'group_ids'    => $this->group_ids,
        );
        return $this->_storage->freeze($sessionName, $propertyValues);
    } // end func freeze

    /**
     * Reinitializes properties
     *
     * @param   array  $sessionName name of the session in use.
     * @param boolean always returns true
     *
     * @access  public
     */
    function unfreeze($sessionName)
    {
        $propertyValues = $this->_storage->unfreeze($sessionName);
        if ($propertyValues) {
            foreach ($propertyValues as $key => $value) {
                $this->{$key} = $value;
            }
        }
        return true;
    } // end func unfreeze

    /**
     * properly disconnect from resources
     *
     * @return void
     *
     * @access  public
     */
    function disconnect()
    {
        $this->_storage->disconnect();
    }
}
?>
