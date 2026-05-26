<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the OeL plugin.
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;

require_once '../../0_dal/dal.global_lib.php';

require_once '../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.save.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

if (!isset($_GET['idteach'])) {
    exit;
}

$step = get_int_from('step');
$idPage = get_int_from('idteach');

if (false == oel_ctr_rights($idPage)) {
    echo 'KO';

    exit;
}

$user = $VDB->w_api_get_user_info();
$userId = $user['id'];
$idurl = api_get_current_access_url_id();
$UrlWhere = '';
$title = 'error';

$localFolder = get_local_folder($idPage);
$customPageCss = 'CStudio/editor/img_cache/';
$customPageCss .= strtolower($localFolder).'/custom.css';

$pluginFileSystem = Container::getPluginsFileSystem();

if (0 == $step) {
    // Create
    if (!$pluginFileSystem->fileExists($customPageCss)) {
        $customCssOri = $VDB->w_get_path(SYS_PATH).'plugin/';
        $customCssOri .= 'CStudio/editor/';
        $customCssOri .= 'templates/colors/paper-chami.css';

        $base_css = file_get_contents($customCssOri);

        $pluginFileSystem->write($customPageCss, $base_css);
    }

    if ($pluginFileSystem->fileExists($customPageCss)) {
        echo $pluginFileSystem->read($customPageCss);
    } else {
        echo 'KO';
    }
}
if (1 == $step) {
    $content_css = get_string_direct_from('content');
    $pluginFileSystem->write($customPageCss, $content_css);
}
