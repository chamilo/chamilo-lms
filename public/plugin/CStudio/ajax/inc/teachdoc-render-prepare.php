<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

function scormPrepareFilesProcess($courseSys, $projectLang): void
{
    $fileSystem = Container::getAssetRepository()->getFileSystem();

    $VDB = new VirtualDatabase();
    $courseSysFvideo = $courseSys.'/audio';
    if (!$fileSystem->directoryExists($courseSysFvideo)) {
        $fileSystem->createDirectory($courseSysFvideo);
    }

    $courseSysFvideo = $courseSys.'/video';
    if (!$fileSystem->directoryExists($courseSysFvideo)) {
        $fileSystem->createDirectory($courseSysFvideo);
    }

    $courseSysOelPlug = $courseSys.'/oel-plug';
    if (!$fileSystem->directoryExists($courseSysOelPlug)) {
        $fileSystem->createDirectory($courseSysOelPlug);
    }
    $srcFOelPlug = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/oel-plug';
    recurse_copy_teachdoc($srcFOelPlug, $courseSysOelPlug);

    $fileA = $srcFOelPlug.'/oeldragthewords/plugin.html';
    $fileB = $courseSysOelPlug.'/oeldragthewords/plugin.html';
    $dataFileA = file_get_contents($fileA);
    $dataFileA = tradFileProcess($dataFileA, $projectLang);
    $fileSystem->write($fileB, $dataFileA);

    $fileA = $srcFOelPlug.'/oelfilltheblanks/plugin.html';
    $fileB = $courseSysOelPlug.'/oelfilltheblanks/plugin.html';
    $dataFileA = file_get_contents($fileA);
    $dataFileA = tradFileProcess($dataFileA, $projectLang);
    $fileSystem->write($fileB, $dataFileA);

    $fileA = $srcFOelPlug.'/oelmarkthewords/plugin.html';
    $fileB = $courseSysOelPlug.'/oelmarkthewords/plugin.html';
    $dataFileA = file_get_contents($fileA);
    $dataFileA = tradFileProcess($dataFileA, $projectLang);
    $fileSystem->write($fileB, $dataFileA);

    $fileA = $srcFOelPlug.'/oelwordsinlettergrid/plugin.html';
    $fileB = $courseSysOelPlug.'/oelwordsinlettergrid/plugin.html';
    $dataFileA = file_get_contents($fileA);
    $dataFileA = tradFileProcess($dataFileA, $projectLang);
    $fileSystem->write($fileB, $dataFileA);

    $fileA = $srcFOelPlug.'/oelsorttheparagraphs/plugin.html';
    $fileB = $courseSysOelPlug.'/oelsorttheparagraphs/plugin.html';
    $dataFileA = file_get_contents($fileA);
    $dataFileA = tradFileProcess($dataFileA, $projectLang);
    $fileSystem->write($fileB, $dataFileA);

    $courseSysFimg = $courseSys.'/img';
    if (!$fileSystem->directoryExists($courseSysFimg)) {
        $fileSystem->createDirectory($courseSysFimg);
    }

    $courseSysFqcm = $courseSys.'/img/qcm';
    if (!$fileSystem->directoryExists($courseSysFqcm)) {
        $fileSystem->createDirectory($courseSysFqcm);
    }

    $courseSysFbtn = $courseSys.'/img/btn';
    if (!$fileSystem->directoryExists($courseSysFbtn)) {
        $fileSystem->createDirectory($courseSysFbtn);
    }

    $courseSysCss = $courseSys.'/css';
    if (!$fileSystem->directoryExists($courseSysCss)) {
        $fileSystem->createDirectory($courseSysCss);
    }
    $courseSysCssOD = $courseSys.'/css/OpenDyslexic';
    if (!$fileSystem->directoryExists($courseSysCssOD)) {
        $fileSystem->createDirectory($courseSysCssOD);
    }
    $srcOpenDyslexic = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/resources/css/OpenDyslexic';
    recurse_copy_teachdoc($srcOpenDyslexic, $courseSysCssOD);

    $courseSysCssImg = $courseSysCss.'/img';
    if (!$fileSystem->directoryExists($courseSysCssImg)) {
        $fileSystem->createDirectory($courseSysCssImg);
    }
    $courseSysCssclassique = $courseSysCssImg.'/classique';
    if (!$fileSystem->directoryExists($courseSysCssclassique)) {
        $fileSystem->createDirectory($courseSysCssclassique);
    }
    $srcCheckCircle = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/img/classique/check-circle.svg';
    $srcCheckCircle2 = $courseSysCssclassique.'/check-circle.svg';
    $stream = fopen($srcCheckCircle, 'r');
    $fileSystem->writeStream($srcCheckCircle2, $stream);
    fclose($stream);

    $srcCheckPin = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/img/classique/pin.png';
    $srcCheckPin2 = $courseSysCssclassique.'/pin.png';
    $stream = fopen($srcCheckPin, 'r');
    $fileSystem->writeStream($srcCheckPin2, $stream);
    fclose($stream);

    $srcFimg = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/img/qcm';
    recurse_copy_teachdoc($srcFimg, $courseSysFqcm);

    $srcFbtn = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/img/btn';
    recurse_copy_teachdoc($srcFbtn, $courseSysFbtn);

    $courseSysCLass = $courseSys.'/img/classique';
    if (!$fileSystem->directoryExists($courseSysCLass)) {
        $fileSystem->createDirectory($courseSysCLass);
    }
    $srcClsImg = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/img/classique';
    recurse_copy_teachdoc($srcClsImg, $courseSysCLass);

    $courseSysFinterface1 = $courseSys.'/interfaces';
    if (!$fileSystem->directoryExists($courseSysFinterface1)) {
        $fileSystem->createDirectory($courseSysFinterface1);
    }

    $courseSysFinterface2 = $courseSys.'/interfaces/sco';
    if (!$fileSystem->directoryExists($courseSysFinterface2)) {
        $fileSystem->createDirectory($courseSysFinterface2);
    }
    $courseSysFinterface3 = $courseSys.'/interfaces/xapi';
    if (!$fileSystem->directoryExists($courseSysFinterface3)) {
        $fileSystem->createDirectory($courseSysFinterface3);
    }
    $srcClsInterfaces = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/resources/interfaces';
    recurse_copy_teachdoc($srcClsInterfaces, $courseSys.'/interfaces');
}

