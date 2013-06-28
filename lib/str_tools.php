<?php


function str_makerand ($minlength, $maxlength, $useupper, $usespecial, $usenumbers) 
{ 
/*  
Author: Peter Mugane Kionga-Kamau 
http://www.pmkmedia.com 

Description: string str_makerand(int $minlength, int $maxlength, bool $useupper, bool $usespecial, bool $usenumbers)  
returns a randomly generated string of length between $minlength and $maxlength inclusively. 

Notes:  
- If $useupper is true uppercase characters will be used; if false they will be excluded. 
- If $usespecial is true special characters will be used; if false they will be excluded. 
- If $usenumbers is true numerical characters will be used; if false they will be excluded. 
- If $minlength is equal to $maxlength a string of length $maxlength will be returned. 
- Not all special characters are included since they could cause parse errors with queries.  

Modify at will. 
*/ 
    $charset = "abcdefghijklmnopqrstuvwxyz"; 
    if ($useupper)   $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
    if ($usenumbers) $charset .= "0123456789"; 
    if ($usespecial) $charset .= "~@#$%^*()_+-={}|][";   // Note: using all special characters this reads: "~!@#$%^&*()_+`-={}|\\]?[\":;'><,./"; 
    if ($minlength > $maxlength) $length = mt_rand ($maxlength, $minlength); 
    else                         $length = mt_rand ($minlength, $maxlength); 
    for ($i=0; $i<$length; $i++) $key .= $charset[(mt_rand(0,(strlen($charset)-1)))]; 
    return $key; 
}

?>
