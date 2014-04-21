<?php
/*
 * fileopen.php
 * To be used with ext-server_opensave.js for SVG-edit
 *
 * Licensed under the Apache License, Version 2
 *
 * Copyright(c) 2010 Alexis Deveria
 *
 * Integrate svg-edit with Chamilo
 * @author Juan Carlos Ra�a Trabado
 * @since 25/september/2010
*/

require_once '../../../../inc/global.inc.php';//hack for chamilo
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

api_protect_course_script();
api_block_anonymous_users();

if(!isset($_FILES['svg_file']['tmp_name'])) {
	api_not_allowed(false);//from Chamilo
	die();
}


?>
<!doctype html>
<?php
	// Very minimal PHP file, all we do is Base64 encode the uploaded file and
	// return it to the editor
	
	$file = $_FILES['svg_file']['tmp_name'];
	
	$output = file_get_contents($file);
	
	$type = $_REQUEST['type'];
	
	$prefix = '';
	
	// Make Data URL prefix for import image
	if($type == 'import_img') {
		$info = getimagesize($file);
		$prefix = 'data:' . $info['mime'] . ';base64,';
	}
	
//check the extension
$extension = explode('.', $file);
$extension = strtolower($extension[sizeof($extension) - 1]);	

//a bit title security
$filename = addslashes(trim($file));
$filename = Security::remove_XSS($filename);
$filename = replace_dangerous_char($filename, 'strict');
$filename = disable_dangerous_file($filename);	
	
//a bit mime security
$current_mime = $_FILES['svg_file']['type'];
$mime_svg='image/svg+xml';
$mime_xml='application/xml';//hack for svg-edit because original code return application/xml; charset=us-ascii.
		
if(strpos($current_mime, $mime_svg)===false && strpos($current_mime, $mime_xml)===false && $extension=='svg'){
	// die();//File extension does not match its content disabled to check into chamilo dev campus TODO:enabled
}

?>

<script>
window.top.window.svgEditor.processFile("<?php echo $prefix . base64_encode($output); ?>", "<?php echo $type ?>");
</script>