function tradFileProcess($srcData, $ProjectLang)
{
    if ('en' == $ProjectLang) {
        $srcData = str_replace('Vérifier', 'Check', $srcData);
        $srcData = str_replace('Recommencer', 'Retry', $srcData);
        $srcData = str_replace('Voir la correction', 'Show solution', $srcData);
        $srcData = str_replace('Annuler', 'Cancel', $srcData);
        $srcData = str_replace('Vous devez avoir rempli tous les blancs avant de voir la correction', 'You must have filled in all the blanks before you see the correction', $srcData);
        $srcData = str_replace('Annuler', 'Cancel', $srcData);
    }
    if ('fr' == $ProjectLang) {
        $srcData = str_replace('List of words', 'Liste des mots', $srcData);
    }

    return $srcData;
}

function pagethumbFileProcess($pid, $courseSys, $localFolder, $courseDir): void
{
    $pluginFs = Container::getPluginsFileSystem();
    $srcFimg = 'CStudio/editor/img_cache/'.$localFolder.'/thumbnail-studio-'.$pid.'.png';
    $courseSysFdest = $courseSys.'/img_cache/thumbnail-studio-'.$pid.'.png';
    if ($pluginFs->fileExists($srcFimg)) {
        $fileSystem = Container::getAssetRepository()->getFileSystem();
        if (!$fileSystem->fileExists($courseSysFdest)) {
            $stream = $pluginFs->readStream($srcFimg);
            $fileSystem->writeStream($courseSysFdest, $stream);
            echo '<span style="color:gray;" >process 632 '.$srcFimg.' <b>to</b> '.$courseSysFdest.'</span></br>';
        }
    } else {
        echo '<span style="color:red;" >process 632 '.$srcFimg.' <b>Not exits</b>to</b> '.$courseSysFdest.'</span></br>';
    }
}

