<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
* Provides a method of storing and manipulating multibyte strings in PHP.
*
* PHP versions 4 and 5
*
* LICENSE: Copyright 2004-2006 John Downey. All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* o Redistributions of source code must retain the above copyright notice, this
*   list of conditions and the following disclaimer.
* o Redistributions in binary form must reproduce the above copyright notice,
*   this list of conditions and the following disclaimer in the documentation
*   and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE FREEBSD PROJECT "AS IS" AND ANY EXPRESS OR
* IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
* MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
* EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
* INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
* DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
* OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
* NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
* EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
* The views and conclusions contained in the software and documentation are
* those of the authors and should not be interpreted as representing official
* policies, either expressed or implied, of The PEAR Group.
*
* @category  Internationalization
* @package   I18N_UnicodeString
* @author    John Downey <jdowney@gmail.com>
* @copyright 2004-2006 John Downey
* @license   http://www.freebsd.org/copyright/freebsd-license.html 2 Clause BSD License
* @version   CVS: $Id$
* @filesource
*/

/**
* Class provides a way to use and manipulate multibyte strings in PHP
*
* @package I18N_UnicodeString
* @author  John Downey <jdowney@gmail.com>
* @access  public
* @version Release: @package_version@
*/
class I18N_UnicodeString
{
    /**
    * The internal representation of the string as an array of numbers.
    * @access private
    * @var    array $_unicode
    */
    var $_unicode = array();
    
    /**
    * Converts an entire array of strings to Unicode capable strings.
    *
    * Useful for converting a GET/POST of all its Unicode values into a
    * workable and easily changed format. Takes an optional second
    * parameter similer to that of {@link setString()}.
    *
    * @static
    * @access public
    * @param  array $array An array of PHP variables.
    * @param  string $encoding The encoding the string values are in.
    * @return array The array with all of its string values converted to
    *               I18N_Unicode objects.
    * @see    setString()
    */
    function convertArray($array, $encoding = 'HTML') {
        foreach($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = new I18N_UnicodeString($value, $encoding);
            }
        }
        
