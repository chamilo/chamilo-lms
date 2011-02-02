<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 30/january/2011
*/

require_once '../inc/global.inc.php';
api_protect_course_script();
api_block_anonymous_users();
if (!isset($_SESSION['exit_pixlr'])){
	$location=api_get_path(WEB_CODE_PATH).'document/document.php';
	echo '<script>top.location.href="'.$location.'"</script>';					 
	api_not_allowed(true);
}
else{
	$location=api_get_path(WEB_CODE_PATH).'document/document.php?curdirpath='.Security::remove_XSS($_SESSION['exit_pixlr']);
	echo '<script>top.location.href="'.$location.'"</script>';
	unset($_SESSION['exit_pixlr']);
}
?>