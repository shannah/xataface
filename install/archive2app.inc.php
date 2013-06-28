<?php include 'install'.DIRECTORY_SEPARATOR.'install_header.inc.php';?>
<h2>Install Pre-built Application</h2>
<p>This form allows you to install a pre-built Dataface application provided in the form of a TAR archive.</p>
<form <?php echo $context['attributes'];?>>
	<?php echo $context['javascript'].$context['hidden'];?>
	
	<div class="errors">
		<?php if (count($context['errors']) > 0):?>
			<ul>
			<?php foreach ($context['errors'] as $err):?>
				<?php echo $err;?>
			<?php endforeach; ?>
			</ul>
		<?php endif;?>

	
	
	<div id="step1"><h3>Step 1: Choose Application</h3>
		<p>Please select archive file for your application located on your local drive.  The application should be stored as a .tar or .tar.gz archive.</p>
		
		<div><?php echo $context['elements']['archive']['html'];?></div>
		<input type="button" onclick="document.getElementById('step2').style.display=''" value="Proceed to next step" />
	</div>
	
	
	<div id="step2" style="display:none"><h3>Step 2: Enter Database Connection Info</h3>
	
	<p>Please enter the connection information that resulting Xataface application will
	use to connect to the database.</p>
	
		<table>
			<tr><td width="175" valign="top">Database name</td><td><?php echo $context['elements']['database_name']['html'];?>
			<div class="instructions">
				If this database does not exist, it will be created.
			</div>
			
			</td></tr>
			
			<tr><td width="175" valign="top">MySQL Username</td><td><?php echo $context['elements']['mysql_user']['html'];?> Create user? <?php echo $context['elements']['create_user']['html'];?>

				<div class="instructions">
					This is the username that your application should use to connect to the database.
					This install process uses the <em><?php echo $_SERVER['PHP_AUTH_USER'];?></em> user to create the database 
					(and to create this user if you selected the <em>Create User</em> checkbox above), but username
					and password you enter here will be used by the installed application to interact with
					the database.
				</div>
			
			</td></tr>
			
			<tr><td>MySQL Password</td><td><?php echo $context['elements']['mysql_password']['html'];?></td></tr>
			

		</table>
		
		
		<div>If database connection info is correct, <input type="button" onclick="document.getElementById('step3').style.display='';return false;" value="proceed to next step" /></div>
		
	</div>
	
	
	<div id="step3" style="display:none"><h3>Step 3: Select Installation Type</h3>
	
		<p>You can either install the application directly on your web server (requires FTP
		connection information), or download the application as a tar archive, so that you
		can install it on your server manually.</p>
		
		<p>Please select your preferred method of installation:
		
		<?php echo $context['elements']['install_type']['html'];?></p>
		
	</div>
	
	
	<div id="step4" style="display:none"><h3>Step 4: FTP Connection Details</h3>
	
		<p>In order to install the application on your web server, Xataface needs to know
		the FTP connection details to connect to the server.  This is because Xataface
		will make an FTP connection to your web server and copy the application directly 
		to a directory of your choosing.</p>
		
		<fieldset>
			<legend>FTP Connection Info</legend>
			<table>
				<?php $f =& $context['sections']['ftp_info']['elements']; ?>
				<tr><td>Host</td><td><?php echo $f['ftp_host']['html'];?> Use SSL: <?php echo $f['ftp_ssl']['html'];?>
				<div class="instructions">
					e.g. weblite.ca
				</div>
				
				
				</td></tr>
				
				<tr><td valign="top">Path</td><td><?php echo $f['ftp_path']['html'];?> (e.g. /var/www)
				<div class="instructions">This should be the parent directory of your application.  Your application will be created in a directory of the same name as the database.  E.g. If your database is named <em>my_db</em> and you enter path <em>/var/www</em> in this field, then this installer will create the directory <em>/var/www/my_db</em> for your application.</div>
				
				</td></tr>
				
				<tr><td>Username</td><td><?php echo $f['ftp_username']['html'];?></td></tr>
				
				<tr><td>Password</td><td><?php echo $f['ftp_password']['html'];?></td></tr>
				
			</table>
			
			<p><input type="button" onclick="testftp(document.getElementById('fromarchive')); return false;" value="Test FTP Connection"></p>
			<div id="ftp-test-results"></div>
			<p>If FTP connection info is correct, <input type="button" onclick="document.getElementById('submitstep').style.display='';return false;" value="proceed to next step"></p>
		</fieldset>
	</div>
	
	<div id="submitstep" style="text-align:center; display:none"><?php echo $f['submit']['html'];?></div>

</form>


<?php include 'install'.DIRECTORY_SEPARATOR.'install_footer.inc.php';?>
