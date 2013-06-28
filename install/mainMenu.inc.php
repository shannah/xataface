<?php include 'install'.DIRECTORY_SEPARATOR.'install_header.inc.php';?>
		<h2>Please select your installation action</h2>
		<ol class="main-menu">
			<li><a href="<?php echo $_SERVER['PHP_SELF']?>?-action=db2app">
				<h3><img src="images/preferences-system-windows.png"/> Create application for existing database</h3>
				<p>Use this option if you already have a database on your server
				and you would like to create an application based on this database.</p>
				</a>
			</li>
			
			<li>
				<a href="<?php echo $_SERVER['PHP_SELF']?>?-action=archive2app">
				<h3><img src="images/system-installer.png" /> Install a pre-built application</h3>
				<p>Use this option if you have a ready-made Dataface application
				that you wish to install on this server.</p>
				</a>
			</li>
			<!--
			<li>
				<h3>Generate application from a UML Model</h3>
				<p>Use this option if you have designed an application using a
				UML modelling tool (e.g. Poseidon, ArgoUML, etc...) and you want to 
				convert this model into a Dataface application.</p>
			</li>
			-->
		</ol>
<?php include 'install'.DIRECTORY_SEPARATOR.'install_footer.inc.php';?>
