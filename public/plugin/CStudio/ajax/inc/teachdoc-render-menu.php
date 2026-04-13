<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

$fileSystem = Container::getAssetRepository()->getFileSystem();
$pluginFileSystem = Container::getPluginsFileSystem();

$CollectionPages = getCollectionPages($idPageTop);

$CollectionAlone = getAlonePages($idPageTop);

$oldLpid = -1;
$pageActives = 0;

foreach ($CollectionPages as &$row) {
    $row['prev_id'] = $oldLpid;
    $oldLpid = $row['id'];
    $row['next_id'] = findNextId($CollectionPages, $oldLpid);
}

if (!str_contains($optionsProjectCheck, 'L')) {
    $renderM = '<div class=div-teachdoc>';
} else {
    $renderM = '<div class=div-teachdoc-hide >';
}

$renderM .= '<div class=deco-teachdoc ></div>';
$renderM .= '<img class="logotop-teachdoc" src="img/classique/oel_back.jpg" />';
// -low
$renderM .= '<div class="progress-teachdoc" >';
$renderM .= '<div class="left-text-progress" >0%</div>';
$renderM .= '<div class="left-teach-progress" >';
$renderM .= '<div class="left-barre-progress" ></div>';
$renderM .= '</div>';

$renderM .= '</div>';

$renderM .= '<div class="list-teachdoc_wrapper">';
$renderM .= '<ul class="list-teachdoc">';

$iP = 1;
$basePages = 'var basePages = new Array();';
$basePages .= 'basePages[0]=0;';

$basePages .= 'var behavPages = new Array();';
$basePages .= 'behavPages[0]=0;';

$basePages .= 'var leveldocPages = new Array();';
$basePages .= 'leveldocPages[0]=0;';

$basePages .= 'var catPages = new Array();';
$basePages .= 'catPages[0]=0;';

$basePages .= 'var baseTitles = new Array();';
$basePages .= 'baseTitles[0]="";';

$basePages .= 'var baseChapter = new Array();';
$basePages .= 'baseChapter[0]="";';

$inPg = 0;
foreach ($CollectionPages as &$row) {
    $basePages .= 'baseChapter['.$inPg.']=\'\';';
    $inPg++;
}

$idPgCat = 0;

foreach ($CollectionPages as &$row) {
    $typeNode = (int) $row['type_node'];

    $pagebehav = $row['behavior'];
    if ('' == $pagebehav) {
        $pagebehav = 0;
    }

    $pageleveldoc = $row['leveldoc'];
    if ('' == $pageleveldoc) {
        $pageleveldoc = 2;
    }
    if (0 == $pageleveldoc) {
        $pageleveldoc = 2;
    }
    if (!str_contains($optionsProjectCheck, 'D')) {
        $pageleveldoc = 2;
    }

    if (3 != $typeNode) {
        $pageActives++;
        $basePages .= 'catPages['.$iP.']='.$idPgCat.';';
        $basePages .= 'basePages['.$iP.']='.$row['id'].';';
        $basePages .= 'behavPages['.$iP.']='.$pagebehav.';';
        $basePages .= 'leveldocPages['.$iP.']='.$pageleveldoc.';';
        $titleMod = str_replace("'", '&apos;', $row['title']);
        $basePages .= 'baseTitles['.$iP.']=\''.$titleMod.'\';';
    } else {
        $titleMod = str_replace("'", '&apos;', $row['title']);
        $basePages .= 'baseChapter['.$iP.']=\''.$titleMod.'\';';
    }

    if (2 == $pageleveldoc) {
        if (0 != $idPgCat && 3 != $typeNode) {
            $renderM .= "<li style='position:relative;display:none;' ";
        } else {
            $renderM .= "<li style='position:relative;' ";
        }
    } else {
        $renderM .= "<li style='position:relative;display:none;' "; // background-color:#FAE5D3;
    }

    // Thumbnail Page
    $thumbnailImg = 'CStudio/editor/img_cache/'.strtolower($local_folder).'/thumbnail-studio-'.$row['id'].'.png';
    $destThumbnailImg = $courseSysPage.'/img_cache/thumbnail-studio-'.$row['id'].'.png';
    if ($pluginFileSystem->fileExists($thumbnailImg)) {
        $stream = $pluginFileSystem->readStream($thumbnailImg);
        $fileSystem->writeStream($destThumbnailImg, $stream);
    }
    // Thumbnail Page

    $renderM .= " pagebehav='".$pagebehav."' ";

    if (1 == $typeNode || 2 == $typeNode || 4 == $typeNode) {
        if (!str_contains($optionsProjectCheck, 'N')) {
            $renderM .= " onClick='ctrlpl(".$row['id'].',act-bahavior,'.$iP.','.$pagebehav.");' ";
        }
        $renderM .= " class='NodeLvl".$row['type_node'].' ';
        $renderM .= 'subMenuSco'.$iP.' pgh'.$row['id'].' ';
        $renderM .= 'subCatMenu'.$idPgCat.' ';
        $renderM .= 'subMenuMini'.$row['index']."' ";
        $renderM .= " leveldoc='$pageleveldoc' >";
        $renderM .= "<span class='dotSubLudi' ></span>";
    } else {
        if (3 == $typeNode) {
            $renderM .= " onClick='collapselpl(".$row['id'].");' ";
            $renderM .= " class='mainCatMenu".$row['id']." NodeLvl1' >";
        } else {
            $renderM .= " class='NodeLvl1' >";
        }
    }

    $renderM .= '<span>'.$row['title'].'</span>';
    if (3 == $typeNode) {
        $idPgCat = $row['id'];
        $renderM .= "<i class='icon-arrow open'></i>";
    }
    $renderM .= '</li>';
    if (3 != $typeNode) {
        $iP++;
    }
}

