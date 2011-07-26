<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Ra�a Trabado
 * @since 30/january/2011
*/
/**
 * Code
 */

require_once '../inc/global.inc.php';
api_protect_course_script();
api_block_anonymous_users();
	
//delete temporal file
unlink($_SESSION['temp_realpath_image']);

//Clean sessions and return to Chamilo file list
unset($_SESSION['paint_dir']);
unset($_SESSION['paint_file']);
unset($_SESSION['whereami']);
unset($_SESSION['temp_realpath_image']);

if (!isset($_SESSION['exit_pixlr'])){
	$location=api_get_path(WEB_CODE_PATH).'document/document.php';
	echo '<script>window.parent.location.href="'.$location.'"</script>';					 
	api_not_allowed(true);
}
else{
	echo '<div align="center" style="padding-top:150; font-family:Arial, Helvetica, Sans-serif;font-size:25px;color:#aaa;font-weight:bold;">'.get_lang('PleaseStandBy').'</div>';
	$location=api_get_path(WEB_CODE_PATH).'document/document.php?id='.Security::remove_XSS($_SESSION['exit_pixlr']);
	echo '<script>window.parent.location.href="'.$location.'"</script>';
	unset($_SESSION['exit_pixlr']);
}
