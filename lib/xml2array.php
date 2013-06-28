<?php
/********************************************************************************
 *
 *  Xataface Web Application Framework for PHP and MySQL
 *  Copyright (C) 2005  Steve Hannah <shannah@sfu.ca>
 *  
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 *  
 *  This library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  Lesser General Public License for more details.
 *  
 *  You should have received a copy of the GNU Lesser General Public
 *  License along with this library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *===============================================================================
 */

// downloaded from http://whoooop.co.uk/xml.txt

function GetXMLTree ($xmldata)
{
	// we want to know if an error occurs
	ini_set ('track_errors', '1');

	$xmlreaderror = false;

	$parser = xml_parser_create ('ISO-8859-1');
	xml_parser_set_option ($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parser_set_option ($parser, XML_OPTION_CASE_FOLDING, 0);
	if (!xml_parse_into_struct ($parser, $xmldata, $vals, $index)) {
		$xmlreaderror = true;
		$err = PEAR::raiseError("XML Read error", 1, E_USER_WARNING, null, "Could not parse xml into structure.");
		return $err;
	}
	xml_parser_free ($parser);

	if (!$xmlreaderror) {
		$result = array ();
		$i = 0;
		$attributes = array();
		if (isset ($vals [$i]['attributes']))
			foreach (array_keys ($vals [$i]['attributes']) as $attkey)
			$attributes [$attkey] = $vals [$i]['attributes'][$attkey];

		$result [$vals [$i]['tag']] = array_merge ($attributes, GetChildren ($vals, $i, 'open'));
	}

	ini_set ('track_errors', '0');
	return $result;
}

function GetChildren ($vals, &$i, $type)
{
	if ($type == 'complete') {
		if (isset ($vals [$i]['value']))
			return ($vals [$i]['value']);
		else
			return '';
	}

	$children = array (); // Contains node data

	/* Loop through children */
	while ($vals [++$i]['type'] != 'close') {
		$type = $vals [$i]['type'];
		// first check if we already have one and need to create an array
		if (isset ($children [$vals [$i]['tag']])) {
			if (is_array ($children [$vals [$i]['tag']])) {
				$temp = array_keys ($children [$vals [$i]['tag']]);
				// there is one of these things already and it is itself an array
				if (is_string ($temp [0])) {
					$a = $children [$vals [$i]['tag']];
					unset ($children [$vals [$i]['tag']]);
					$children [$vals [$i]['tag']][0] = $a;
				}
			} else {
				$a = $children [$vals [$i]['tag']];
				unset ($children [$vals [$i]['tag']]);
				$children [$vals [$i]['tag']][0] = $a;
			}

			$children [$vals [$i]['tag']][] = GetChildren ($vals, $i, $type);
		} else
			$children [$vals [$i]['tag']] = GetChildren ($vals, $i, $type);
		// I don't think I need attributes but this is how I would do them:
		if (isset ($vals [$i]['attributes'])) {
			$attributes = array ();
			foreach (array_keys ($vals [$i]['attributes']) as $attkey)
			$attributes [$attkey] = $vals [$i]['attributes'][$attkey];
			// now check: do we already have an array or a value?
			if (isset ($children [$vals [$i]['tag']])) {
				// case where there is an attribute but no value, a complete with an attribute in other words
				if ($children [$vals [$i]['tag']] == '') {
					unset ($children [$vals [$i]['tag']]);
					$children [$vals [$i]['tag']] = $attributes;
				}
				// case where there is an array of identical items with attributes
				elseif (is_array ($children [$vals [$i]['tag']])) {
					$index = count ($children [$vals [$i]['tag']]) - 1;
					// probably also have to check here whether the individual item is also an array or not or what... all a bit messy
					if ($children [$vals [$i]['tag']][$index] == '') {
						unset ($children [$vals [$i]['tag']][$index]);
						$children [$vals [$i]['tag']][$index] = $attributes;
					}
					$children [$vals [$i]['tag']][$index] = array_merge ($children [$vals [$i]['tag']][$index], $attributes);
				} else {
					$value = $children [$vals [$i]['tag']];
					unset ($children [$vals [$i]['tag']]);
					$children [$vals [$i]['tag']]['value'] = $value;
					$children [$vals [$i]['tag']] = array_merge ($children [$vals [$i]['tag']], $attributes);
				}
			} else
				$children [$vals [$i]['tag']] = $attributes;
		}
	}

	return $children;
}
//$url = "test.xml"; //URL of the XML FEED
//
//$contents = file_get_contents($url);
//
//$data = GetXMLTree ($contents);
//print_r($data);


?>
