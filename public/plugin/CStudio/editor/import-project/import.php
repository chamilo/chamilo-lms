<?php

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../0_dal/dal.global_lib.php';
ob_start();
?>
<!doctype html>
  <html lang="en" >
  <head>
  
  <meta charset="utf-8" />
  <title>Import Studio</title>

  <?php

    ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

ini_set('max_execution_time', 700);

require_once __DIR__.'/../../inc/tranformSource.php';

require_once __DIR__.'/../../ajax/inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';

$VDB = new VirtualDatabase();

require_once __DIR__.'/../../0_dal/dal.save.php';

require_once __DIR__.'/../../0_dal/dal.insert.php';

require_once __DIR__.'/../../0_dal/dal.getpaths.php';

require_once __DIR__.'/../../inc/csrf_token.php';

$version = '3209';
$idPage = 0;
$action = isset($_GET['action']) ? $VDB->remove_XSS($_GET['action']) : 'step1';
$log = isset($_GET['log']) ? $VDB->remove_XSS($_GET['log']) : 0;
$cid = isset($_GET['cid']) ? $VDB->remove_XSS($_GET['cid']) : '';
$oel_token = isset($_GET['cotk']) ? $_GET['cotk'] : '';
$cotk = $oel_token;
$iduser = $VDB->w_api_get_user_id();

if (1 == $log) {
    echo '<br/>Session ID: '.session_id();
    echo '<br/>Session status: '.session_status();
    echo '<br/>Session data: ';
    print_r($_SESSION);
    echo '<br/>CSRF token from URL: '.$oel_token;
    echo '<br/>User ID: '.$iduser;
}

if (false == validateCSRFToken($oel_token, $iduser)) {
    Display::display_header();
    echo 'CSRF token is not valid or has expired. Form submission rejected. (v'.$version.' u'.$iduser.')';
    if (1 == $log) {
        $ctr_token = savedCSRFToken($iduser);
        echo '<br />CSRF token is '.$oel_token;
        echo '<br />Saved CSRF token is '.$ctr_token;
    }
    Display::display_footer();

    exit;
}

if (isset($_GET['id'])) {
    $idPage = (int) $_GET['id'];

    if (!$VDB->w_api_is_anonymous()) {
        $user = $VDB->w_api_get_user_info();
        if (isset($user['status'])) {
            if (SESSIONADMIN == $user['status']
              || COURSEMANAGER == $user['status']
              || PLATFORM_ADMIN == $user['status']) {
            } else {
                if (false == $VDB->w_is_platform_admin()) {
                    echo "<div style='color:red;' >Status !".$user['status'].'</div>';
                    echo 'Context status '.$user['status'].' is not valid or has expired. User rejected ! v'.$version;

                    exit;
                }
            }
        }
    }
} else {
    if (!isset($_GET['cid'])) {
        echo 'Context token is not valid or has expired. Form submission rejected !! v'.$version;

        exit;
    }
}

?>

  <script src="../jscss/jquery.js?v=<?php echo $version; ?>"></script>
  <link href="../jscss/oel-teachdoc.css?v=<?php echo $version; ?>" rel="stylesheet" />
  <link href="../templates/styles/classic-ux.css?v=<?php echo $version; ?>" rel="stylesheet"/>
     
</head>
<body style="background-color:#D8D8D8;" >
    
  <div class="ludimenu"  style="z-index: 1000;">
    <div class="luditopheader"></div>
    <div class="ludimenuteachdoc" style="height: 360px;"></div>
    <a class="tool-base" href="#" ></a>
    <?php
  if ('' == $cid) { ?>
      <a id="tool-quit" name="tool-quit" class="tool-quit tool-base" href="../index.php?id=<?php echo $idPage; ?>&cotk=<?php echo generateCSRFToken(api_get_user_id()); ?>" >
      <div>Quit</div>
      </a>
    <?php } else { ?>
      <a id="tool-quit" name="tool-quit" class="tool-quit tool-base" href="<?php echo $VDB->w_course_path($cid).'&cotk='.generateCSRFToken(api_get_user_id()); ?>" >
      <div>Quit</div>
      </a>
    <?php }
    echo '<p style="position:absolute;text-align:center;bottom:10px;width:100%;">Version&nbsp;'.$version.'</p>';
