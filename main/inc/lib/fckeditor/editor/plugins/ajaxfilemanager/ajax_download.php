<?php
/* For licensing terms, see /license.txt */

/**
 * delete selected files
 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
 * @link www.phpletter.com
 * @since 22/April/2007
 *
 */

require_once '../../../../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fckeditor/editor/plugins/ajaxfilemanager/inc/config.php';

if (!empty($_GET['path']) && file_exists($_GET['path']) && is_file($_GET['path']) && isUnderRoot($_GET['path'])) {

    $path = $_GET['path'];
    //check if the file size
    $fileSize = @filesize($path);

    if ($fileSize > getMemoryLimit()) {
        //larger then the php memory limit, redirect to the file
        header('Location: '.$path);
        exit;
    } else { //open it up and send out with php
        downloadFile($path);
    }
} else {
    die(ERR_DOWNLOAD_FILE_NOT_FOUND);
}
