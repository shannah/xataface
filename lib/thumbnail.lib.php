<?php
class Xataface_Thumbnail {
	
	const JPEG_QUALITY = 90;
	const PNG_COMPRESSION = 0;
	
	
	public static function outputThumbnail($image_path, $max_width=100, $max_height=100, $format=null){
			
			
		
		
	    if ( !defined('MEDIA_CACHE_PATH') ){
			define('MEDIA_CACHE_PATH', DATAFACE_SITE_PATH.DIRECTORY_SEPARATOR.'templates_c'.DIRECTORY_SEPARATOR.'mediacache');
		}
		
		if ( !is_dir(MEDIA_CACHE_PATH) ){
			mkdir(MEDIA_CACHE_PATH);
		}
		
		 
		
		
		
		
		//$image_type = exif_imagetype($image_path);
		
		$size = GetImageSize($image_path);
		$width = $size[0];
		$height = $size[1];
		$image_type = $size[2];
		
		
		if ( !$max_width && !$max_height && !isset($format) ){
			// No max width is set.. so we can just return the image unaltered
			header('Content-type: '.image_type_to_mime_type($image_type));
			header('Connection: close');
			header('Cache-Control: max-age=3600');
			header('Content-Length: '.filesize($image_path));
			echo file_get_contents($image_path);
			flush();
			exit;
		}
		
		//$max_height = @$_GET['max_height'] ? $_GET['max_height'] : 1000;
		//$max_width = @$_GET['max_width'] ? $_GET['max_width'] : 1000;
		
		$filterstr = array();
		
		$cachestr = '&max_width='.$max_width.'&max_height='.$max_height;
		if (isset($format) ) $cachestr .= '&format='.$format;
		$cache_code = md5(md5($image_path).'?'.$cachestr);
		$cache_path = MEDIA_CACHE_PATH.DIRECTORY_SEPARATOR.$cache_code;
		if ( file_exists($cache_path) and filemtime($cache_path) > filemtime($image_path) ){
			header('Content-type: '.image_type_to_mime_type($image_type));
			header('Cache-Control: max-age=360000');
			header('Conent-Length: '.filesize($cache_path));
			header('Connection: close');
			$fp = fopen($cache_path, "rb");
			//start buffered download
			while(!feof($fp))
					{
					print(fread($fp,1024*16));
					@flush();
					@ob_flush();
					}
			fclose($fp);
			//echo file_get_contents($cache_path);
			exit;
		}
		
		
		// get the ratio needed
		$x_ratio = $max_width / $width;
		$y_ratio = $max_height / $height;
		
		// if image allready meets criteria, load current values in
		// if not, use ratios to load new size info
		if ( ($width <= $max_width) && ($height <= $max_height) ) {
		  $tn_width = $width;
		  $tn_height = $height;
		} else if (($x_ratio * $height) < $max_height) {
		  $tn_height = ceil($x_ratio * $height);
		  $tn_width = $max_width;
		} else {
		  $tn_width = ceil($y_ratio * $width);
		  $tn_height = $max_height;
		}
		
		
		// read image
		//$src = ImageCreateFromJpeg($image);
		
		switch ( $image_type ){
			case IMAGETYPE_GIF: $src = imagecreatefromgif($image_path);break;
			case IMAGETYPE_PNG: $src = imagecreatefrompng($image_path);break;
			case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($image_path);break;
			default: $src = imagecreatefromstring(file_get_contents($image_path)); break;
		}
		//imagejpeg($im);
		
		// set up canvas
		$dst = imagecreatetruecolor($tn_width,$tn_height);
		
		
		if ( ($image_type == IMAGETYPE_GIF) || ($image_type == IMAGETYPE_PNG) ) {
			$trnprt_indx = imagecolortransparent($src);
			
			// If we have a specific transparent color
			if ($trnprt_indx >= 0) {
				
				// Get the original image's transparent color's RGB values
				$trnprt_color = imagecolorsforindex($src, $trnprt_indx);
				
				// Allocate the same color in the new image resource
				$trnprt_indx = imagecolorallocate($dst, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
				
				// Completely fill the background of the new image with allocated color.
				imagefill($dst, 0, 0, $trnprt_indx);
				
				// Set the background color for new image to transparent
				imagecolortransparent($dst, $trnprt_indx);
		 
			 
			 } 
			 // Always make a transparent background color for PNGs that don't have one allocated already
			 elseif ($image_type == IMAGETYPE_PNG) {
			 
				 // Turn off transparency blending (temporarily)
				 imagealphablending($dst, false);
				 
				 // Create a new transparent color for image
				 $color = imagecolorallocatealpha($dst, 0, 0, 0, 127);
				 
				 // Completely fill the background of the new image with allocated color.
				 imagefill($dst, 0, 0, $color);
				 
				 // Restore transparency blending
				 imagesavealpha($dst, true);
			 }
		 }
		
		
		
		
		
		// copy resized image to new canvas
		ImageCopyResampled($dst, $src, 0, 0, 0, 0, $tn_width,$tn_height,$width,$height);
		
		// send the header and new image
		
		if ( !$format ) $format = '';
		
		switch (strtolower($format)){
			case 'gif': $format = IMAGETYPE_GIF;break;
			case 'jpg':
			case 'jpeg':
				$format = IMAGETYPE_JPEG;break;
			case 'png':
				$format = IMAGETYPE_PNG;break;
			default:
				$format = $image_type;
		}
		
		switch ($format){
		
			case IMAGETYPE_GIF:
				header("Content-type: image/gif");
				header('Cache-Control: max-age=3600');
				imagegif($dst,null,-1);
				imagegif($dst,$cache_path,-1);
				break;
			case IMAGETYPE_JPEG:
				header("Content-type: image/jpeg");
				header('Cache-Control: max-age=3600');
				imagejpeg($dst,null,self::JPEG_QUALITY);
				imagejpeg($dst,$cache_path,self::JPEG_QUALITY);
				break;
			case IMAGETYPE_PNG:
				header("Content-type: image/png");
				header('Cache-Control: max-age=3600');
				imagepng($dst,null,self::PNG_COMPRESSION);
				imagepng($dst,$cache_path,self::PNG_COMPRESSION);
				break;
		}
		//ImageJpeg($dst, null, -1);
		
		// clear out the resources
		ImageDestroy($src);
		ImageDestroy($dst);
		
	
	
	
	}

}