function pagePrepareFileProcess($pid, $courseSys, $base_html, $courseDirImg, $totempl = false): void
{
    $bh = $base_html;
    $matches = [];
    preg_match_all('/src="([^"]+)/i', $base_html, $matches);
    for ($i = 0; $i < count($matches[0]); $i++) {
        $cleanSrc = $matches[0][$i];
        $cleanSrc = str_replace('src="', '', $cleanSrc);
        pagePrepareFileCopy($cleanSrc, $courseSys, $bh, $courseDirImg, $totempl);
    }

    $matchesback = [];
    preg_match_all('~url\((?P<img_url>.+?)\)~', $base_html, $matchesback);
    for ($i = 0; $i < count($matchesback[0]); $i++) {
        $cleanSrc = $matchesback[0][$i];
        $cleanSrc = str_replace('url("', '', $cleanSrc);
        $cleanSrc = str_replace('")', '', $cleanSrc);
        $cleanSrc = str_replace('url(', '', $cleanSrc);
        $cleanSrc = str_replace(')', '', $cleanSrc);
        echo " -> cleanSrc $cleanSrc <br>";
        pagePrepareFileCopy($cleanSrc, $courseSys, $bh, $courseDirImg, $totempl);
    }

    $matchespdfdocs = [];
    preg_match_all('/datatext4="([^"]+)/i', $base_html, $matchespdfdocs);
    for ($i = 0; $i < count($matchespdfdocs[0]); $i++) {
        $cleanSrc = $matchespdfdocs[0][$i];
        $cleanSrc = str_replace('dow@', '', $cleanSrc);
        $cleanSrc = str_replace('datatext4="', '', $cleanSrc);
        if (false != strpos($cleanSrc, '.pdf')
            || false != strpos($cleanSrc, '.xlsx')
            || false != strpos($cleanSrc, '.ods')
            || false != strpos($cleanSrc, '.odt')
            || false != strpos($cleanSrc, '.odp')
            || false != strpos($cleanSrc, '.otp')
            || false != strpos($cleanSrc, '.pptx')
            || false != strpos($cleanSrc, '.doc')) {
            echo "<span style='color:purple;' > -> cleanSrc $cleanSrc </span><br>";
            pagePrepareFileCopy($cleanSrc, $courseSys, $bh, $courseDirImg, $totempl);
        }
    }

    // datatext2 span img
    $matchesimgdata = [];
    $pattern = '/<span[^>]*class="'.preg_quote('datatext2', '/').'"[^>]*>(.*?)<\/span>/s';
    preg_match_all($pattern, $base_html, $matchesimgdata);
    for ($i = 0; $i < count($matchesimgdata[0]); $i++) {
        $cleanSrc = $matchesimgdata[0][$i];
        // clean inner text
        $cleanSrc = str_replace('<span class="datatext2">', '', $cleanSrc);
        $cleanSrc = str_replace('</span>', '', $cleanSrc);
        $cleanSrc = str_replace(' ', '', $cleanSrc);
        echo ' - '.$cleanSrc.'<br>';
        if (false != strpos($cleanSrc, '.svg')) {
            echo "<span style='color:#27AE60;' > -> cleanSrc = $cleanSrc </span><br>";
            pagePrepareFileCopy($cleanSrc, $courseSys, $bh, $courseDirImg, $totempl);
        }
    }
}

