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

require_once __DIR__.'/../../ajax/inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

require_once __DIR__.'/../../inc/tranformSource.php';

require_once __DIR__.'/../../0_dal/dal.save.php';

require_once __DIR__.'/../../0_dal/dal.insert.php';

require_once __DIR__.'/../../0_dal/dal.getpaths.php';

require_once __DIR__.'/../../inc/csrf_token.php';

$version = '1.11.26-42';
$idPage = 0;
$action = '';

$oel_token = isset($_GET['cotk']) ? $_GET['cotk'] : '';
$cotk = isset($_GET['cotk']) ? $_GET['cotk'] : '';

if (false == $VDB->w_is_platform_admin()) {
    if (false == validateCSRFToken($oel_token, $VDB->w_api_get_user_id())) {
        echo 'CSRF token is not valid or has expired. Form submission rejected.'.$version;

        exit;
    }
}

if (!file_exists('files')) {
    //mkdir('files', 0777, true);
}
if (!file_exists('files/tmp')) {
    //mkdir('files/tmp', 0777, true);
}
if (isset($_GET['id'])) {
    $idPage = (int) $_GET['id'];
    $action = isset($_GET['action']) ? $VDB->remove_XSS($_GET['action']) : 'step1';
    $typefile = isset($_GET['typefile']) ? $VDB->remove_XSS($_GET['typefile']) : '';
    if (!$VDB->w_api_is_anonymous()) {
        $user = $VDB->w_api_get_user_info();
        if (isset($user['status'])) {
            if (SESSIONADMIN == $user['status']
              || COURSEMANAGER == $user['status']
              || PLATFORM_ADMIN == $user['status']) {
            } else {
                if (false == $VDB->w_is_platform_admin()) {
                    echo "<div style='color:red;' >Status !".$user['status'].'</div>';
                    echo 'Context status is not valid or has expired. Form submission rejected ! '.$version;

                    exit;
                }
            }
        }
    }
} else {
    echo 'Error'.$version;

    exit;
}

?>

  <script src="../jscss/jquery.js?v=<?php echo $version; ?>"></script>
  <link href="../jscss/oel-teachdoc.css?v=<?php echo $version; ?>" rel="stylesheet" />
  <link href="../templates/styles/classic-ux.css?v=<?php echo $version; ?>" rel="stylesheet"/>
  <link href="css/style.css?v=<?php echo $version; ?>" rel="stylesheet"/>

</head>
<body style="background-color:rgb(246, 247, 247);" >
    
    <?php if ('step1' == $action) { ?>
      
      <center>
        
        <h3>Direct import file</h3>
        <div id="run" style="text-align:center;" >
          <form class="uploader" action="inc/bigUpload.php?action=post-unsupported" method="post" enctype="multipart/form-data" id="bigUploadForm">
          
          <?php if ('13' == $typefile) { ?>
              <input type="file" id="bigUploadFile" onchange="readURL(this);" name="bigUploadFile" accept=".jpg,.png,.gif,.svg" />
          <?php } else { ?>
              <input type="file" id="bigUploadFile" name="bigUploadFile" accept=".jpg,.png,.gif,.svg,.mp3,.mp4,.pdf,.odt,.ods,.odp,.otp,.xlsx,.docx,.pptx" />
          <?php } ?>

              <div id="bigUploadProgressBarContainer" >
                <div id="bigUploadProgressBarFilled"></div>
              </div>
              
              <button class=" btn btn-primary " name="button" type="button" onclick="upload();<?php if ('13' == $typefile) { ?>showImgPrev();<?php } ?>" id="bigUploadSubmit" >
              <em class="fa fa-upload"></em>Upload</button>
                
              <input type="button" class="bigUploadButton bigUploadAbort" style="display:none;" value="Annuler" onclick="abort();" />
              <input id="scormid" name="scormid" type="hidden" value="testsco" />

          </form>
          
          <?php if ('13' == $typefile) { ?>
            <p style="text-align:center;" >
            <img id="imgpreviewFile" src="#" style="display:none;height:120px;width:auto;" /></p>
            <script>
              function readURL(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $('#imgpreviewFile').attr('src', e.target.result);
                    };

                    reader.readAsDataURL(input.files[0]);
                }
              }
              function showImgPrev(){
                $('#imgpreviewFile').css('display','block');
              }
            </script>
          <?php } ?>

        </div>

      </center>

      <div id="bigUploadTimeRemaining"></div>
      <div id="bigUploadResponse"></div>
      <div id="finalNameSrc"></div>
      
      <?php $lk = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/editor/import-project/import-file.php?cotk='.$cotk.'&id='.$idPage; ?>
      
      <div id="linkact" style="display:none;" ><?php echo $lk; ?></div>
      
      <div id="see" style="display:none;" >
        <img style="margin:15px;" src="../img/cube-oe.gif" />
      </div>

      <script src="js/big_upload.js?v=<?php echo $version; ?>"></script>
      <script src="js/init_oel.js?v=<?php echo $version; ?>"></script>

    <?php } ?>
    
    <?php if ('present' == $action) { ?>

      <center>
      
      <h3>&nbsp;</h3>

      <?php

        $finalPathW = '';
        $namFileF = '';
        if (isset($_GET['namesrc'])) {
            $namFile = $_GET['namesrc'];

            if (isFileDirectUpload($namFile)) {
                $pluginFileSystem = Container::getPluginsFileSystem();
                if ($pluginFileSystem->fileExists(api_get_folder_imporfiles().$namFile)) {
                    $localFolder = get_local_folder($idPage);
                    $filePathNg = "CStudio/editor/img_cache/$localFolder";
                    if (!$pluginFileSystem->directoryExists($filePathNg)) {
                        $pluginFileSystem->createDirectory($filePathNg);
                    }
                    if (!$pluginFileSystem->directoryExists($filePathNg)) {
                        echo "<span style='color:red;' >Error folder imgcache not exist !</span></br>";
                    }

                    if (!$pluginFileSystem->fileExists("$filePathNg/$namFile")) {
                        $namFileNeo = get_clean_idstring(filter_filename($namFile));

                        $stream = $pluginFileSystem->readStream(api_get_folder_imporfiles().$namFile);
                        $pluginFileSystem->writeStream("$filePathNg/$namFileNeo", $stream);

                        if ($pluginFileSystem->fileExists("$filePathNg/$namFileNeo")) {
                            // echo "File is OK";
                            $namFileF = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/img-cache.php?path='.rawurlencode($localFolder.'/'.$namFileNeo);
                        } else {
                            // echo "Error Upload";
                            $namFileF = 'errorupload';
                        }
                    } else {
                        // echo "File already exist";
                        $namFileF = 'filexist';
                    }
                } else {
                    // echo "Error Upload";
                    $namFileF = 'errorupload';
                }
            } else {
                // echo "File not authorized !";
                $namFileF = 'filenot';
            }
        }

        ?>
      </center>
      
      <script>parent.postMessage("importfileok:<?php echo $namFileF; ?>", "*")</script>
      
    <?php } ?>

</body>
</html>

<?php
  ob_end_flush();
?>
