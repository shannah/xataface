<?php
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'ftp.api.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'ftp.class.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Archive'.DIRECTORY_SEPARATOR.'Tar.php';

class FTPExtractor {
	var $archive;
	var $ftp;
	var $destination;
	var $source;
	
	function FTPExtractor(&$archive){
		$this->archive =& $archive;
	}
	
	function connect($host, $user, $password ){
		$this->ftp = ftp_connect($host);
		if ( !$this->ftp ){
			return PEAR::raiseError("Failed to connect to FTP host '$host'");
		}
		
		$res = ftp_login($this->ftp, $user, $password);
		if ( !$res ){
			return PEAR::raiseError("Incorrect username or password while trying to connect to FTP host '$host'");
		}
		
		return true;
	}
	
	function extract($destination, $sourcePath){
		$log = array();
		$res = ftp_chdir($this->ftp, $destination);
		
		if ( !$res ) return PEAR::raiseError("Directory $destination does not exist on the server.");
		//print_r($this->archive->listContent());exit;
		foreach ( $this->archive->listContent() as $item ){
			//if ( !$item['filename'] ) continue;
			//if ( $item['filename']{ strlen($item['filename'])-1 } == '/' ) $item['filename'] = substr($item['filename'],0,strlen($item['filename'])-1);
			
			$log[] =  "Extracting ".$item['filename']." to $destination";
			//print_r($item);
			if ( $item['typeflag'] == 5){
				//directory
				//print_r();
				//$size = ftp_size($this->ftp, $item['filename']);
				//echo "Size: $size";
				if ( !ftp_nlist($this->ftp, $item['filename']) ){
					$res = ftp_mkdir($this->ftp, $item['filename']);
					if ( !$res ) return PEAR::raiseError("Extraction failed.  Failed to create directory ".$item['filename']);
					ftp_chmod($this->ftp, $item['mode'], $item['filename']);
				}

			} else {	
				// a file
				$fpath = tempnam(null, $item['filename']);
				file_put_contents($fpath, $this->archive->extractInString($item['filename']));
				$res = ftp_put($this->ftp, $item['filename'], $fpath, FTP_BINARY);
				if ( !$res ){
					$dirname = dirname($item['filename']);
					if ( $dirname and $dirname{strlen($dirname)-1} == '/' ) $dirname = substr($dirname,0,strlen($dirname)-1);
					
					$dirstack = array();
					
					while ( !($ls = ftp_nlist($this->ftp, $dirname)) or (is_array($ls) and count($ls) < 2) ){
	
						$dirstack[] = $dirname;
						$dirname = dirname($dirname);
						if ( $dirname and $dirname{strlen($dirname)-1} == '/' ) $dirname = substr($dirname,0,strlen($dirname)-1);
						if ( dirname($dirname) == $dirname) break;
					}
					
					while ( count($dirstack) > 0  ){
						$dirname = array_pop($dirstack);
						$dres = ftp_mkdir($this->ftp, $dirname);
						//if ( !$dres ) return PEAR::raiseError("Failed to create directory $dirname");
						ftp_chmod($this->ftp, 0755, $dirname);
					}
					$res = ftp_put($this->ftp, $item['filename'], $fpath, FTP_BINARY);
				}
				
				if ( !$res ) return PEAR::raiseError("Failed to put file ".$item['filename']);
				ftp_chmod($this->ftp, $item['mode'], $item['filename']);
					
			}
		}
		$result = "Successfully installed at $destination";
		return array('log'=>$log, 'result'=>$result);
	}
	
}
