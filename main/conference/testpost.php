<?php
//file_put_contents("result.txt", print_r($_POST, true));
//file_put_contents("result3.txt", print_r($_FILES, true));
//file_put_contents("result2.txt", print_r($_GET, true));
require('../inc/global.inc.php');
if(api_get_setting('service_visio','active')=='true'
	&& $_SERVER['REMOTE_HOST'] == api_get_setting('service_visio','visio_host'))
{
	$target = "/tmp/";
	$target = $target . basename( $_FILES['file']['name']) ;
	if(move_uploaded_file($_FILES['file']['tmp_name'], $target));
}
?>