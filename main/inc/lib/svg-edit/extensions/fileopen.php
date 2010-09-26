<!doctype html>
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
 * @author Juan Carlos Raña Trabado
 * @since 25/september/2010
*/

require_once '../../../../inc/global.inc.php';//hack for chamilo
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'security.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';

api_protect_course_script();
api_block_anonymous_users();
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
	
//disable upload files (IMPORT SVG FILES) for now. Chamilo
	$file='';
	$output='';
	type='';
	prefix='';
//
?>

<script>
window.top.window.svgEditor.processFile("<?php echo $prefix . base64_encode($output); ?>", "<?php echo $type ?>");
</script>