        return $array;
    }
    
    /**
    * The constructor of the class string which can receive a new string in a
    * number of formats.
    *
    * @access public
    * @param  mixed $value A variable containing the Unicode string in one of
    *                      various encodings.
    * @param  string $encoding The encoding that the string is in.
    * @see    setString()
    */
    function I18N_UnicodeString($value = '', $encoding = 'UTF-8') {
        $this->setString($value, $encoding);
    }
    
    /**
    * Set the string to a value passed in one of many encodings.
    *
    * You may pass the encoding as an optional second parameter which defaults
    * to UTF-8 encoding. Possible encodings are:
    * 
    * o <i>ASCII</i> - when you pass a normal 7 bit ASCII string
    * o <i>UTF-8</i> - when you pass a UTF-8 encoded string
    * o <i>HTML</i> - when you pass a string encoded with HTML entities, such
    *   as the kind received from a GET/POST
    * o <i>Unicode</i> or <i>UCS-4</i> - when passing an array of integer values representing
    *   each character
    *
    * @access public
    * @param  mixed $value A variable containing the Unicode string in one of
    *                      various encodings.
    * @param  string $encoding The encoding that the string is in.
    * @return mixed Returns true on success or PEAR_Error otherwise.
    */
    function setString($value, $encoding = 'UTF-8') {
           switch(strtoupper($encoding)) {
            case 'ASCII':
            case 'UTF8':
            case 'UTF-8':
                $this->_unicode = self::utf8ToUnicode($value);
                break;

            case 'HTML':
                $this->_unicode = $this->_stringFromHtml($value);
                break;

            case 'UTF32':
            case 'UTF-32':
            case 'UCS4':
            case 'UCS-4':
            case 'UNICODE':
                $this->_unicode = $value;
                break;

            default:
                return self::raiseError('Unrecognized encoding');
        }
        
        if (strtolower(get_class($this->_unicode)) == 'pear_error') {
            return $this->_unicode;
        }
        
        return true;
    }
    
    /**
    * Converts a string encoded with HTML entities into our internal
    * representation of an array of integers.
    *
    * @access private
    * @param  string $string A string containing Unicode values encoded as HTML
    *                        entities.
    * @return array The array of Unicode values.
    */
    function _stringFromHtml($string = '') {
        $parts   = explode('&#', $string);
        $unicode = array();
        foreach($parts as $part) {
            $text = strstr($part, ';');
            
            if (!empty($text)) {
                $value = substr($part, 0, strpos($part, ';'));

                /* Suggested by Jonathan Yavner to allow HTML to also be in
                   the form of &#xNNNN where NNNN is the hexidecimal of a
                   Unicode character */
                if (ord($value[0]) == 120) {
                    $unicode[] = intval(substr($value, 1), 16);
                } else {
                    $unicode[] = intval($value);
                }

                $text = substr($text, 1);

                for($i = 0, $max = strlen($text); $i < $max; $i++) {
                    $unicode[] = ord($text[$i]);
                }
            } else {
                for($i = 0, $max = strlen($part); $i < $max; $i++) {
                    $unicode[] = ord($part[$i]);
                }
            }
        }

        return $unicode;
    }
    
    /**
    * Converts a UTF-8 string into our representation of an array of integers.
    *
    * Method was made static by suggestion of Lukas Feiler (#7429)
    *
    * @static
    * @access public
    * @param  string $string A string containing Unicode values encoded in UTF-8
    * @return array The array of Unicode values.
    */
    function utf8ToUnicode($string = '') {
        $unicode = array();
        $values  = array();
        $search  = 1;
        
        for($count = 0, $length = strlen($string); $count < $length; $count++) {
            $value = ord($string[$count]);

            if ($value < 128) {
                // if the value is an ASCII char then just go ahead and add it on
                $unicode[] = $value;
            } else {
                // if not then we need to know how many more bytes make up this character
                if (count($values) == 0) {
                    if ($value >> 5 == 6) {
                        $values[] = ($value - 192) << 6;
                        $search   = 2;
                    } elseif ($value >> 4 == 14) {
                        $values[] = ($value - 224) << 12;
                        $search   = 3;
                    } elseif ($value >> 3 == 30) {
                        $values[] = ($value - 240) << 18;
                        $search   = 4;
                    } elseif ($value >> 2 == 62) {
                        $values[] = ($value - 248) << 24;
                        $search   = 5;
                    } elseif ($value >> 1 == 126) {
                        $values[] = ($value - 252) << 30;
                        $search   = 6;
                    } else {
                        return self::raiseError('Malformed UTF-8 string');
                    }
                } else {
                    if ($value >> 6 == 2) {
                        $values[] = $value - 128;
                    } else {
                        return self::raiseError('Malformed UTF-8 string');
                    }

                    if (count($values) == $search) {
                        // if we have all of our bytes then go ahead an encode it in unicode
                        $value = $values[0];
                        for($i = 1; $i < $search; $i++) {
                            $value += ($values[$i] << ((($search - $i) - 1) * 6));
                        }
                        
                        $unicode[] = $value;

                        $values = array();
                        $search = 1;
                    }
                }
            }
        }
        
        return $unicode;
    }

    /**
     * Transforms a single unicode character represented by an integer to a UTF-8 string.
     *
     * Suggested by Lukas Feiler (#7429)
     *
     * @static
     * @access public
     * @param  integer $int A unicode character as an integer
     * @return string The unicode character converted to a UTF-8 string.
     */
    function unicodeCharToUtf8($char) {
        $string = '';
        if ($char < 128) {
            // its an ASCII char no encoding needed
            $string .= chr($char);
        } elseif ($char < 1 << 11) {
            // its a 2 byte UTF-8 char
            $string .= chr(192 + ($char >> 6));
            $string .= chr(128 + ($char & 63));
        } elseif ($char < 1 << 16) {
            // its a 3 byte UTF-8 char
            $string .= chr(224 + ($char >> 12));
            $string .= chr(128 + (($char >> 6) & 63));
            $string .= chr(128 + ($char & 63));
        } elseif ($char < 1 << 21) {
            // its a 4 byte UTF-8 char
            $string .= chr(240 + ($char >> 18));
            $string .= chr(128 + (($char >> 12) & 63));
            $string .= chr(128 + (($char >>  6) & 63));
            $string .= chr(128 + ($char & 63));
        } elseif ($char < 1 << 26) {
            // its a 5 byte UTF-8 char
            $string .= chr(248 + ($char >> 24));
            $string .= chr(128 + (($char >> 18) & 63));
            $string .= chr(128 + (($char >> 12) & 63));
            $string .= chr(128 + (($char >> 6) & 63));
            $string .= chr(128 + ($char & 63));
        } else {
            // its a 6 byte UTF-8 char
            $string .= chr(252 + ($char >> 30));
            $string .= chr(128 + (($char >> 24) & 63));
            $string .= chr(128 + (($char >> 18) & 63));
            $string .= chr(128 + (($char >> 12) & 63));
            $string .= chr(128 + (($char >> 6) & 63));
            $string .= chr(128 + ($char & 63));
        }

        return $string;
    }
    
    /**
    * Retrieves the string and returns it as a UTF-8 encoded string.
    *
    * @access public
    * @return string A string with the Unicode values encoded in UTF-8.
    */
    function toUtf8String() {
        $string = '';

        foreach($this->_unicode as $char) {
            $string .= $this->unicodeCharToUtf8($char);
        }

        return $string;
    }
    
    /**
    * Retrieves the string and returns it as a string encoded with HTML
    * entities.
    *
    * @access public
    * @return string A string with the Unicode values encoded as HTML entities.
    */
    function toHtmlEntitiesString() {
        $string = '';

        foreach($this->_unicode as $char) {
            if ($char > 127) {
                $string .= '&#' . $char . ';';
            } else {
                $string .= chr($char);
            }
        }

        return $string;
    }
    
    /**
    * Retrieve the length of the string in characters.
    *
    * @access public
    * @return integer The length of the string.
    */
    function length() {
        return count($this->_unicode);
    }
    
    /**
    * Works exactly like PHP's substr function only it works on Unicode
    * strings.
    *
    * @access public
    * @param  integer $begin The beginning of the substring.
    * @param  integer $length The length to read. Defaults to the rest of the
    *                         string.
    * @return I18N_UnicodeString A new I18N_UnicodeString class containing the
    *                            substring or a PEAR_Error if an error is
    *                            thrown.
    */
    function subString($begin, $length = null) {
        $unicode = array();
        
        if (is_null($length)) {
            $length = $this->length() - $begin;
        }
        
        if (($begin + $length) > $this->length()) {
            return self::raiseError('Cannot read past end of string.');        
        }
        
        if ($begin > $this->length()) {
            return self::raiseError('Beginning extends past end of string.');
        }
        
        for($i = $begin, $max_length = ($begin + $length); $i < $max_length; $i++) {
            array_push($unicode, $this->_unicode[$i]);
        }

        return new I18N_UnicodeString($unicode, 'Unicode');
    }
    
    /**
    * Works like PHP's substr_replace function.
    *
    * @access public
    * @param  I18N_UnicodeString $find The string to replaced
    * @param  I18N_UnicodeString $replace The string to replace $find with
    * @param  integer $start The position in the string to start replacing at
    * @param  integer $length The length from the starting to position to stop
    *                         replacing at
    * @return I18N_UnicodeString The current string with all $find replaced by
    *                            $replace
    * @see    stringReplace()
    */
    function subStringReplace(&$find, &$replace, $start, $length = null) {
        if (is_null($length)) {
            $length = $this->length() - $start;
        }
        
        $begin  = $this->subString(0, $start);
        $string = $this->subString($start, $length);
        if (!method_exists($string, 'stringReplace')) {
	        // $string is a PEAR_Error, return it
	        return $string;
        } else {
	        $string = $string->stringReplace($find, $replace);
	        $after  = $this->subString($start + $length);
	        
	        return new I18N_UnicodeString(array_merge($begin->_unicode, $string->_unicode, $after->_unicode), 'Unicode');
        }
    }
    
    /**
    * Works like PHP's str_replace function.
    *
    * @access public
    * @param  I18N_UnicodeString $find The string to replaced
    * @param  I18N_UnicodeString $replace The string to replace $find with
    * @return I18N_UnicodeString The current string with all $find replaced by
    *                            $replace
    * @see    subStringReplace()
    */
    function stringReplace(&$find, &$replace) {
        $return = new I18N_UnicodeString($this->_unicode, 'Unicode');
        
        while($return->strStr($find) !== false) {
            $after = $return->strStr($find);
            $begin = $return->subString(0, $return->length() - $after->length());
            $after = $after->subString($find->length());
            
            $return = new I18N_UnicodeString(array_merge($begin->_unicode, $replace->_unicode, $after->_unicode), 'Unicode');
        }
        
        return $return;
    }
    
    /**
    * Works like PHP's strstr function by returning the string from $find on.
    *
    * @access public
    * @param  I18N_UnicodeString $find The string to found
    * @return I18N_UnicodeString The current string from $find on to the end
    */
    function strStr(&$find) {
        $found = false;
        $after = $find->_unicode;
        
        for($i = 0, $length = $this->length(); $i < $length; $i++) {
            if ($found) {
                $after[] = $this->_unicode[$i];
            } else {
                if ($this->_unicode[$i] == $find->_unicode[0]) {
                    if ($i + $find->length() > $length) {
                        break;
                    }
                    
                    $found = true;
                    for($c = 1, $max = $find->length(); $c < $max; $c++) {
                        if ($this->_unicode[++$i] != $find->_unicode[$c]) {
                            $found = false;
                            break;
                        }
                    }
                }
            }
        }
        
        if ($found) {
            return new I18N_UnicodeString($after, 'Unicode');
        } else {
            return false;
        }
    }

    /**
    * Returns the position of a character much like PHP's strpos function.
    *
    * @access public
    * @param  mixed $char A Unicode char represented as either an integer or a
    *                     UTF-8 char.
    * @return integer The location of the character in the string.
    */
    function indexOf($char) {
        if (!is_int($char)) {
            if (strlen($char) > 1) {
                $char = array_shift(self::utf8ToUnicode($char));
            } else {
                $char = ord($char);
            }
        }
        
        for($i = 0, $length = $this->length(); $i < $length; $i++) {
            if ($this->_unicode[$i] == $char) {
                return $i;
            }
        }
        
        return -1;
    }
    
    /**
    * Returns the last position of a character much like PHP's strrpos function.
    *
    * @access public
    * @param  mixed $char A Unicode char represented as either an integer or a
    *                     UTF-8 char.
    * @return integer The last location of the character in the string.
    */
    function lastIndexOf($char) {
        if (!is_int($char)) {
            if (strlen($char) > 1) {
                $char = array_shift($this->utf8ToUnicode($char));
            } else {
                $char = ord($char);
            }
        }
        
        for($i = $this->length() - 1; $i >= 0; $i--) {
            if ($this->_unicode[$i] == $char) {
                return $i;
            }
        }
        
        return -1;    
    }
    
    /**
    * Determines if two Unicode strings are equal
    *
    * @access public
    * @param  I18N_UnicodeString $unicode The string to compare to.
    * @return boolean True if they are equal, false otherwise.
    */
    function equals(&$unicode) {
        if ($this->length() != $unicode->length()) {
            // if they arn't even the same length no need to even check
            return false;
        }
        
        return ($this->_unicode == $unicode->_unicode);
    }
    
    /**
    * Appends a given Unicode string to the end of the current one.
    *
    * @access public
    * @param  I18N_UnicodeString $unicode The string to append.
    * @return I18N_UnicodeString The new string created from the appension.
    */
    function append(&$unicode) {
        return new I18N_UnicodeString(array_merge($this->_unicode, $unicode->_unicode), 'Unicode');
    }
    
    /**
    * Used to raise a PEAR_Error.
    *
    * Hopefully this method is never called, but when it is it will include the
    * PEAR class and return a new PEAR_Error.
    *
    * @static
    * @access public
    * @param string $message The error message to raise.
    * @return PEAR_Error A PEAR error message.
    */
    function raiseError($message) {
        include_once('PEAR.php');
    
        return PEAR::raiseError($message);
    }
}
?>
