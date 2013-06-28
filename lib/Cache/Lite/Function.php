<?php

/**
* This class extends Cache_Lite and can be used to cache the result and output of functions/methods
*
* This class is completly inspired from Sebastian Bergmann's
* PEAR/Cache_Function class. This is only an adaptation to
* Cache_Lite
*
* There are some examples in the 'docs/examples' file
* Technical choices are described in the 'docs/technical' file
*
* @package Cache_Lite
* @version $Id: Function.php,v 1.1.1.1 2005/11/29 19:21:53 sjhannah Exp $
* @author Sebastian BERGMANN <sb@sebastian-bergmann.de>
* @author Fabien MARTY <fab@php.net>
*/
 
require_once('Cache/Lite.php');

class Cache_Lite_Function extends Cache_Lite
{

    // --- Private properties ---
    
    /**
    * Default cache group for function caching
    *
    * @var string $_defaultGroup
    */
    var $_defaultGroup = 'Cache_Lite_Function';
    
    // --- Public methods ----
    
    /**
    * Constructor
    *
    * $options is an assoc. To have a look at availables options,
    * see the constructor of the Cache_Lite class in 'Cache_Lite.php'
    *
    * Comparing to Cache_Lite constructor, there is another option :
    * $options = array(
    *     (...) see Cache_Lite constructor
    *     'defaultGroup' => default cache group for function caching (string)
    * );
    *
    * @param array $options options
    * @access public
    */
    function Cache_Lite_Function($options = array(NULL))
    {
        if (isset($options['defaultGroup'])) {
            $this->_defaultGroup = $options['defaultGroup'];
        }
        $this->Cache_Lite($options);
    }
    
    /**
    * Calls a cacheable function or method (or not if there is already a cache for it)
    *
    * Arguments of this method are read with func_get_args. So it doesn't appear
    * in the function definition. Synopsis : 
    * call('functionName', $arg1, $arg2, ...)
    * (arg1, arg2... are arguments of 'functionName')
    *
    * @return mixed result of the function/method
    * @access public
    */
    function call()
    {
        $arguments = func_get_args();
        $id = $this->_makeId($arguments);
        $data = $this->get($id, $this->_defaultGroup);
        if ($data !== false) {
            $array = unserialize($data);
            $output = $array['output'];
            $result = $array['result'];
        } else {
            ob_start();
            ob_implicit_flush(false);
            $target = array_shift($arguments);
            if (strstr($target, '::')) { // classname::staticMethod
                list($class, $method) = explode('::', $target);
                $result = call_user_func_array(array($class, $method), $arguments);
            } else if (strstr($target, '->')) { // object->method
                // use a stupid name ($objet_123456789 because) of problems whene the object
                // name is the same as this var name
                list($object_123456789, $method) = explode('->', $target);
                global $$object_123456789;
                $result = call_user_func_array(array($$object_123456789, $method), $arguments);
            } else { // function
                $result = call_user_func_array($target, $arguments);
            }
            $output = ob_get_contents();
            ob_end_clean();
            $array['output'] = $output;
            $array['result'] = $result;
            $this->save(serialize($array), $id, $this->_defaultGroup);
        }
        echo($output);
        return $result;
    }
    
    /**
    * Drop a cache file
    *
    * Arguments of this method are read with func_get_args. So it doesn't appear
    * in the function definition. Synopsis : 
    * remove('functionName', $arg1, $arg2, ...)
    * (arg1, arg2... are arguments of 'functionName')
    *
    * @return boolean true if no problem
    * @access public
    */
    function drop()
    {
        $id = $this->_makeId(func_get_args());
        $this->remove($id, $this->_defaultGroup);
    }
    
    /**
    * Make an id for the cache
    *
    * @var array result of func_get_args for the call() or the remove() method
    * @return string id
    * @access private
    */
    function _makeId($arguments) {
        $id = serialize($arguments); // Generate a cache id
        if (!$this->_fileNameProtection) {
            $id = md5($id);
            // if fileNameProtection is set to false, then the id has to be hashed
            // because it's a very bad file name in most cases
        }    
        return $id;
    }
    
}

?>