function pagePrepareFileCopy($filename, $courseSys, $bh, $courseDirImg, $totempl = false): void
{
    $fileSystem = Container::getAssetRepository()->getFileSystem();

    $VDB = new VirtualDatabase();

    echo $filename.'</br>';
    $isHttp = strpos($filename, 'http');

    if (false === $isHttp) {
        $isImgFolder = strpos($filename, 'mg/');
        $isVideoFolder = strpos($filename, 'ideo/');
        $isVideoAudio = strpos($filename, 'udio/');

        if (false != $isImgFolder || false != $isVideoFolder || false != $isVideoAudio) {
            $srcFimg = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/'.$filename;

            if (false == $totempl) {
                $courseSysFdest = $courseSys.'/'.$filename;
                $_SESSION['all-files-studio'] = (string) $_SESSION['all-files-studio'].$filename.';';
            } else {
                $courseSysFdest = $courseSys.'/'.basename($filename);
            }

            // process 132
            if (file_exists($srcFimg)) {
                if (!$fileSystem->fileExists($courseSysFdest)) {
                    $stream = fopen($srcFimg, 'r');
                    $fileSystem->writeStream($courseSysFdest, $stream);
                    fclose($stream);

                    echo '<span style="color:gray;" >process 132 '.$srcFimg.' <b>to</b> '.$courseSysFdest.'</span></br>';
                }
            } else {
                echo '<span style="color:red;" >process 132 '.$srcFimg.' <b>Not exits</b></span></br>';
            }
        }

        $isImgCache = strpos($filename, 'mg_cache/');

        if (false != $isImgCache) {
            echo " -> Log $filename <br>";
            $srcFimg = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/'.$filename;

            if (false == $totempl) {
                $courseSysFdest = $courseSys.'/'.$filename;
                $_SESSION['all-files-studio'] = (string) $_SESSION['all-files-studio'].$filename.';';
            } else {
                $courseSysFdest = $courseSys.'/'.basename($filename);
            }

            // process 243
            if (file_exists($srcFimg)) {
                if (!$fileSystem->fileExists($courseSysFdest)) {
                    $stream = fopen($srcFimg, 'r');
                    $fileSystem->writeStream($courseSysFdest, $stream);
                    fclose($stream);

                    echo '<span style="color:gray;" >@copy'.$srcFimg.' <b>to</b> '.$courseSysFdest.'</span></br>';
                }
            } else {
                echo '<span style="color:red;" >process 243 '.$srcFimg.' <b>Not exits</b></span></br>';
            }

            $isSvgFile = strpos($srcFimg, '.svg');
            if (false != $isSvgFile) {
                $svgNsch = str_replace('.svg', '.html', $srcFimg);
                if (file_exists($svgNsch)) {
                    $courseSysNschdest = str_replace('.svg', '.html', $courseSysFdest);
                    $stream = fopen($svgNsch, 'r');
                    $fileSystem->writeStream($courseSysNschdest, $stream);
                    fclose($stream);

                    echo '<span style="color:orange;" >'.$svgNsch.' <b>to</b> '.$courseSysNschdest.'</span></br>';
                }
            }
        }
    }
}

function recurse_copy_teachdoc($src, $dst): void
{
    $fileSystem = Container::getAssetRepository()->getFileSystem();

    if (!$fileSystem->directoryExists($dst)) {
        $fileSystem->createDirectory($dst);
    }

    foreach (new DirectoryIterator($src) as $item) {
        if ($item->isDot() || 'Thumbs.db' === $item->getFilename()) {
            continue;
        }

        $dstPath = $dst.'/'.$item->getFilename();

        if ($item->isDir()) {
            recurse_copy_teachdoc($item->getPathname(), $dstPath);
        } else {
            $stream = fopen($item->getPathname(), 'rb');
            $fileSystem->writeStream($dstPath, $stream);
            fclose($stream);
        }
    }
}

