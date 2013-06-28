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
/**
 * Handler for the RecordBrowser_data.  This action will display a series of HTML
 * <OPTION> tags that are intended to be loaded by the RecordBrowser jquery component
 * into a SELECT list dynamically.  This takes the standard Xataface query parameters
 * to dictate which records get returned.  The output will look something like:
 *
 * <option value="1">Steve Hannah</option>
 * <option value="2">Joe Smith</option>
 * ... etc...
 *
 * The option values are the ID of the record.  If the table requested has only a 
 * single column in its primary key, then this will contain only that value.
 * Otherwise it will contain a Xataface record id.
 * The text is the title of the record.
 *
 * @param $_GET['-value'] The name of the field to be used as the value in the <option> tag.
 *		@value : -value=<fieldname> Indicates that we should use the field named <fieldname>
 *			as the value.
 *		@value : -value=__id__  Indicates that we should use the xataface record id.
 *		@value : If this parameter is omitted it will either use the primary key field, if
 *			the primary key is only a single column.  If the primary key contains more than
 *			one column, then the xataface record id is used.
 *
 * @param $_GET['-text'] The name of the field to be used as the text of the <option> tag.
 *		@value : -text=<fieldname> Indicates that we should use the field named <fieldname>
 *			as the text.
 *		@value : -text=__title__ Indicates that we shoudl use the record title.
 *		@value : If this parameter is omitted it will simply use the record title.
 * @return The title of the record.
 *
 * Example usage:
 *
 * index.php?-action=RecordBrowser_data&-table=people
 * index.php?-action=RecordBrowser_data&-table=people&-search=Hannah
 *
 * This was created specifically to be used by the RecordBrowser jquery
 * component that is part of the lookup widget.
 *
 * Related files:
 * Dataface/FormTool/lookup.php
 * HTML/QuickForm/lookup.php
 * js/RecordBrowser
 *
 *
 * @created July 15, 2009
 * @author Steve Hannah <shannah@sfu.ca>
 */
class dataface_actions_RecordBrowser_data {
	function handle(&$params){
	    @session_write_close();
		$app =& Dataface_Application::getInstance();
		//$out = array();
		$query =& $app->getQuery();
		$records = df_get_records_array($query['-table'], $query);
		header("Content-type: text/html; charset=".$app->_conf['oe']);
		echo '<option value="">(None)</option>'."\n";
		foreach ($records as $record){
		
			// First lets get the value that we are using for this option
			$value = null;
			if ( @$query['-value'] == '__id__' ){
				// Use the record id as the value
				$value = $record->getId();
			} else if ( @$query['-value'] ){
				// We have an explicitly specified column to use as the key.
				$value = $record->val($query['-value']);
				
			} else if ( count($record->_table->keys()) > 1 ){
				// This record has a compound key and no value column was specified
				// so we use the record id.
				$value = $record->getId();
			} else {
				// This record has a single key column so we return its value
				$tkeys = $record->_table->keys();
				$tkeysKeys = array_keys($tkeys);
				$firstKey = reset($tkeysKeys);
				$value = $record->val($firstKey);
				
			}
			
			// Now let's get the text that we are using for this option
			$text = null;
			switch (strval(@$query['-text'])){
				case '':
				case '__title__':
					$text = $record->getTitle();
					break;
				default:
					$text = $record->display($query['-text']);
					break;
			
			}
			echo '<option value="'.df_escape($value).'">'.df_escape($text).'</option>'."\n";
			
		}
		exit;
		
		
	}
}