$basePages .= 'baseChapter['.$iP.']=\'\';';

$renderM .= '</ul>';
$renderM .= '</div>';

$renderM .= '</div>';

if (!str_contains($optionsProjectCheck, 'L')) {
    $renderM .= '<nav id="nav-bottom" class="fixed-top-nav fixed-top-nav-classic" >';
} else {
    $renderM .= '<nav id="nav-bottom" class="fixed-top-nav fixed-top-nav-large" >';
}

$renderM .= '<div class="span-ludi-progress" >';
$renderM .= '<div class="barre-ludi-progress" ></div>';
$renderM .= '</div>';

if (!str_contains($optionsProjectCheck, 'T')) {
    $renderM .= '<button id="btn-next" act-next class="btn btn-outline-light" >&nbsp;>&nbsp;</button>';
    $renderM .= '<button id="btn-prev" act-prev class="btn btn-outline-light" >&nbsp;<&nbsp;</button>';
}

$renderM .= '</nav>';

if ('' == $optionsProjectMessKo) {
    $optionsProjectMessKo = 'Page not complete.';
}

$renderM .= '<div id="message-bottom" class="fixed-top-message" >';
$renderM .= $optionsProjectMessKo;
$renderM .= '</div>';

$renderM .= '<div id="infosfulltxt" style="display:none;" >';
$renderM .= $infosfulltxt.'</div>';

// $pageScormCss = 'files/scorm.css';
// $baseCssC = file_get_contents($pageScormCss);
// $renderCss = $baseCssC;
$renderCss = '';
$pageScormNavig = '../resources/navig.js';
$baseNavig = file_get_contents($pageScormNavig);

$renderJS = '<script>var localIdTeachdoc='.(int) $lp_id.';';
$renderJS .= 'var progressBtop='.(int) $pageActives.';';
$renderJS .= 'var titleMod = "'.htmlspecialchars($titleMod).'";';
$renderJS .= 'var pQuizzTheme = "'.$ProjectQuizzTheme.'";';

