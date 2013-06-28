<?php include 'install'.DIRECTORY_SEPARATOR.'install_header.inc.php';?>
		<h1>Archive Installation Results</h1>
		<div id="result"><?php echo $context['result'];?></div>
		<div id="readout">
		<?php foreach ($context['log'] as $line):?>
			<?php echo $line;?><br />
		<?php endforeach;?>
		</div>
		
		<?php
			if ( $_SERVER['DOCUMENT_ROOT'] == substr($_REQUEST['ftp_path'],0,strlen($_SERVER['DOCUMENT_ROOT']))){
				$urlpath = substr($_REQUEST['ftp_path'], strlen($_SERVER['DOCUMENT_ROOT']));
				$url = $_SERVER['HTTP_HOST'];
				if ( $_SERVER['HTTPS'] == 'on' ) $protocol = 'https';
				else $protocol = 'http';
				
				$url = $protocol.$url.$urlpath;
			}
		?>
		
		<?php if ( $url ):?>
			<p>Access your application <a href="<?php echo $url;?>">Here</a></p>
		<?php endif;?>
		<p><a href="installer.php">Return to main menu</a></p>
		
		
<?php include 'install'.DIRECTORY_SEPARATOR.'install_footer.inc.php';?>
