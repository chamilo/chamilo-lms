<?php
/* For licensing terms, see /license.txt */
/**
 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
 * @link www.phpletter.com
 * @since 22/April/2007
 *
 */
require_once '../../../../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fckeditor/editor/plugins/ajaxfilemanager/inc/config.php';

echo '{';
$count = 1;
foreach (getFolderListing(CONFIG_SYS_ROOT_PATH) as $k => $v) {


    echo (($count > 1) ? ', ' : '')."'".$v."':'".$k."'";
    $count++;
}
echo "}";
