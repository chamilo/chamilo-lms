<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This file allows creating new svg and png documents with an online editor.
 *
 * @package chamilo.document
 *
 * @author Juan Carlos Ra�a Trabado
 *
 * @since 30/january/2011
 */
require_once __DIR__.'/../inc/global.inc.php';
api_protect_course_script(true);
api_block_anonymous_users();

//delete temporal file
$fileToDelete = Session::read('temp_realpath_image');
if (file_exists($fileToDelete)) {
    unlink($fileToDelete);
}

//Clean sessions and return to Chamilo file list
Session::erase('paint_dir');
Session::erase('paint_file');
Session::erase('temp_realpath_image');
$exit = Session::read('exit_pixlr');

if (empty($exit)) {
    $location = api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq();
    echo '<script>window.parent.location.href="'.$location.'"</script>';
    api_not_allowed(true);
} else {
    echo '<div align="center" style="padding-top:150; font-family:Arial, Helvetica, Sans-serif;font-size:25px;color:#aaa;font-weight:bold;">'.get_lang('PleaseStandBy').'</div>';
    $location = api_get_path(WEB_CODE_PATH).'document/document.php?id='.Security::remove_XSS($exit).'&'.api_get_cidreq();
    echo '<script>window.parent.location.href="'.$location.'"</script>';
    Session::erase('exit_pixlr');
}
