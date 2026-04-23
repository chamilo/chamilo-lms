<!doctype html>
  <html lang="en" >
  <head>
  
  <meta charset="utf-8" />
  <title>Clean Cache</title>

  <?php

  use Chamilo\CoreBundle\Framework\Container;

  error_reporting(\E_ERROR | \E_PARSE);

  ini_set('max_execution_time', 700);

  require_once __DIR__.'/../../0_dal/dal.global_lib.php';

  require_once __DIR__.'/../../0_dal/dal.vdatabase.php';
  $VDB = new VirtualDatabase();

  require_once __DIR__.'/../../inc/tranformSource.php';

  require_once __DIR__.'/../../ajax/inc/functions.php';

  require_once __DIR__.'/../../0_dal/dal.save.php';

  require_once __DIR__.'/../../0_dal/dal.insert.php';

  require_once __DIR__.'/../../0_dal/dal.getpaths.php';

  $version = '1.11.16-21';
  $idPage = 0;
  $action = '';

  if (!$VDB->w_api_is_anonymous()) {
      $user = $VDB->w_api_get_user_info();
      if (!$VDB->w_api_is_allowed_to_edit()) {
          echo "<div style='color:red;' >Status !".$user['status'].'</div>';
          echo "<script>setTimeout(function(){ location.href = '../../index.php'; }, 3000);</script>";

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
  </div>
  
  <div class="center-import" >
    
  <?php

    $UrlWhere = '';
  if ($VDB->w_get_multiple_access_url()) {
      $idurl = $VDB->w_get_current_access_url_id();
      $UrlWhere = " AND id_url = $idurl ";
  }

  $table = 'plugin_oel_tools_teachdoc';
  $sqlNS = "SELECT id , lp_id , local_folder FROM $table ";
  $sqlNS .= "WHERE id_parent = 0 $UrlWhere ";

  $justone = true;

  $pluginFileSystem = Container::getPluginsFileSystem();

  $VDB->query_to_array($sqlNS);
  foreach ($VDB->query_to_array($sqlNS) as $PartTop) {
      $idTopPage = $PartTop['id'];
      $lpId = $PartTop['lp_id'];
      $localFolder = $PartTop['local_folder'];

      if ('' != $lpId && 0 != $lpId) {
          if ($justone) {
              $numsave = 0;
              $selectLP = "SELECT COUNT(id) as numsave FROM c_lp WHERE id = $lpId ";

              $numsave = (int) $VDB->get_value_by_query($selectLP, 'numsave');

              if (0 == $numsave) {
                  $justone = false;

                  echo 'lp_id : '.$lpId.' ('.$numsave.') '.$localFolder.' <br>';

                  $folderDel = "CStudio/editor/img_cache/$localFolder";
                  if ($pluginFileSystem->directoryExists($folderDel)) {
                      $pluginFileSystem->deleteDirectory($folderDel);
                  }

                  echo 'deleteDir : '.$folderDel.' <br>';

                  if (!$pluginFileSystem->directoryExists($folderDel)) {
                      $sqlDEL = "DELETE FROM $table ";
                      $sqlDEL .= "WHERE id_parent = $idTopPage ";
                      $VDB->query($sqlDEL);

                      $sqlDEL = "DELETE FROM $table ";
                      $sqlDEL .= "WHERE id = $idTopPage ";
                      $VDB->query($sqlDEL);
                  }
              }
          }
      }
  }

  ?>

  </div>
    

</div>

</body>
</html>
