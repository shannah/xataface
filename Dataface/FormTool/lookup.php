<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */

$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['lookup'] = array('HTML/QuickForm/lookup.php', 'HTML_QuickForm_lookup');

/**
 * Wrapper class for the lookup widget.  The lookup widget is specified in the 
 * fields.ini file with widget:type=lookup.  It requires the widget:table parameter
 * to be set to the table that is being looked up.
 *
 * @created July 15, 2009
 * @author Steve Hannah <shannah@sfu.ca>
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_lookup {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		/*
		 * This field uses a calendar widget
		 */
		
		$widget =& $field['widget'];
		$factory =& Dataface_FormTool::factory();
		$el =& $factory->addElement('lookup', $formFieldName, $widget['label']);
		if ( PEAR::isError($el) ) return $el;
		$el->setProperties($widget);
	
		return $el;
	}
}
