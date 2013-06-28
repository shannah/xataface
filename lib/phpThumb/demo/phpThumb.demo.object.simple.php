<?php
//////////////////////////////////////////////////////////////
///  phpThumb() by James Heinrich <info@silisoftware.com>   //
//        available at http://phpthumb.sourceforge.net     ///
//////////////////////////////////////////////////////////////
///                                                         //
// phpThumb.demo.object.simple.php                          //
// James Heinrich <info@silisoftware.com>                   //
//                                                          //
// Simplified example of how to use phpthumb.class.php as   //
// an object -- please also see phpThumb.demo.object.php    //
//                                                          //
//////////////////////////////////////////////////////////////

require_once('../phpthumb.class.php');

$phpThumb = new phpThumb();

// set data
$phpThumb->setSourceFilename($_FILES['userfile']['tmp_name']);

// set parameters (see "URL Parameters" in phpthumb.readme.txt)
$phpThumb->setParameter('w', $thumbnail_width);

// generate & output thumbnail
$output_filename = './thumbnails/'.basename($_FILES['userfile']['name']).'_'.$thumbnail_width.'.'.$phpThumb->config_output_format;
if ($phpThumb->GenerateThumbnail()) { // this line is VERY important, do not remove it!
	if ($phpThumb->RenderToFile($output_filename)) {
		// do something on success
		echo 'Successfully rendered to "'.$output_filename.'"';
	} else {
		// do something with debug/error messages
		echo 'Failed:<pre>'.implode("\n\n", $phpThumb->debugmessages).'</pre>';
	}
} else {
	// do something with debug/error messages
	echo 'Failed:<pre>'.$phpThumb->fatalerror."\n\n".implode("\n\n", $phpThumb->debugmessages).'</pre>';
}

?>
