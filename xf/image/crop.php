<?php
namespace xf\image;

class Crop {
    var $jpegImageQuality = 90;
    var $pngImageQuality = 9;

    function fit($srcPath, $destPath, $width, $height, $mimetype=null) {
        return $this->crop($srcPath, $destPath, 0, 0, 0, 0, null, $width, $height, $mimetype);
    }

    function fill($srcPath, $destPath, $width, $height, $mimetype=null) {
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
		$aspectIn = $imgW/(float)$imgH;
		/*
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
		*/
		if ($aspectIn > $aspectOut) {
			// input aspect is wider than output aspect
			// so output will be scaled to height.
			$scale = $ry;
			
			$scaledWidth = $width / $scale;
			
			$x0 = abs(($imgW - $scaledWidth)/2);
			$x1 -= $x0;
		} else {
			// input aspect is narrower than output aspect
			// so output will be scaled to width
			$scale = $rx;
			$scaledHeight = $height / $scale;
			$y0 = abs(($imgH - $scaledHeight)/2);
			$y1 -= $y0;
		}
		
        return $this->crop($srcPath, $destPath, $x0, $y0, $x1, $y1, null, $width, $height, $mimetype);
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
            $maxHeight = null,
			$mimetype = null) {
        $image = $srcPath;
    	$file_name = $srcPath;
    	$cachedir = dirname($destPath);
    	if (!file_exists($cachedir)) {
    	    throw new \Exception("Destination directory does not exist");
    	}

    	$cachepath = $destPath;
		
        $type = strtolower(substr(strrchr($file_name, '.'), 1));
		if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
			if ($mimetype) {
				switch ($mimetype) {
					case 'image/jpg': $type = 'jpg'; break;
					case 'image/jpeg': $type = 'jpg'; break;
					case 'image/png': $type = 'png'; break;
					case 'image/gif': $type = 'gif'; break;
					default: $type = 'png';
				}
			}
		}
    	//$mimetype = null;
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
				error_log("Failed to crop image.  Could not find type.  mimetype=$mimetype, srcPath=$srcPath");
    			return false;
    	}

    	$src = @$src_func($file_name);
        if ($src and $type == 'png') {
            imagealphablending($src, false);
            imagesavealpha($src, true);
        }
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
			return true;
    	}

    	if ( $width != $x2-$x1 or $height != $y2-$y1 ){
    		$dest = imagecreatetruecolor($width, $height);
            //$transparentColor = imagecolortransparent($src);
            //echo "Transparent color $transparentColor";exit;
            if($type == "gif" or $type == "png"){
                imagecolortransparent($dest, imagecolorallocatealpha($dest, 0, 0, 0, 127));
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
              }
    		imagecopyresampled($dest, $src, 0, 0, $x1, $y1, $width, $height, $x2-$x1, $y2-$y1);
            
    	} else {
    		$dest = imagecreatetruecolor($x2-$x1, $y2-$y1);
            if($type == "gif" or $type == "png"){
                imagecolortransparent($dest, imagecolorallocatealpha($dest, 0, 0, 0, 127));
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
              }
    		imagecopy($dest, $src, 0, 0, $x1, $y1, $x2-$x1, $y2-$y1);

    	}
		
    	$write_func($dest, $cachepath, $image_quality);
		if (!file_exists($cachepath)) {
			throw new Exception("Failed to write to $cachepath");
		}
    	if ($type == 'jpg' or $type == 'jpeg') {
    		// For jpegs we need to restore the color profile
    		try {
    			require_once('xf/image/JPEG_ICC.php');
    			$o = new JPEG_ICC();
    			$o->LoadFromJPEG($file_name);
    			$o->SaveToJPEG($cachepath);
    		} catch (\Exception $ex){}
    	}
		return true;
    }
    
    function imagecopyresampledalpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
        if (!imageistruecolor($src_im)) {
          $original_transparency = imagecolortransparent($src_im);
          //we have a transparent color
          if ($original_transparency >= 0) {
            //get the actual transparent color
            $rgb = imagecolorsforindex($src_im, $original_transparency);
            $original_transparency = ($rgb['red'] << 16) | ($rgb['green'] << 8) | $rgb['blue'];
            //change the transparent color to black, since transparent goes to black anyways (no way to remove transparency in GIF)
            imagecolortransparent($src_im, imagecolorallocate($src_im, 0, 0, 0));
          }

          imagealphablending($src_im, false);
          imagesavealpha($src_im, true);
          imagecopyresampled($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
          $img = $dst_im;
          //remake transparency (if there was transparency)
          if ($original_transparency >= 0) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            for ($x = $dst_x; $x < $dst_x+$dst_w; $x++)
              for ($y = $dst_y; $y < $dst_y+$dst_h; $y++)
                if (imagecolorat($img, $x, $y) == $original_transparency)
                  imagesetpixel($img, $x, $y, 127 << 24);
          }
        } else {
            imagecopyresampled($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        }
    }
    
    function imagecopyalpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h) {
        if (!imageistruecolor($src_im)) {
          $original_transparency = imagecolortransparent($src_im);
          //we have a transparent color
          if ($original_transparency >= 0) {
            //get the actual transparent color
            $rgb = imagecolorsforindex($src_im, $original_transparency);
            $original_transparency = ($rgb['red'] << 16) | ($rgb['green'] << 8) | $rgb['blue'];
            //change the transparent color to black, since transparent goes to black anyways (no way to remove transparency in GIF)
            imagecolortransparent($src_im, imagecolorallocate($src_im, 0, 0, 0));
          }

          imagealphablending($src_im, false);
          imagesavealpha($src_im, true);
          imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
          $img = $dst_im;
          //remake transparency (if there was transparency)
          if ($original_transparency >= 0) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            for ($x = $dst_x; $x < $dst_x+$src_w; $x++)
              for ($y = $dst_y; $y < $dst_y+$src_h; $y++)
                if (imagecolorat($img, $x, $y) == $original_transparency)
                  imagesetpixel($img, $x, $y, 127 << 24);
          }
        } else {
            imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
        }
    }
}