function recurseCopyTeachdocOufs($src, $dst): void
{
    $pluginFileSystem = Container::getPluginsFileSystem();

    if (!$pluginFileSystem->directoryExists($dst)) {
        $pluginFileSystem->createDirectory($dst);
    }

    foreach ($pluginFileSystem->listContents($src, false) as $item) {
        if ('Thumbs.db' === basename($item->path())) {
            continue;
        }

        $dstPath = $dst.'/'.basename($item->path());

        if ($item->isDir()) {
            recurseCopyTeachdocOufs($item->path(), $dstPath);
        } else {
            $pluginFileSystem->copy($item->path(), $dstPath);
        }
    }
}

// RENDER
function prepareFoldersSco($courseSysPage, $course_dir, $local_folder, $idPTop, $optionsProjectCheck, $lp_id): void
{
    $fileSystem = Container::getAssetRepository()->getFileSystem();

    $VDB = new VirtualDatabase();
    // Engine
    $filePathNg = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/resources/navigobjcontrol.js';
    $courseDysNg = $courseSysPage.'/ng.js';
    $stream = fopen($filePathNg, 'r');
    $fileSystem->writeStream($courseDysNg, $stream);
    fclose($stream);

    if (!file_exists($filePathNg)) {
        $dataNg = file_get_contents($filePathNg);
        $fileSystem->write($courseDysNg, $dataNg);
    }

    // INDEX
    $filePathIndex = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/resources/index.html';
    $courseDyIndex = $courseSysPage.'/index.html';
    $courseDyIndex2 = $courseSysPage.'/teachdoc-undefined.html';
    $dataIndex = file_get_contents($filePathIndex);

    if (oel_ctr_options('ALL')) {
        $dataIndex = str_replace('{sendlogs}', '1', $dataIndex);
    } else {
        $dataIndex = str_replace('{sendlogs}', '0', $dataIndex);
    }
    $dataIndex = str_replace('{localIdTeachdoc}', (string) $lp_id, $dataIndex);

    $dataIndex2 = str_replace('{start}', (string) $idPTop, $dataIndex);
    $dataIndex2 = str_replace('load=', 'resetall=', $dataIndex2);

    $dataIndex = str_replace('{start}', (string) $idPTop, $dataIndex);
    if (false != strpos($optionsProjectCheck, 'P')) {
        $dataIndex = str_replace('=first', '=save', $dataIndex);
    }

    $fileSystem->write($courseDyIndex, $dataIndex);

    $fileSystem->write($courseDyIndex2, $dataIndex2);

    prepaRootFile($courseSysPage, 'jq.js');

    // API
    prepaRootFile($courseSysPage, 'interfaces/sco/api.js');
    prepaRootFile($courseSysPage, 'interfaces/xapi/api.js');
    prepaRootFile($courseSysPage, 'interfaces/inclusive/ui-inclusive.js');
    prepaRootFileFold($courseSysPage, 'imsmanifest.xml', 'CStudio/resources/interfaces/sco/');
    prepaRootFileFold($courseSysPage, 'adlcp_rootv1p2.xsd', 'CStudio/resources/interfaces/sco/');
    prepaRootFileFold($courseSysPage, 'ims_xml.xsd', 'CStudio/resources/interfaces/sco/');
    prepaRootFileFold($courseSysPage, 'imscp_rootv1p1p2.xsd', 'CStudio/resources/interfaces/sco/');
    prepaRootFileFold($courseSysPage, 'imsmd_rootv1p2p1.xsd', 'CStudio/resources/interfaces/sco/');

    $courseSysCss = $courseSysPage.'/css';
    if (!$fileSystem->directoryExists($courseSysCss)) {
        $fileSystem->createDirectory($courseSysCss);
    }

    // CSS
    $filePathCss = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/ajax/files/scorm.css';
    $courseDyCss = $courseSysPage.'/css/scorm.css';
    $stream = fopen($filePathCss, 'r');
    $fileSystem->writeStream($courseDyCss, $stream);
    fclose($stream);
    if (!file_exists($filePathCss)) {
        $dataCss = file_get_contents($filePathCss);
        $fileSystem->write($courseDyCss, $dataCss);
    }

    // CSS Plug rewrite
    $filePlugCss = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/templates/styles/plug.css';
    $coursePlugCss = $courseSysPage.'/css/plug.css';
    $dataCss = file_get_contents($filePlugCss);
    $dataCss = str_replace('url(img/classique/', 'url(../img/classique/', $dataCss);
    $fileSystem->write($coursePlugCss, $dataCss);

    // CSS Object Menu
    $fileObjectCss = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/templates/styles/base-title.css';
    $courseObjectCss = $courseSysPage.'/css/base-title.css';
    $dataCss = file_get_contents($fileObjectCss);
    $dataCss = str_replace('url(img/classique/', 'url(../img/classique/', $dataCss);
    $fileSystem->write($courseObjectCss, $dataCss);

    $coursePageCache = $courseSysPage.'/img_cache/';
    if (!$fileSystem->directoryExists($coursePageCache)) {
        $fileSystem->createDirectory($coursePageCache);
    }
    $coursePageCacheFolder1 = $coursePageCache.strtolower($course_dir);
    if (!$fileSystem->directoryExists($coursePageCacheFolder1)) {
        $fileSystem->createDirectory($coursePageCacheFolder1);
    }
    $coursePageCacheFolder2 = $coursePageCache.strtolower($local_folder);
    if (!$fileSystem->directoryExists($coursePageCacheFolder2)) {
        $fileSystem->createDirectory($coursePageCacheFolder2);
    }
}

