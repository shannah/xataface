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
 * <p>Returns A tree node for for the Record tree menu (displayed in the left column
 * of the Dataface application window.  This action is exclusively called by the 
 * RecordNavMenu.html template via an HTTPRequest ajax call.</p>
 *
 * <p>If a relationship is specified then this will output a javascript associative
 * 	array of objects.</p>
 *
 * <p>If no relationship is specified then all of the relationships for the found
 * 	  record will be returned in a javascript associative array with the keys 
 * 	  of the array being the names of the relationships.
 * </p>
 * <p>All data returned in JSON format (www.json.org).</p>
 *
 * <h2>Example output:</h2>
 * <p>Given a url: <em>http://yourdomain.com/path.to.app/index.php?-table=profiles&profileid=10</em> and assuming
 * 	that the profiles table has 3 relationships: courses (to the course table), invoices (to the invoice table), and publications (to the pub table).  we would obtain output something like:</p>
 * <code>
 *		{'courses':
 *			{	'course?CourseID=10': {'CourseID': 10, 'CourseTitle': 'Introduction to widgetry', ...},
 *				'course?CourseID=21': {'CourseID': 21, 'CourseTitle': 'Basics of accounting', ...},
 *				...
 *			},
 *		 'invoices':
 *		 	{	'invoice?InvoiceID=2': {'InvoiceID': 2, 'Amount': 20.32, ... },
 *		 		'invoice?InvoiceID=11': {'InvoiceID': 11, 'Amount': 234.89, ...},
 *		 		...
 *		 	},
 *		 'publications':
 *		 	{ ... }
 *		}
 * </code>
 * <p>Alternatively, if you supplied a url: <em>http://yourdomain.com/path.to.app/index.php?-table=profiles&profileid=10&-relationship=courses</em>
 * Then this action would only output the associative array of objects in the courses relationship.  e.g.:</p>
 * <code>
 * 	{	'course?CourseID=10': {'CourseID': 10, 'CourseTitle': 'Introduction to widgetry', ...},
 *		'course?CourseID=21': {'CourseID': 21, 'CourseTitle': 'Basics of accounting', ...},
 *		...
 *	}
 * </code>
 *
 * @author Steve Hannah (shannah@sfu.ca)
 * @created September 20, 2006
 * @sponsored by Advanced Medical (advanced-medical.com)
 *
 */ 
class dataface_actions_ajax_nav_tree_node {

	function handle(&$params){
		
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		if ( !$record ){
			echo '{}';
		}
		
		$relationships = $record->_table->getRelationshipsAsActions();
		if ( isset($_GET['-relationship']) ){
			$relationships = array($relationships[$_GET['-relationship']]);	
		}
		$outerOut = array();
		foreach ($relationships as $relationship){
			$innerOut = array();
			$relatedRecords = $record->getRelatedRecordObjects($relationship['name'],0,60);
			foreach ($relatedRecords as $relatedRecord){
				$domainRecord = $relatedRecord->toRecord();
				$override = array('__title__'=>$relatedRecord->getTitle());
				
				$innerOut[] = "'".$domainRecord->getId()."': ".$domainRecord->toJS(array(), $override);
			}
			if ( count($relationships) > 1 ){
				$outerOut[] = "'".$relationship['name']."': {'__title__': '".$relationship['label']."', '__url__': '".$record->getURL('-action=related_records_list&-relationship='.urlencode($relationship['name']))."','records': {".implode(',',$innerOut)."}}";
			} else {
				$outerOut[] = implode(',',$innerOut);			
			}	
			
		}
		echo '{'.implode(',',$outerOut).'}';
		exit;
		
	}	
}
