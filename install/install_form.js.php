<?php
header('Content-type: text/javascript');

?>

/*------------------------------------------------------------------------------
 * End redefinition of window.alert function
 *----------------------------------------------------------------------------*/

function fieldInfo(id){
	alert(info[id]);
}

function testftp(form){
	
	postDataReturnText('installer.php','-action=testftp&-ftphost='+escape(form.ftp_host.value)+'&-ftpuser='+escape(form.ftp_username.value)+'&-ftppass='+escape(form.ftp_password.value)+'&-ftppath='+escape(form.ftp_path.value), function(result){
		var resultDiv = document.getElementById('ftp-test-results');
		eval('var results = '+result+';');
		resultDiv.innerHTML = results.message;
		});
	

}

function testdb(form){
	postDataReturnText('installer.php','-action=testdb&-dbname='+escape(form.database_name.options[form.database_name.selectedIndex].value)+'&-dbuser='+escape(form.mysql_user.value)+'&-dbpass='+escape(form.mysql_password.value), function(result){
		var resultDiv = document.getElementById('db-test-results');
		eval('var results = '+result+';');
		resultDiv.innerHTML = results.message;
	});
}


var listeners = {
	'database_name' : {
		'onchange' : function(fld){
			
			var option = fld.options[fld.selectedIndex].value;
			if ( option ){
				document.getElementById('step2').style.display='';
			} else {
				document.getElementById('step2').style.display='none';
			}
		
		}
	},
	'install_type': {
		'onchange': function(fld){
			var option = fld.options[fld.selectedIndex].value;
			if ( option == 'ftp_install' ){
				document.getElementById('step4').style.display='';
			} else {
				document.getElementById('step4').style.display='none';
				document.getElementById('submitstep').style.display='';
			}
		}
	}
};

	


	
	
	