function prepareMenuStartSco($courseSysPage, $idPTop, $optionsProjectCheck, $renderM, $titleModule, $lp_id, $basePagesArray, $strActivityId, $projLang, $projOptions): void
{
    $VDB = new VirtualDatabase();
    // INDEX MENU
    $filePathIndex3 = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/resources/index-neo.html';

    $courseDyIndex = $courseSysPage.'/index.html';
    $dataIndex = file_get_contents($filePathIndex3);

    if (oel_ctr_options('ALL')) {
        $dataIndex = str_replace('{sendlogs}', '1', $dataIndex);
    } else {
        $dataIndex = str_replace('{sendlogs}', '0', $dataIndex);
    }

    $dataIndex = str_replace('{localIdTeachdoc}', (string) $lp_id, $dataIndex);
    // <!--MENU-->
    $dataIndex = str_replace('{start}', (string) $idPTop, $dataIndex);
    $dataIndex = str_replace('{basePages}', $basePagesArray, $dataIndex);

    $dataIndex = str_replace('{activityid}', $strActivityId, $dataIndex);

    $dataIndex = str_replace('{projLang}', $projLang, $dataIndex);
    $dataIndex = str_replace('{projOptions}', $projOptions, $dataIndex);

    $renderMN = str_replace('oel_back.jpg', 'oel_back-low.jpg', $renderM);
    $dataIndex = str_replace('<!--MENU-->', $renderMN, $dataIndex);

    $dataIndex = str_replace('div-teachdoc', 'div-teachdoc-full', $dataIndex);

    // " '
    $dataIndex = str_replace('{titleplace}', htmlspecialchars($titleModule), $dataIndex);

    // act-bahavior
    $dataIndex = str_replace('act-bahavior', '-100', $dataIndex);

    if (false != strpos($optionsProjectCheck, 'P')) {
        $dataIndex = str_replace('=first', '=save', $dataIndex);
    }

    Container::getAssetRepository()
        ->getFileSystem()
        ->write($courseDyIndex, $dataIndex)
    ;
}

