<?php

$inputPath = null;
$outputPath = null;
$dicts = array();

foreach ($argv as $arg ){
	if ( preg_match('/\.csv$/', $arg) ){
		$inputPath = $arg;
		continue;
	}
	
	if ( preg_match('/\.php$/', $arg )  ) continue;
	
	if ( is_dir($arg) ){
		$outputPath = dirname($arg).'/';
	} else {
		$outputPath = $arg.'.';
	}
}

if ( !isset($outputPath) ){

	fwrite( STDERR, "No output path was supplied.\n");
	exit;
}


if ( !isset($inputPath) ){
	fwrite(STDERR, "No input CSV file was specified.\n");
	exit;

}



$fp = fopen($inputPath, 'r');
if ( !$fp ){
	fwrite(STDERR, "The Input file $inputPath could not be opened for reading.\n");
	exit;
}

$filenames = fgetcsv($fp);
array_shift($filenames); // get rid of Key column.
$filenames = array_map('basename', $filenames);
foreach ( $filenames as $file ){
	$dicts[$file] = array();
}

while ( $row = fgetcsv($fp) ){
	$key = array_shift($row);
	foreach ( $filenames as $i=>$file ){
		if ( isset($row[$i]) and !empty($row[$i]) ){
                        $row[$i] = preg_replace(
                            array(
                                '/&uuml;/',
                                '/&auml;/',
                                '/&ouml;/',
                                '/&szlig;/'
                            ),
                            array(
                                'ü',
                                'ä',
                                'ö',
                                'ß'
                            ),
                            $row[$i]
                        );
			$dicts[$file][$key] = $row[$i];
		}
	}
}
fclose($fp);

foreach ( $dicts as $file => $contents ){
	
	$df = fopen($outputPath.$file, 'w');
	foreach ($contents as $key=>$val){
		fwrite($df, $key.' = "'.str_replace('"', '"XATAFACEQ"', $val).'"'."\r\n");
		
	}
	fclose($df);

}

fwrite(STDOUT, "Conversions complete.\n");






