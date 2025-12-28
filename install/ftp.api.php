<?php
	if (!function_exists("ftp_connect")) {
		
		require_once "ftp.class.php";
	
		function ftp_connect($host,$port=21,$timeout=0) { // Opens an FTP connection
			if ($timeout<1) $timeout = 90;
			$ftp = new FTP();
			if (!$ftp->connect($host,$port,$timeout)) return false;
			return $ftp;
		}
		function ftp_login($ftp,$user,$pass) { // Logs in to an FTP connection
			return $ftp->login($user,$pass);
		}
		function ftp_close($ftp) { // Closes an FTP connection
			$ftp->disconnect();
		}

		function ftp_cdup($ftp) { // Changes to the parent directory
			return $ftp->cdup();
		}
		function ftp_chdir($ftp,$directory) { // Changes directories on a FTP server
			return $ftp->chdir($directory);
		}
		function ftp_chmod($ftp,$mode,$filename) { // Set permissions on a file via FTP
			return $ftp->chmod($mode,$filename);
		}
		function ftp_delete($ftp,$path) { // Deletes a file on the FTP server
			return $ftp->delete($path);
		}
		function ftp_exec($ftp,$command) { // Requests execution of a program on the FTP server
			return $ftp->exec($command);
		}
		function ftp_fget($ftp,$handle,$remote_file,$mode,$resumepos=0) { // Downloads a file from the FTP server and saves to an open file
			return $ftp->fget($handle,$remote_file,$mode,$resumepos);
		}
		function ftp_fput($ftp,$remote_file,$handle,$mode,$startpos=0) { // Uploads from an open file to the FTP server
			return $ftp->fput($remote_file,$handle,$mode,$startpos);
		}
		function ftp_get_option($ftp,$option) { // Retrieves various runtime behaviours of the current FTP stream
			return $ftp->get_option($option);
		}
		function ftp_get($ftp,$local_file,$remote_file,$mode,$resumepos=0) { // Downloads a file from the FTP server
			return $ftp->get($local_file,$remote_file,$mode,$resumepos);
		}
		function ftp_mdtm($ftp,$remote_file) { // Returns the last modified time of the given file
			return $ftp->mdtm($remote_file);
		}
		function ftp_mkdir($ftp,$directory) { // Creates a directory
			return $ftp->mkdir($directory);
		}
		function ftp_nb_continue($ftp) { // Continues retrieving/sending a file (non-blocking)
			return $ftp->nb_continue();
		}
		function ftp_nb_fget($ftp,$handle,$remote_file,$mode,$resumepos=0) { // Retrieves a file from the FTP server and writes it to an open file (non-blocking)
			return $ftp->nb_fget($handle,$remote_file,$mode,$resumepos);
		}
		function ftp_nb_fput($ftp,$remote_file,$handle,$mode,$startpos=0) { // Stores a file from an open file to the FTP server (non-blocking)
			return $ftp->nb_fput($remote_file,$handle,$mode,$startpos);
		}
		function ftp_nb_get($ftp,$local_file,$remote_file,$mode,$resumepos=0) { // Retrieves a file from the FTP server and writes it to a local file (non-blocking)
			return $ftp->nb_get($local_file,$remote_file,$mode,$resumepos);
		}
		function ftp_nb_put($ftp,$remote_file,$local_file,$mode,$startpos=0) { // Stores a file on the FTP server (non-blocking)
			return $ftp->nb_put($remote_file,$local_file,$mode,$startpos);
		}
		function ftp_nlist($ftp,$directory="") { // Returns a list of files in the given directory
			return $ftp->nlist($directory);
		}
		function ftp_pasv($ftp,$pasv) { // Turns passive mode on or off
			return $ftp->pasv($pasv);
		}
		function ftp_put($ftp,$remote_file,$local_file,$mode,$startpos=0) { // Uploads a file to the FTP server
			return $ftp->put($remote_file,$local_file,$mode,$startpos);
		}
		function ftp_pwd($ftp) { // Returns the current directory name
			return $ftp->pwd();
		}
		function ftp_quit($ftp) { // Alias of ftp_close
			return $ftp->quit();
		}
		function ftp_raw($ftp,$command) { // Sends an arbitrary command to an FTP server
			return $ftp->raw($command);
		}
		function ftp_rawlist($ftp,$directory="") { // Returns a detailed list of files in the given directory
			return $ftp->rawlist($directory);
		}
		function ftp_rename($ftp,$from,$to) { // Renames a file on the FTP server
			return $ftp->rename($from,$to);
		}
		function ftp_rmdir($ftp,$directory) { // Removes a directory
			return $ftp->rmdir($directory);
		}
		function ftp_set_option($ftp,$option,$value) { // Set miscellaneous runtime FTP options
			return $ftp->set_option($option,$value);
		}
		function ftp_site($ftp,$cmd) { // Sends a SITE command to the server
			return $ftp->site($cmd);
		}
		function ftp_size($ftp,$remote_file) { // Returns the size of the given file
			return $ftp->size($remote_file);
		}
		function ftp_ssl_connect($host,$port=21,$timeout=0) { // Opens an Secure SSL-FTP connection
			if ($timeout<1) $timeout = 90;
			$ftp = new FTP();
			if (!$ftp->ssl_connect($host,$port,$timeout)) return false;
			return $ftp;
		}
		function ftp_systype($ftp) { // Returns the system type identifier of the remote FTP server
			return $ftp->systype();
		}
	}
?>