function prepaRootFile($courseSysPage, $namefile): void
{
    $VDB = new VirtualDatabase();
    $filePathApi = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/resources/'.$namefile;
    $courseDyApi = $courseSysPage.'/'.$namefile;
    $fileSystem = Container::getAssetRepository()->getFileSystem();
    $stream = fopen($filePathApi, 'r');
    $fileSystem->writeStream($courseDyApi, $stream);
    fclose($stream);
    if (!file_exists($filePathApi)) {
        $dataManifest = file_get_contents($filePathApi);
        $fileSystem->write($courseDyApi, $dataManifest);
    }
}

function prepaRootFileFold($courseSysPage, $namefile, $folder): void
{
    $VDB = new VirtualDatabase();
    $filePathApi = $VDB->w_get_path(SYS_PLUGIN_PATH).$folder.$namefile;
    $courseDyApi = $courseSysPage.'/'.$namefile;
    $fileSystem = Container::getAssetRepository()->getFileSystem();
    $stream = fopen($filePathApi, 'r');
    $fileSystem->writeStream($courseDyApi, $stream);
    fclose($stream);
    if (file_exists($filePathApi)) {
        $dataManifest = file_get_contents($filePathApi);
        $fileSystem->write($courseDyApi, $dataManifest);
    }
}

function preparepng2jpg($originalFile, $outputFile, $quality): void
{
    if (file_exists($originalFile)) {
        $image = imagecreatefrompng($originalFile);

        $stream = fopen('php://temp', 'r+');
        imagejpeg($image, $stream, $quality);
        imagedestroy($image);

        rewind($stream);

        Container::getAssetRepository()->getFileSystem()->writeStream($outputFile, $stream);
        fclose($stream);
    }
}

function preparelowjpg($originalFile, $outputFile): void
{
    makeThumbnails($outputFile, $originalFile);
}

function makeThumbnails($outputFile, $img): void
{
    $fileSystem = Container::getAssetRepository()->getFileSystem();
    $cacheDir = Container::getCacheDir();

    $MaxWe = 100;
    $MaxHe = 100;

    // Asegurar que existe el directorio temporal
    $tmpDir = $cacheDir.'/tmp_images';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0775, true);
    }

    $tempInput = tempnam($tmpDir, 'thumb_in_');

    try {
        $stream = $fileSystem->readStream($img);

        file_put_contents($tempInput, $stream);
        fclose($stream);

        $arr_image_details = getimagesize($tempInput);
        $width = $arr_image_details[0];
        $height = $arr_image_details[1];

        $percent = 100;

        if ($width > $MaxWe) {
            $percent = floor(($MaxWe * 100) / $width);
        }

        if (floor(($height * $percent) / 100) > $MaxHe) {
            $percent = (($MaxHe * 100) / $height);
        }

        if ($width > $height) {
            $newWidth = $MaxWe;
            $newHeight = (int) round(($height * $percent) / 100);
        } else {
            $newWidth = (int) round(($width * $percent) / 100);
            $newHeight = $MaxHe;
        }

        if (1 == $arr_image_details[2]) {
            $imgt = 'ImageGIF';
            $imgcreatefrom = 'ImageCreateFromGIF';
        } elseif (2 == $arr_image_details[2]) {
            $imgt = 'ImageJPEG';
            $imgcreatefrom = 'ImageCreateFromJPEG';
        } elseif (3 == $arr_image_details[2]) {
            $imgt = 'ImagePNG';
            $imgcreatefrom = 'ImageCreateFromPNG';
        } else {
            return;
        }

        $old_image = $imgcreatefrom($tempInput);
        $new_image = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        imagecopyresized($new_image, $old_image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $outStream = fopen('php://temp', 'r+');
        $imgt($new_image, $outStream);
        imagedestroy($old_image);
        imagedestroy($new_image);

        rewind($outStream);
        $fileSystem->writeStream($outputFile, $outStream);
        fclose($outStream);
    } finally {
        if (file_exists($tempInput)) {
            unlink($tempInput);
        }
    }
}
