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
 * @version CVS: $Id: Cache.php,v 1.1.1.1 2005/11/29 19:21:56 sjhannah Exp $
 * @link http://pear.php.net/LiveUser
 */

/**
 * Require parent class definition.
 */
require_once 'LiveUser/Perm/Storage.php';

/**
 * Cache container for permission handling
 *
 * This is a Cache backend driver for the LiveUser class.
 *
 * @category authentication
 * @package  LiveUser
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @author  Bjoern Kraus <krausbn@php.net>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser
 */
class LiveUser_Perm_Storage_Cache extends LiveUser_Perm_Storage
{

    /**
     * Storage Container
     *
     * @var object
     * @access public
     */
    var $_storage = null;

    /**
     *
     *
     *
     * @param array &$storageConf Array with the storage configuration
     * @return boolean true on success, false on failure.
     *
     * @access public
     */
    function init(&$storageConf, &$confArray)
    {
        if (!parent::init($storageConf)) {
            return false;
        }

        $this->_storage =& LiveUser::storageFactory($confArray);
        if ($this->_storage === false) {
            $this->_stack->push(LIVEUSER_ERROR, 'exception',
                array('msg' => 'Could not instanciate storage container'));
            return false;
        }

        return true;
    }

    /**
     *
     *
     * @param int $auth_user_id
     * @param string $containerName
     * @return mixed array or false on failure
     *
     * @access public
     */
    function mapUser($auth_user_id, $containerName)
    {
        if (in_cache) {
            return cache;
        }
        $result = $this->_storage->mapUser($auth_user_id, $containerName);
        if ($result === false) {
            return false;
        }
        write_into_cache
        return $result;
    }

    /**
     * Reads all rights of current user into a
     * two-dimensional associative array, having the
     * area names as the key of the 1st dimension.
     * Group rights and invididual rights are being merged
     * in the process.
     *
     * @param int $perm_user_id
     * @return mixed array of false on failure
     *
     * @access public
     */
    function readUserRights($perm_user_id)
    {
        if (in_cache) {
            return cache;
        }
        $result = $this->_storage->readUserRights($perm_user_id);
        if ($result === false) {
            return false;
        }
        write_into_cache
        return $result;
    }

    function readAreaAdminAreas($perm_user_id)
    {
        if (in_cache) {
            return cache;
        }
        $result = $this->_storage->readAreaAdminAreas($perm_user_id);
        if ($result === false) {
            return false;
        }
        write_into_cache
        return $result;
    }

    /**
     * Reads all the group ids in that the user is also a member of
     * (all groups that are subgroups of these are also added recursively)
     *
     * @param int $perm_user_id
     * @return void
     *
     * @access private
     * @see    readRights()
     */
    function readGroups($perm_user_id)
    {
        if (in_cache) {
            return cache;
        }
        $result = $this->_storage->readGroups($perm_user_id);
        if ($result === false) {
            return false;
        }
        write_into_cache
        return $result;
    } // end func readGroups

    /**
     * Reads the group rights
     * and put them in the array
     *
     * right => 1
     *
     * @param array $group_rights
     * @return  boolean
     *
     * @access  public
     */
    function readGroupRights($group_ids)
    {
        if (in_cache) {
            return cache;
        }
        $result = $this->_storage->readGroupRights($group_ids);
        if ($result === false) {
            return false;
        }
        write_into_cache
        return $result;
    } // end func readGroupRights

    /**
     *
     *
     * @param array $group_ids
     * @param array $newGroupIds
     * @return mixed array or false on failure
     *
     * @access public
     */
    function readSubGroups($group_ids, $newGroupIds)
    {
        if (in_cache) {
            return cache;
        }
        $result = $this->_storage->readSubGroups($group_ids, $newGroupIds);
        if ($result === false) {
            return false;
        }
        write_into_cache
        return $result;
    }

    function readImplyingRights($rightIds, $table)
    {
        if (in_cache) {
            return cache;
        }
        $result = $this->_storage->readImplyingRights($rightIds, $table);
        if ($result === false) {
            return false;
        }
        write_into_cache
        return $result;
    }

    function readImpliedRights($currentRights, $currentLevel)
    {
        if (in_cache) {
            return cache;
        }
        $result = $this->_storage->readImpliedRights($currentRights, $currentLevel);
        if ($result === false) {
            return false;
        }
        write_into_cache
        return $result;
    }

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
