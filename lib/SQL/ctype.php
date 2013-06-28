<?php
/**
 * PHP ctype compatibility functions. See the PHP ctype module for more
 * information on usage.
 *
 * @author John Millaway
 * @author Brent Cook
 * 
 * Note: These functions expect an integer argument, like the C versions
 * To use with a PHP character, use ord($c). These functions do not support
 * string arguments like their PHP extension counterparts
 */
if (!extension_loaded('ctype')) {
    function ctype_alnum($c) {
        global $ctype__;
        return ($ctype__[$c] & 7); // (1 | 2 | 4)
    }
    function ctype_alpha($c) {
        global $ctype__;
        return ($ctype__[$c] & 3); // (1 | 2)
    }
    function ctype_cntrl($c) {
        global $ctype__;
        return ($ctype__[$c] & 40);
    }
    function ctype_digit($c) {
        global $ctype__;
        return ($ctype__[$c] & 4);
    }
    function ctype_graph($c) {
        global $ctype__;
        return ($ctype__[$c] & 27); // (20 | 1 | 2 | 4)
    }
    function ctype_lower($c) {
        global $ctype__;
        return ($ctype__[$c] & 2);
    }
    function ctype_print($c) {
        global $ctype__;
        return ($ctype__[$c] & 227); // (20 | 1 | 2 | 4 | 200)
    }
    function ctype_punct($c) {
        global $ctype__;
        return ($ctype__[$c] & 20);
    }
    function ctype_space($c) {
        global $ctype__;
        return ($ctype__[$c] & 10);
    }
    function ctype_upper($c) {
        global $ctype__;
        return ($ctype__[$c] & 1);
    }
    function ctype_xdigit($c) {
        global $ctype__;
        return ($ctype__[$c] & 104); // (100 | 4));
    }
    $ctype__ =
    array(32,32,32,32,32,32,32,32,32,40,40,40,40,40,32,32,32,32,32,32,32,32,32,
          32,32,32,32,32,32,32,32,32,-120,16,16,16,16,16,16,16,16,16,16,16,16,
          16,16,16,4,4,4,4,4,4,4,4,4,4,16,16,16,16,16,16,16,65,65,65,65,65,65,
          1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,16,16,16,16,16,16,66,66,66,
          66,66,66,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,16,16,16,16,32,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
}
?>
