<?php

/**
* Using I18Nv2_Locale
* ===================
*
* I18Nv2_Locale is a formatter object that provides functionality to format
* dates, times, numbers and currencies in locale dependent conventions.
* 
* $Id: using_I18Nv2_Locale.php,v 1.2 2005/01/05 09:26:18 mike Exp $
*/

require_once 'I18Nv2.php';

$locale = &I18Nv2::createLocale('de_AT');

echo "de_AT\n=====\n";
echo "Format a currency value of 2000: ",
    $locale->formatCurrency(2000, I18Nv2_CURRENCY_INTERNATIONAL), "\n";

echo "Format todays date:              ",
    $locale->formatDate(null, I18Nv2_DATETIME_FULL), "\n";

echo "Format current time:             ",
    $locale->formatTime(null, I18Nv2_DATETIME_SHORT), "\n";


$locale->setLocale('en_GB');

echo "\nen_GB\n=====\n";
echo "Format a currency value of 2000: ",
    $locale->formatCurrency(2000, I18Nv2_CURRENCY_INTERNATIONAL), "\n";

echo "Format todays date:              ",
    $locale->formatDate(null, I18Nv2_DATETIME_FULL), "\n";

echo "Format current time:             ",
    $locale->formatTime(null, I18Nv2_DATETIME_SHORT), "\n";

?>