if (false != strpos($optionsProjectCheck, 'V')) {
    $pLifeBar = 3;
    if (false != strpos($optionsProjectCheck, 'H4')) {
        $pLifeBar = 4;
    }
    if (false != strpos($optionsProjectCheck, 'H5')) {
        $pLifeBar = 5;
    }
    if (false != strpos($optionsProjectCheck, 'H6')) {
        $pLifeBar = 6;
    }
    if (false != strpos($optionsProjectCheck, 'H7')) {
        $pLifeBar = 7;
    }
    if (false != strpos($optionsProjectCheck, 'H8')) {
        $pLifeBar = 8;
    }
    $renderJS .= 'var pLifeBar = '.$pLifeBar.';';
} else {
    $renderJS .= 'var pLifeBar = 0;';
}

$renderJS .= $basePages;
$renderJS .= $baseNavig.'</script>';
// .$baseCtrNavig

$cPathGloss = 'CStudio/editor/img_cache/'.strtolower($local_folder).'/gloss.js';

if ($pluginFileSystem->fileExists($cPathGloss)) {
    $destPathGloss = $courseSysPage.'/gloss.js';
    $stream = $pluginFileSystem->readStream($cPathGloss);
    $fileSystem->writeStream($destPathGloss, $stream);
} else {
    $fileSystem->write($courseSysPage.'/gloss.js', 'var glossaryRender = new Array();');
}

$vdep = 38;

$renderJS .= '<script type="text/javascript" src="gloss.js?v='.$vdep.uuid(5).'"></script>';
$renderJS .= '<script type="text/javascript" src="jq.js?v='.$vdep.'"></script>';
$renderJS .= '<script type="text/javascript" src="interfaces/sco/api.js?v='.$vdep.'"></script>';
$renderJS .= '<script type="text/javascript" src="ng.js?v='.$vdep.'"></script>';
$renderJS .= '<link type="text/css" href="css/plug.css?v='.$vdep.'" rel="stylesheet" />';
$renderJS .= '<link type="text/css" href="css/base-title.css?v='.$vdep.'" rel="stylesheet" />';

// Custom Code customcode.css
$filcustomcode = 'CStudio/editor/img_cache/'.strtolower($local_folder).'/customcode.css';
if ($pluginFileSystem->fileExists($filcustomcode)) {
    $customcss = $pluginFileSystem->read($filcustomcode);
    $renderJS .= '<style>'.$customcss.'</style>';
} else {
    $renderJS .= '<style>/*'.$filcustomcode.'*/</style>';
}

$renderJS .= '<script type="text/javascript" src="interfaces/schem.js?v='.$vdep.uuid(5).'"></script>';

// Render Html Schema
$renderHtmlSchema = 'var schemRender = new Array();';
$srcImgCache = $courseSysPage.'/img_cache/'.strtolower($local_folder).'/';
$listing = $fileSystem->listContents($srcImgCache);

foreach ($listing as $item) {
    if (!$item->isFile()) {
        continue;
    }

    $fileNsch = basename($item->path());

    if ('Thumbs.db' === $fileNsch) {
        continue;
    }

    if (str_contains($fileNsch, '.htm')) {
        $rschem = $fileSystem->read($item->path());

        if ('' !== $rschem) {
            $svgNsch = str_replace('.html', '.svg', $fileNsch);
            $renderHtmlSchema .= 'schemRender[\''.$svgNsch.'\'] = '.json_encode($rschem).';';
        }
    }
}

if (!$fileSystem->directoryExists($courseSysPage.'/interfaces')) {
    $fileSystem->createDirectory($courseSysPage.'/interfaces');
}

$fileSystem->write($courseSysPage.'/interfaces/schem.js', $renderHtmlSchema);

if (str_contains($optionsProjectCheck, 'I')) {
    $renderJS .= '<script type="text/javascript" ';
    $renderJS .= ' src="interfaces/inclusive/ui-inclusive.js?v='.$vdep.'"></script>';
    $renderJS .= '<link href="css/OpenDyslexic/OpenDyslexic.css" ';
    $renderJS .= ' rel="stylesheet" type="text/css" >';
}

if (str_contains($optionsProjectCheck, 'G')) {
    $renderJS .= '<script type="text/javascript" ';
    $renderJS .= ' src="https://api.chamidoc.com/trd.js"></script>';
}
