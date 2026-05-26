<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the OeL plugin.
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;
use League\Flysystem\FilesystemOperator;

require_once '../../0_dal/dal.global_lib.php';

require_once '../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.save.php';

$userFilter = '-';

if (!oel_ctr_options('DTA')) {
    echo '';

    exit;
}

if (oel_ctr_options('OUT')) {
    $userFilter = $VDB->w_api_get_user_id().'-';
}

$nbTpl = 0;

$tab[] = [];

$pluginFileSystem = Container::getPluginsFileSystem();

$foldPath = 'CStudio/custom_code/page-templates/';

$indexP = 1000;

foreach ($pluginFileSystem->listContents($foldPath, false) as $item) {
    if (!$item->isDir()) {
        continue;
    }

    $nam = basename($item->path());
    $exp = false;

    if ('-' != $userFilter) {
        if (1 != strpos('@'.$nam, $userFilter)) {
            $exp = true;
        }
    }

    if (false === $exp) {
        $iuname = getnameFromFolder($item->path().'/title.txt', $pluginFileSystem);

        $rowTpl = '<div class="contain-pagetpl-select" >';
        $rowTpl .= '<img onClick="selectTplPage('.$indexP.');" ';
        $rowTpl .= ' tplref="'.$nam.'" class="tpl-page-select customtplpage tplpage'.$indexP.'" ';
        $rowTpl .= ' src="../custom_code/page-templates/'.$nam.'/overview.png" />';
        $rowTpl .= '<div class="contain-pagetpl-title" >';
        $rowTpl .= $iuname.'</div>';
        $rowTpl .= '</div>';

        $nbTpl++;
        $indexP++;
        $rendertempls .= $rowTpl;
    }
}

$renderFinal = '<p class="tpl-page-title trd" >Choose a template style</p>';
$renderFinal .= '<div class="areaContainLstA areaContainLst" ';
$renderFinal .= ' style="width:'.(int) ($nbTpl * 130).'px" >';
$renderFinal .= $rendertempls.'</div>';

if (0 == $nbTpl) {
    $renderFinal = '';
}

echo $renderFinal;

function getnameFromFolder(string $path, FilesystemOperator $filesystem): string
{
    if (!$filesystem->fileExists($path)) {
        return 'error';
    }

    $lines = array_filter(explode("\n", $filesystem->read($path)));

    return end($lines) ?: 'error';
}
