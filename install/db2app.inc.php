<?php include 'install'.DIRECTORY_SEPARATOR.'install_header.inc.php';?>
<h2><img src="images/preferences-system-windows.png"/> Create Application from Existing Database</h2>
<p>This form allows you to create a Dataface user interface for an existing MySQL database.</p>

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

	
	
	<div id="step1"><h3>Step 1: Select database</h3>
		<p>Please select that database for which you wish to build a Xataface application.</p>
		
		<div><?php echo $context['elements']['database_name']['html'];?></div>
	</div>
	
	
	<div id="step2" style="display:none"><h3>Step 2: Enter Database Connection Info</h3>
	
	<p>Please enter the connection information that resulting Xataface application will
	use to connect to the database.</p>
	
		<table>
		
			<tr><td>MySQL Username</td><td><?php echo $context['sections']['db_info']['elements']['mysql_user']['html'];?></td></tr>
			
			<tr><td>MySQL Password</td><td><?php echo $context['sections']['db_info']['elements']['mysql_password']['html'];?></td></tr>
			
		</table>
		<input type="button" onclick="testdb(document.getElementById('db2app'));return false;" value="Test DB Connection" /><div id="db-test-results" ></div>
		
		<div>If database connection info is correct, <input type="button" onclick="document.getElementById('step3').style.display='';return false;" value="proceed to next step" /></div>
		
	</div>
	
	
	<div id="step3" style="display:none"><h3>Step 3: Select Installation Type</h3>
	
		<p>You can either install the application directly on your web server (requires FTP
		connection information), or download the application as a tar archive, so that you
		can install it on your server manually.</p>
		
		<p>Please select your preferred method of installation:
		
		<?php echo $context['sections']['db_info']['elements']['install_type']['html'];?></p>
		
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
				<tr><td>Host</td><td><?php echo $f['ftp_host']['html'];?> Use SSL: <?php echo $f['ftp_ssl']['html'];?></td><td class="instructions">e.g. weblite.ca</td></tr>
				
				<tr><td valign="top">Path</td><td><?php echo $f['ftp_path']['html'];?>
				<div class="instructions">This should be the parent directory of your application.  Your application will be created in a directory of the same name as the database.  E.g. If your database is named <em>my_db</em> and you enter path <em>/var/www</em> in this field, then this installer will create the directory <em>/var/www/my_db</em> for your application.</div>
				
				</td><td valign="top" class="instructions">e.g. /var/www </td></tr>
				
				<tr><td>Username</td><td><?php echo $f['ftp_username']['html'];?></td><td></td></tr>
				
				<tr><td>Password</td><td><?php echo $f['ftp_password']['html'];?></td><td></td></tr>
				
			</table>
			
			<p><input type="button" onclick="testftp(document.getElementById('db2app')); return false;" value="Test FTP Connection" />Test FTP Connection</p>
			<div id="ftp-test-results"></div>
			<p>If FTP connection info is correct, <input type="button" onclick="document.getElementById('submitstep').style.display='';return false;" value="proceed to next step" /></p>
		</fieldset>
	</div>
	
	<div id="submitstep" style="text-align:center; display:none"><?php echo $f['submit']['html'];?></div>

</form>

<?php include 'install'.DIRECTORY_SEPARATOR.'install_footer.inc.php';?>