?>
    
  </div>
  
  <div class="center-import" >
    
    <?php if ('step1' == $action) { ?>
      <h2>Import project</h2>
      <div id="run" >
        <form action="inc/bigUpload.php?action=post-unsupported" method="post" enctype="multipart/form-data" id="bigUploadForm">
            <input type="file" id="bigUploadFile" name="bigUploadFile" />
            <button class=" btn btn-primary " name="button" type="button" onclick="upload();" id="bigUploadSubmit" ><em class="fa fa-upload"></em> Upload</button>
            <input type="button" class="bigUploadButton bigUploadAbort" style="display:none;" value="Annuler" onclick="abort();" />
            <input id="scormid" name="scormid" type="hidden" value="testsco" />
        </form>
        <div id="bigUploadProgressBarContainer" >
            <div id="bigUploadProgressBarFilled"></div>
        </div>
      </div>

      <div id="bigUploadTimeRemaining"></div>
      <div id="bigUploadResponse"></div>
      <div id="finalNameSrc"></div>
      
      <?php
    $lk = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/editor/import-project/import.php?cid='.$cid.'&cotk='.$cotk.'&id='.$idPage;
        ?>
      
      <div id="linkact" style="display:none;" ><?php echo $lk; ?></div>

      <div id="see" style="display:none;" >
        <img style="margin:15px;" src="../img/cube-oe.gif" />
      </div>

      <script src="js/big_upload.js?v=<?php echo $version; ?>"></script>
      <script src="js/init_oel.js?v=<?php echo $version; ?>"></script>

    <?php } ?>

    <?php if ('present' == $action) { ?>

      <h2>Project detail</h2>

      <?php

            $tmpFolderW = $idPage.'-'.date('Y').'-'.date('d').'-'.date('H').'-'.date('i').'-'.date('s');
        $finalPathW = '';

        if (isset($_GET['namesrc'])) {
            $namFileZip = $_GET['namesrc'];
            $pluginFileSystem = Container::getPluginsFileSystem();

            if ($pluginFileSystem->fileExists(api_get_folder_imporfiles().$namFileZip)) {
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    $tmpZipPath = Container::getCacheDir().'/cstudio_import_'.basename($namFileZip);
                    $zipStream = $pluginFileSystem->readStream(api_get_folder_imporfiles().$namFileZip);
                    file_put_contents($tmpZipPath, $zipStream);
                    fclose($zipStream);

                    if (true === $zip->open($tmpZipPath)) {
                        $fsBasePath = api_get_folder_importmp().$tmpFolderW;
                        for ($zi = 0; $zi < $zip->numFiles; $zi++) {
                            $stat = $zip->statIndex($zi);
                            $entryName = $stat['name'];
                            $entryPath = $fsBasePath.'/'.$entryName;
                            if (str_ends_with($entryName, '/')) {
                                $pluginFileSystem->createDirectory($entryPath);
                            } else {
                                $stream = $zip->getStream($entryName);
                                $pluginFileSystem->writeStream($entryPath, $stream);
                                fclose($stream);
                            }
                        }
                        $zip->close();
                        unlink($tmpZipPath);

                        if ($pluginFileSystem->directoryExists($fsBasePath)) {
                            $finalPathW = $fsBasePath;
                        }
                    }
                }
            }
        }

        if ('' == $finalPathW) {
            echo "<p style='color:red;' >Error not find the folder source</p>";
        }

        if ($pluginFileSystem->fileExists($finalPathW.'/exportdata/0-base_html.txt')) {
            echo "<p style='color:green;' >Source is OK</p>";

            $nbPages = 0;

            for ($i = 0; $i <= 100; $i++) {
                if ($pluginFileSystem->fileExists($finalPathW.'/exportdata/'.$i.'-base_html.txt')) {
                    $nbPages++;
                }
            }

            if ($nbPages > 0) {
                echo "<p style='color:green;' >$nbPages pages detected</p>";

                echo '</br>';
                echo "<a href='import.php?cid=".$cid.'&id='.$idPage.'&action=create&namesrc='.$namFileZip.'&fold='.$tmpFolderW.'&cotk='.$cotk."' ";
                echo "style='border:solid 1px gray;padding:7px;cursor:pointer;color:white;' ";
                echo "class='gjs-one-bg ludiButtonSave' >Import</a>";

                echo '<script>';
                echo 'setTimeout(function(){';
                echo " $('#inlineFrameShowImport').attr('src','".'files/tmp/'.$tmpFolderW."/index.html');";
                echo '},600);';
                echo '</script>';

                echo '<iframe id="inlineFrameShowImport" class="inlineFrameShowImport" ';
                echo 'width="300" height="200" ';
                echo 'src="files/index.html"> ';
                echo '</iframe> ';
            } else {
                echo "<p style='color:red;' >This project is not valid</p>";
            }
        } else {
            echo "<p style='color:red;' >This project is not valid</p>";
        }

        ?>

    <?php } ?>
    
    <?php

    if ('create' == $action) {
        echo '<h2>Project creation</h2>';

        $tmpFolderW = $_GET['fold'];
        $pluginFileSystem = Container::getPluginsFileSystem();

        if ($pluginFileSystem->directoryExists(api_get_folder_importmp().$tmpFolderW)) {
            $tmpImgCache = api_get_folder_importmp().$tmpFolderW.'/img_cache/';
            $tmpFolderWork = api_get_folder_importmp().$tmpFolderW.'/exportdata/';

            $base_css = '';
            $base_html = '';
            $GpsComps = '';
            $GpsStyle = '';
            $Goptions = '';
            $Gtitle = '';
            $Gbehavior = 0;
            $Gcolors = '';
            $type_node = 2;

            if ($pluginFileSystem->fileExists($tmpFolderWork.'0-base_css.txt')) {
                $base_css = $pluginFileSystem->read($tmpFolderWork.'0-base_css.txt');
            } else {
                echo $tmpFolderWork.'0-base_css not exits';
                echo '</div></div></body></html>';

                exit;
            }
            if ($pluginFileSystem->fileExists($tmpFolderWork.'0-base_html.txt')) {
                $base_html = $pluginFileSystem->read($tmpFolderWork.'0-base_html.txt');
                // 'img_cache/teachdoc-td-20210921/'
            }
            if ($pluginFileSystem->fileExists($tmpFolderWork.'0-GpsComps.txt')) {
                $GpsComps = $pluginFileSystem->read($tmpFolderWork.'0-GpsComps.txt');
            }
            if ($pluginFileSystem->fileExists($tmpFolderWork.'0-GpsStyle.txt')) {
                $GpsStyle = $pluginFileSystem->read($tmpFolderWork.'0-GpsStyle.txt');
            }
            if ($pluginFileSystem->fileExists($tmpFolderWork.'0-options.txt')) {
                $Goptions = $pluginFileSystem->read($tmpFolderWork.'0-options.txt');
            }
            if ($pluginFileSystem->fileExists($tmpFolderWork.'0-title.txt')) {
                $Gtitle = $pluginFileSystem->read($tmpFolderWork.'0-title.txt');
            }
            if ($pluginFileSystem->fileExists($tmpFolderWork.'0-behavior.txt')) {
                $Gbehavior = $pluginFileSystem->read($tmpFolderWork.'0-behavior.txt');
            }
            if ($pluginFileSystem->fileExists($tmpFolderWork.'0-colors.txt')) {
                $Gcolors = $pluginFileSystem->read($tmpFolderWork.'0-colors.txt');
            }

            $oldFolder = '';
            if ($pluginFileSystem->fileExists($tmpFolderWork.'0-localfolder.txt')) {
                $oldFolder = $pluginFileSystem->read($tmpFolderWork.'0-localfolder.txt');
            }
            if ('' == $oldFolder) {
                echo 'oldFolder is empty<br>';
                echo '</div></div></body></html>';

                exit;
            }
            if ('' == $base_css || '' == $base_html) {
                echo 'Base code is empty<br>';
                echo '</div></div></body></html>';

                exit;
            }

            echo 'Création en cours .... <br>';
            echo '</div></div></body></html>';

            $title = $_GET['namesrc'];
            $userId = $VDB->w_api_get_user_id();
            $objectIdlpid = insertNewProject($title, $userId);

            $objectIdTop = get_top_page_by_lpid($objectIdlpid);
            $localFolder = get_local_folder($objectIdTop);

            $base_html = cleanOldCode($base_html, $oldFolder, $localFolder);
            $GpsStyle = cleanOldCode($GpsStyle, $oldFolder, $localFolder);
            $GpsComps = cleanOldCode($GpsComps, $oldFolder, $localFolder);

            $params = [
                'base_css' => $base_css, 'base_html' => $base_html,
                'gpscomps' => $GpsComps, 'gpsstyle' => $GpsStyle,
                'options' => $Goptions, 'title' => $Gtitle,
                'behavior' => $Gbehavior, 'colors' => $Gcolors,
            ];
            $VDB->update('plugin_oel_tools_teachdoc', $params, ['id = ?' => $objectIdTop]);

            echo '<script>';
            echo 'setTimeout(function(){';
            echo "window.location = 'import.php?cid=".$cid.'&id='.$idPage.'&action=create2&objectIdTop='.$objectIdTop.'&fold='.$tmpFolderW.'&tmpFolderWork='.urlencode($tmpFolderWork).'&objectIdlpid='.$objectIdlpid.'&cotk='.$cotk."';";
            echo '},1500);';
            echo '</script>';
        }
    }

