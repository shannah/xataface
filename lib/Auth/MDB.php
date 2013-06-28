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
 * @version CVS: $Id: MDB.php,v 1.1.1.1 2005/11/29 19:21:53 sjhannah Exp $
 * @link http://pear.php.net/LiveUser
 */

/**
 * Require parent class definition and PEAR::MDB class.
 */
require_once 'LiveUser/Auth/Common.php';
require_once 'MDB.php';
MDB::loadFile('Date');

/**
 * MDB container for Authentication
 *
 * This is a PEAR::MDB backend driver for the LiveUser class.
 * A PEAR::MDB connection object can be passed to the constructor to reuse an
 * existing connection. Alternatively, a DSN can be passed to open a new one.
 *
 * Requirements:
 * - File "LiveUser.php" (contains the parent class "LiveUser")
 * - Array of connection options or a PEAR::MDB connection object must be
 *   passed to the constructor.
 *   Example: array('dsn'                   => 'mysql://user:pass@host/db_name',
 *                  'connection             => &$conn, # PEAR::MDB connection object
 *                  'loginTimeout'          => 0,
 *                  'allowDuplicateHandles' => 1);
 *
 * @category authentication
 * @package  LiveUser
 * @author   Markus Wolff <wolff@21st.de>
 * @copyright 2002-2005 Markus Wolff
 * @license http://www.gnu.org/licenses/lgpl.txt
 * @version Release: @package_version@
 * @link http://pear.php.net/LiveUser
 */
class LiveUser_Auth_MDB extends LiveUser_Auth_Common
{
    /**
     * dsn that was connected to
     *
     * @var object
     * @access private
     */
    var $dsn = null;

    /**
     * Database connection object.
     *
     * @var    object
     * @access private
     */
    var $dbc = null;

    /**
     * Table prefix
     * Prefix for all db tables the container has.
     *
     * @var    string
     * @access public
     */
    var $prefix = 'liveuser_';

    /**
     * Load the storage container
     *
     * @param  mixed &$conf   Name of array containing the configuration.
     * @param string $containerName name of the container that should be used
     * @return  boolean true on success or false on failure
     *
     * @access  public
     */
    function init(&$conf, $containerName)
    {
        parent::init($conf, $containerName);

        if (is_array($conf['storage'])) {
            if (isset($conf['storage']['connection'])
                && MDB::isConnection($conf['storage']['connection'])
            ) {
                $this->dbc = &$conf['storage']['connection'];
            } elseif (isset($conf['storage']['dsn'])) {
                $this->dsn = $conf['storage']['dsn'];
                $function = null;
                if (isset($conf['storage']['function'])) {
                    $function = $conf['storage']['function'];
                }
                $options = null;
                if (isset($conf['storage']['options'])) {
                    $options = $conf['storage']['options'];
                }
                $options['optimize'] = 'portability';
                if ($function == 'singleton') {
                    $this->dbc =& MDB::singleton($conf['storage']['dsn'], $options);
                } else {
                    $this->dbc =& MDB::connect($conf['storage']['dsn'], $options);
                }
                if (PEAR::isError($this->dbc)) {
                    $this->_stack->push(LIVEUSER_ERROR_INIT_ERROR, 'error',
                        array('container' => 'could not connect: '.$this->dbc->getMessage()));
                    return false;
                }
            }
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
        if (!isset($this->tables['users']['fields']['lastlogin'])) {
            return true;
        }

        $query  = 'UPDATE ' . $this->prefix . $this->alias['users'].'
                 SET '    . $this->alias['lastlogin']
                    .'='  . $this->dbc->getValue($this->fields['lastlogin'], MDB_Date::unix2Mdbstamp($this->currentLogin)) . '
                 WHERE '  . $this->alias['auth_user_id']
                    .'='  . $this->dbc->getValue($this->fields['auth_user_id'], $this->propertyValues['auth_user_id']);

        $result = $this->dbc->query($query);

        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ERROR, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }

        return true;
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
     * @param  string $handle  user handle
     * @param  boolean $passwd user password
     * @param string $auth_user_id auth user id
     * @return boolean true upon success or false on failure
     *
     * @access private
     */
    function readUserData($handle = '', $passwd = '', $auth_user_id = false)
    {
        $fields = $types = array();
        foreach ($this->tables['users']['fields'] as $field => $req) {
            $fields[] = $this->alias[$field] . ' AS ' . $field;
            $types[] = $this->fields[$field];
        }

        // Setting the default query.
        $query = 'SELECT ' . implode(',', $fields) . '
                   FROM '   . $this->prefix . $this->alias['users'] . '
                   WHERE  ';
        if ($auth_user_id) {
            $query .= $this->alias['auth_user_id'] . '='
                . $this->dbc->getValue($this->fields['auth_user_id'], $this->propertyValues['auth_user_id']);
        } else {
            $query .= $this->alias['handle'] . '='
                . $this->dbc->getValue($this->fields['handle'], $handle);

            if ($this->tables['users']['fields']['passwd']) {
                // If $passwd is set, try to find the first user with the given
                // handle and password.
                $query .= ' AND   ' . $this->alias['passwd'] . '='
                    . $this->dbc->getValue($this->fields['passwd'], $this->encryptPW($passwd));
            }
        }

        // Query database
        $result = $this->dbc->queryRow($query, $types, MDB_FETCHMODE_ASSOC);

        // If a user was found, read data into class variables and set
        // return value to true
        if (PEAR::isError($result)) {
            $this->_stack->push(
                LIVEUSER_ERROR, 'exception',
                array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
            );
            return false;
        }

        if (!is_array($result)) {
            return null;
        }

        if (array_key_exists('lastlogin', $result) && !empty($result['lastlogin'])) {
            $result['lastlogin'] = MDB_Date::mdbstamp2Unix($result['lastlogin']);
        }
        $this->propertyValues = $result;

        return true;
    }

    /**
     * Properly disconnect from database
     *
     * @return boolean true on success or false on failure
     *
     * @access public
     */
    function disconnect()
    {
        if ($this->dsn) {
            $result = $this->dbc->disconnect();
            if (PEAR::isError($result)) {
                $this->_stack->push(
                    LIVEUSER_ERROR, 'exception',
                    array('reason' => $result->getMessage() . '-' . $result->getUserInfo())
                );
                return false;
            }
            $this->dbc = null;
        }
        return true;
    }
}
?>
