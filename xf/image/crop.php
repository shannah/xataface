<?php
namespace xf\image;

class Crop {
    var $jpegImageQuality = 90;
    var $pngImageQuality = 9;

    function fit($srcPath, $destPath, $width, $height) {
        $this->crop($srcPath, $destPath, 0, 0, 0, 0, null, $width, $height);
    }

    function fill($srcPath, $destPath, $width, $height) {
        $size = getimagesize($srcPath);
        $imgW = $size[0];
        $imgH = $size[1];
        $rx = $width/(float)$imgW;
        $ry = $height/(float)$imgH;
        $x0 = 0;
        $x1 = $imgW;
        $y0 = 0;
        $y1 = $imgH;
        $aspectOut = $width/(float)$height;

        if ($rx < 1 and $rx >= $ry) {
            $y1 = $imgW / $aspectOut;
            $diff = $imgH - $y1;
            $y0 += $diff/2;
            $y1 -= $diff/2;

        } else if ($ry < 1 and $rx < $ry) {
            $x1 = $imgW * $aspectOut;
            $diff = $imgW - $x1;
            $x0 += $diff/2;
            $x1 -= $diff/2;
        }

        $this->crop($srcPath, $destPath, $x0, $x1, $y0, $y1, null, $width, $height);
    }

    function crop(
            $srcPath,
            $destPath,
            $x1,
            $y1,
            $x2,
            $y2,
            $refWidth = null,
            $maxWidth = null,
            $maxHeight = null) {
        $image = $srcPath;
    	$file_name = $srcPath;
    	$cachedir = dirname($destPath);
    	if (!file_exists($cachedir)) {
    	    throw new \Exception("Destination directory does not exist");
    	}

    	$cachepath = $destPath;
        $type = strtolower(substr(strrchr($file_name, '.'), 1));

    	$mimetype = null;
    	switch ($type) {
    		case 'jpg':
    		case 'jpeg':
    			$src_func = 'imagecreatefromjpeg';
    			$write_func = 'imagejpeg';
    			$image_quality = $this->jpegImageQuality;
    			$mimetype = 'image/jpeg';
    			break;
    		case 'gif':
    			$src_func = 'imagecreatefromgif';
    			$write_func = 'imagegif';
    			$image_quality = null;
    			$mimetype = 'image/gif';
    			break;
    		case 'png':
    			$src_func = 'imagecreatefrompng';
    			$write_func = 'imagepng';
    			$image_quality = $this->pngImageQuality;
    			$mimetype = 'image/png';
    			break;
    		default:
    			return false;
    	}

    	$src = @$src_func($file_name);
    	if (!$src) {
    		$src = imagecreatefromstring(file_get_contents($file_name));
    	}

    	$width = imagesx($src);
    	$height = imagesy($src);
    	if ($x2 == 0 && $x1 == 0) {
    	    $x2 = $width;
    	}
    	if ($y2 == 0 && $y1 == 0) {
    	    $y2 = $height;
    	}

    	if (isset($refWidth) ){
    		$factor = $width / $refWidth;
    		$x1 *= $factor;
    		$y1 *= $factor;
    		$x2 *= $factor;
    		$y2 *= $factor;
    	}

    	$width = $x2-$x1;
    	$height = $y2-$y1;

    	if ( isset($maxWidth) ){
    		if ( $width > $maxWidth ){
    			$width = $maxWidth;
    			$height = ($y2-$y1) * $width / ($x2-$x1);
    		}
    	}

    	if ( isset($maxHeight) ){
    		if ( $height > $maxHeight ){
    			$oldHeight = $height;
    			$height = $maxHeight;
    			$width = $width * $height / $oldHeight;
    		}
    	}
    	//convert image -resize "275x275^" -gravity center -crop 275x275+0+0 +repage resultimage

    	if ($x1 == 0 and $y1 == 0 and $x2 == $width and $y2 == $height) {
    	    // just copy the image... it's already the right size
            if (!copy($srcPath, $destPath)) {
                throw new \Exception("Failed to copy image to destination");
            }

            return;
    	}

    	if ( $width != $x2-$x1 or $height != $y2-$y1 ){
    		$dest = imagecreatetruecolor($width, $height);
    		imagecopyresampled($dest, $src, 0, 0, $x1, $y1, $width, $height, $x2-$x1, $y2-$y1);
    	} else {
    		$dest = imagecreatetruecolor($x2-$x1, $y2-$y1);
    		imagecopy($dest, $src, 0, 0, $x1, $y1, $x2-$x1, $y2-$y1);

    	}

    	$write_func($dest, $cachepath, $image_quality);
    	if ($type == 'jpg' or $type == 'jpeg') {
    		// For jpegs we need to restore the color profile
    		try {
    			require_once('xf/image/JPEG_ICC.php');
    			$o = new JPEG_ICC();
    			$o->LoadFromJPEG($file_name);
    			$o->SaveToJPEG($cachepath);
    		} catch (\Exception $ex){}
    	}
    }
}