if ('create2' == $action) {
    echo '<h2>Project creation sub pages</h2>';

    $tmpFolderW = $_GET['fold'];
    $tmpImgCache = api_get_folder_importmp().$tmpFolderW.'/img_cache/';
    $pluginFileSystem = Container::getPluginsFileSystem();

    $tmpFolderWork = rawurldecode($_GET['tmpFolderWork']);
    $objectIdTop = $_GET['objectIdTop'];
    $objectIdlpid = $_GET['objectIdlpid'];

    $userId = $VDB->w_api_get_user_id();

    $oldFolder = '';
    if ($pluginFileSystem->fileExists($tmpFolderWork.'0-localfolder.txt')) {
        $oldFolder = $pluginFileSystem->read($tmpFolderWork.'0-localfolder.txt');
    }

    $localFolder = get_local_folder($objectIdTop);

    for ($i = 1; $i <= 100; $i++) {
        if ($pluginFileSystem->fileExists($tmpFolderWork.$i.'-base_html.txt')) {
            echo 'Page '.$i.' is imported  - ';

            $base_css = $pluginFileSystem->read($tmpFolderWork.$i.'-base_css.txt');
            $base_html = $pluginFileSystem->read($tmpFolderWork.$i.'-base_html.txt');
            $GpsComps = $pluginFileSystem->read($tmpFolderWork.$i.'-GpsComps.txt');
            $GpsStyle = $pluginFileSystem->read($tmpFolderWork.$i.'-GpsStyle.txt');
            $Goptions = $pluginFileSystem->read($tmpFolderWork.$i.'-options.txt');
            $Gtitle = $pluginFileSystem->read($tmpFolderWork.$i.'-title.txt');
            $Gbehavior = $pluginFileSystem->read($tmpFolderWork.$i.'-behavior.txt');
            $Gcolors = $pluginFileSystem->read($tmpFolderWork.$i.'-colors.txt');
            $type_node = $pluginFileSystem->read($tmpFolderWork.$i.'-type_node.txt');

            $base_html = cleanOldCode($base_html, $oldFolder, $localFolder);
            $GpsStyle = cleanOldCode($GpsStyle, $oldFolder, $localFolder);
            $GpsComps = cleanOldCode($GpsComps, $oldFolder, $localFolder);

            $idUrl = $VDB->w_get_current_access_url_id();
            $MaxOrder = oel_tools_max_order($objectIdTop);

            if ('' != $base_html) {
                $pageIdI = oel_tools_insert_element('page'.$i, $objectIdTop, $userId, $MaxOrder, $idUrl, $type_node);
                // echo 'insert page ('.$pageIdI.');<br>';
                $params = [
                    'base_css' => $base_css, 'base_html' => $base_html,
                    'gpscomps' => $GpsComps, 'gpsstyle' => $GpsStyle,
                    'options' => $Goptions, 'title' => $Gtitle,
                    'behavior' => $Gbehavior, 'colors' => $Gcolors,
                ];
                $VDB->update('plugin_oel_tools_teachdoc', $params, ['id = ?' => $pageIdI]);
            } else {
                echo 'no source html '.$tmpFolderWork.$i.'-base_html.txt;<br>';
            }
        }
    }

    $pluginFileSystem = Container::getPluginsFileSystem();
    $filePathNg = "CStudio/editor/img_cache/$localFolder";
    if (!$pluginFileSystem->directoryExists($filePathNg)) {
        $pluginFileSystem->createDirectory($filePathNg);
    }
    if (!$pluginFileSystem->directoryExists($filePathNg)) {
        echo "<span style='color:red;' >Error folder imgcache not exist !</span></br>";
    }

    echo '<br>';

    $urlM = '../../oel_tools_teachdoc_link.php?idLudiLP='.$objectIdlpid.'&cotk='.$cotk;
    echo '<a href="'.$urlM.'" >See new project</a><br>';
    echo '<br>';

    echo '<div id="copy-progress">Copying images...</div>';
    echo '<div id="copy-result"></div>';

    echo '<script>';
    echo '$(document).ready(function() {';
    echo '  var tmpImgCache = "'.addslashes($tmpImgCache).'";';
    echo '  var oldFolder = "'.addslashes($oldFolder).'";';
    echo '  var filePathNg = "'.addslashes($filePathNg).'";';
    echo '  var cotk = "'.addslashes($cotk).'";';
    echo '  var idPage = "'.addslashes($idPage).'";';
    echo '  ';
    echo '  function copyImages() {';
    echo '    $.ajax({';
    echo '      url: "import-copy.php",';
    echo '      type: "GET",';
    echo '      data: {';
    echo '        action: "copyimages",';
    echo '        tmpImgCache: tmpImgCache,';
    echo '        oldFolder: oldFolder,';
    echo '        filePathNg: filePathNg,';
    echo '        cotk: cotk,';
    echo '        id: idPage';
    echo '      },';
    echo '      beforeSend: function() {';
    echo '        $("#copy-progress").html("Step 1/3: Copying images...");';
    echo '      },';
    echo '      success: function(response) {';
    echo '        $("#copy-progress").html("Step 1/3: Images copied successfully!");';
    echo '        copyDocuments();';
    echo '      },';
    echo '      error: function(xhr, status, error) {';
    echo '        $("#copy-progress").html("Error copying images: " + error);';
    echo '      }';
    echo '    });';
    echo '  }';
    echo '  ';
    echo '  function copyDocuments() {';
    echo '    $.ajax({';
    echo '      url: "import-copy.php",';
    echo '      type: "GET",';
    echo '      data: {';
    echo '        action: "copydocuments",';
    echo '        tmpImgCache: tmpImgCache,';
    echo '        oldFolder: oldFolder,';
    echo '        filePathNg: filePathNg,';
    echo '        cotk: cotk,';
    echo '        id: idPage';
    echo '      },';
    echo '      beforeSend: function() {';
    echo '        $("#copy-progress").html("Step 2/3: Copying PDF and MP3 files...");';
    echo '      },';
    echo '      success: function(response) {';
    echo '        $("#copy-progress").html("Step 2/3: PDF and MP3 files copied successfully!");';
    echo '        copyVideos();';
    echo '      },';
    echo '      error: function(xhr, status, error) {';
    echo '        $("#copy-progress").html("Error copying documents: " + error);';
    echo '      }';
    echo '    });';
    echo '  }';
    echo '  ';
    echo '  function copyVideos() {';
    echo '    $.ajax({';
    echo '      url: "import-copy.php",';
    echo '      type: "GET",';
    echo '      data: {';
    echo '        action: "copyvideos",';
    echo '        tmpImgCache: tmpImgCache,';
    echo '        oldFolder: oldFolder,';
    echo '        filePathNg: filePathNg,';
    echo '        cotk: cotk,';
    echo '        id: idPage';
    echo '      },';
    echo '      beforeSend: function() {';
    echo '        $("#copy-progress").html("Step 3/3: Copying MP4 videos...");';
    echo '      },';
    echo '      success: function(response) {';
    echo '        $("#copy-progress").html("All files copied successfully! ✓");';
    echo '      },';
    echo '      error: function(xhr, status, error) {';
    echo '        $("#copy-progress").html("Error copying videos: " + error);';
    echo '      }';
    echo '    });';
    echo '  }';
    echo '  ';
    echo '  copyImages();';
    echo '});';
    echo '</script>';

    echo '<br>';
}

?>

 </div>
    

</div>

</body>
</html>

<?php

  function cleanOldCode($src, $oldFold, $newFold)
  {
      $src = str_replace('img_cache/'.$oldFold, 'img_cache/'.$newFold, $src);

      return str_replace('/'.$oldFold.'/', '/'.$newFold.'/', $src);
  }

ob_end_flush();

?>
