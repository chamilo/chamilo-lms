<?php

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../0_dal/dal.global_lib.php';

require_once __DIR__.'/../teachdoc_hub.php';
ob_start();
?>
<!doctype html>
  <html lang="en" >
  <head>
<?php

echo '<script>';
echo "var versionCS = '".teachdoc_hub::create()->get_version()."';";
echo "var userStatusCS = '?';";
echo "var listPagesCS = '?';";
echo "var renderFromSvg = '';";
echo "var optionsGlobalPage = '';";
echo "var lfIdent = '';";
echo '</script>';

error_reporting(\E_ERROR | \E_PARSE);

$title = '';
$base_html = '';
$base_css = '';
$colors_data = '';
$quizztheme_data = '';
$type_base = 1;
$id_parent = 0;
$loadh = '';
$changColor = '';
$changQuizzColor = '';
$localFolder = '';

if (isset($_GET['id'])) {
    $idPage = (int) $_GET['id'];
    echo "<script>console.log('idPage = ".$idPage."');</script>";

    if (isset($_GET['changc'])) {
        $changColor = $_GET['changc'];
    }
    if (isset($_GET['changquizz'])) {
        $changQuizzColor = $_GET['changquizz'];
    }
    if (isset($_GET['loadh'])) {
        $loadh = $_GET['loadh'];
    }
    $fromsvg = '';
    if (isset($_GET['fromsvg'])) {
        $fromsvg = $_GET['fromsvg'];
    }

    require_once __DIR__.'/../0_dal/dal.vdatabase.php';

    require_once __DIR__.'/../inc/tranformSource.php';

    require_once __DIR__.'/../inc/csrf_token.php';

    require_once __DIR__.'/../0_dal/dal.save.php';

    require_once __DIR__.'/../0_dal/dal.getpaths.php';

    include __DIR__.'/inc/getoptions.php';

    require_once __DIR__.'/inc/template-lang.php';

    require_once __DIR__.'/../ajax/inc/teachdoc-render-prepare.php';

    $localFolder = get_local_folder($idPage);

    echo "<script>console.log('localFolder = ".$localFolder."');</script>";

    $VDB = new VirtualDatabase();

    echo '<script>';
    echo "userStatusCS = '';";
    echo "listPagesCS = '';";
    echo "optionsCS = '".$options_studio."';";
    echo "optionsCSCDT = '".$options_studio_cdt."';";
    echo 'renderFromSvg = '.json_encode((string) $fromsvg).';';

    if (!$VDB->w_api_is_anonymous()) {
        $user = $VDB->w_api_get_user_info();

        if ($VDB->w_api_is_allowed_to_edit()) {
            echo "userStatusCS = '".(int) $user['status']."';";
            if (isset($_SESSION['idsessionedition'])) {
                echo "listPagesCS = '".(string) $_SESSION['idsessionedition']."';";
            }
        } else {
            echo 'Context token is not valid or has expired. User rejected !</br>';
            echo "<a href='javascript:history.back();' >Return</a></br></head></html>";

            exit;
        }
    } else {
        echo "console.log('api_is_anonymous !');";
    }

    echo "var renderEngRed = '".$VDB->engine."';";
    echo '</script>';

    $oel_token = isset($_GET['cotk']) ? $_GET['cotk'] : '';

    if (false == validateCSRFToken($oel_token, $VDB->w_api_get_user_id())) {
        echo 'CSRF token is not valid or has expired. Form submission rejected ('.$VDB->w_api_get_user_id().'666).</br>';
        echo "<a href='javascript:history.back();' >Return</a></br></head></html>";

        exit;
    }
    echo "<script>console.log('api_get_user_id');</script>";

    if ('' != $idPage && 0 != $idPage) {
        $pluginFileSystem = Container::getPluginsFileSystem();
        $Part = get_oel_tools_editor($idPage);

        $title = $Part['title'];
        if (isset($_GET['noechec'])) {
            $base_html = preventImg64($Part['base_html']);
        } else {
            $base_html = getSrcForEditor($Part['base_html']);
        }

        $base_css = $Part['base_css'];
        $type_base = $Part['type_base'];
        $GpsComps = $Part['gpscomps'];
        $GpsStyle = $Part['gpsstyle'];
        $id_parent = $Part['id_parent'];
        $colors_data = $Part['colors'];
        $quizztheme_data = $Part['quizztheme'];
        $typeNode = $Part['type_node'];
        $filePageData = '';
        $optionsGlobalPage = $Part['options'];
        $optionsGlobalPage = str_replace("'", '&apos;', $optionsGlobalPage);
        $optionStr = $Part['optionsstr'];
        if ('' != $changColor) {
            $colors_data = $changColor.'.css';
            update_oel_tools_color($id_parent, $colors_data);
        }
        if ('' != $changQuizzColor) {
            $quizztheme_data = $changQuizzColor.'.css';
            update_oel_tools_quizztheme($id_parent, $quizztheme_data);
        }

        if ('' != $loadh) {
            $localFolderH = $localFolder.'-'.$idPage;
            $filDataHistory = 'history_cache/'.$localFolderH.'/'.$loadh.'.html';
            echo '<script>console.log("'.$filDataHistory.'");</script>';
            if ($pluginFileSystem->fileExists("CStudio/editor/$filDataHistory")) {
                echo '<script>console.log("'.$filDataHistory.' exist");</script>';
                $base_html = $pluginFileSystem->read("CStudio/editor/$filDataHistory");
                $base_css = $pluginFileSystem->read("CStudio/editor/history_cache/$localFolderH/$loadh.css");
            }
        }

        if (4 == $typeNode) {
            $filePageData = $optionStr;
            $base_html = '<div class="panel"></div>';
            $base_css = '';
        }

        // Cookie cstudio_lang is written by the JS language switcher (setCstudioLangCookie)
        // on every page load, making it the most reliable source for the user's chosen UI language.
        $cstudioInterfaceLocale = (!empty($_COOKIE['cstudio_lang']) ? $_COOKIE['cstudio_lang'] : null)
            ?? Container::getSession()?->get('_locale')
            ?? 'en_US';

        if ('' == $base_html) {
            if (isset($_GET['pty'])) {
                $pathFile = 'templates/pages/'.$_GET['pty'].'.html';
                if (file_exists($pathFile)) {
                    $base_html = file_get_contents($pathFile);
                } else {
                    $pathFile = 'CStudio/custom_code/page-templates/'.$_GET['pty'].'/data.html';
                    if ($pluginFileSystem->fileExists($pathFile)) {
                        $base_html = $pluginFileSystem->read($pathFile);
                        $localfold = get_local_folder($idPage);
                        $base_html = str_replace('{folderlocal}', $localfold, $base_html);
                        $foldDest = 'img_cache/'.$localfold.'/';
                        recurseCopyTeachdocOufs(
                            'CStudio/custom_code/page-templates/'.$_GET['pty'].'/data/',
                            "CStudio/editor/$foldDest"
                        );
                    } else {
                        $base_html = file_get_contents('templates/pages/error.html');
                    }
                }
            } else {
                $base_html = file_get_contents('templates/pages/p0.html');
            }
            $base_html = str_replace('###TITLE###', $title, $base_html);
            $base_html = apply_cstudio_template_lang($base_html, $cstudioInterfaceLocale);
        }

        if (isset($_GET['resetall'])) {
            $base_html = file_get_contents('templates/pages/p0.html');
            $base_html = str_replace('###TITLE###', $title, $base_html);
            $base_html = apply_cstudio_template_lang($base_html, $cstudioInterfaceLocale);
        }

        oel_add_ctr_rights($idPage);

        echo '<script>';
        echo 'var idPageHtml = '.$idPage.';';
        echo 'var idPageHtmlTop = '.$id_parent.';';
        echo "var colorsPath = '".$colors_data."';";
        echo "var mainColorTpl = '".get_oel_main_color_quizztheme($colors_data)."';";
        echo "var quizzthemePath = '".$quizztheme_data."';";
        echo 'var typeNodePg = '.$typeNode.';';
        echo "var filePageData = '".$filePageData."';";
        echo "optionsGlobalPage = '".$optionsGlobalPage."';";
        echo "lfIdent = '".$localFolder."';";
        echo '</script>';

        // Available JSON translation files + course locale for the UI language system
        $cstudioJsonDir = __DIR__.'/../lang/json/';
        $cstudioLocales = array_map(
            fn ($f) => basename($f, '.json'),
            glob($cstudioJsonDir.'*.json') ?: []
        );
        sort($cstudioLocales);
        $cstudioCourseLocale = $VDB->w_api_get_course_locale();
        echo '<script>';
        echo 'var cstudioAvailableLocales = '.json_encode($cstudioLocales).';';
        echo 'var cstudioCourseLocale = '.json_encode($cstudioCourseLocale).';';
        echo '</script>';

        echo '<script type="text/javascript" src="img_cache/getextras.php?id='.$id_parent.'" ></script>';
    } else {
        echo "<script>location.href = '../oel_tools_teachdoc_list.php';</script>";

        exit;
    }
} else {
    echo "<script>location.href = '../oel_tools_teachdoc_list.php';</script>";

    exit;
}

include __DIR__.'/inc/head.inc.php';

echo '<script>';
echo 'setTimeout(function(){';
echo 'if ($(".list-teachdoc").width()>800) {';
echo "location.href = 'index-recup.php?id=".$idPage."';";
echo '}';
echo '},3000);';
echo '</script>';

?>

</head>
<body style="background-color:#D8D8D8;" >

    <div class=ludiEditIco onCLick="actionEditButon();" ></div>
    <div class=ludiSpeedTools ></div>

    <div id="gjs" style="height:0px; overflow:hidden" >
      <?php
        echo preventImg64($base_html);
if ('' != $base_css) {
    echo '<style>'.cleanCssForEdit($base_css).'</style>';
}
echo '<style>';
echo '
            .cell{
              border:dashed 1px #A9CCE3;
              vertical-align: middle;
              padding:10px;
              width: 8%;
              min-width: 250px;
              display: table-cell;
              height: 0;
              height: auto!important;
              min-height: 75px;
            }
            .row {
              display: table;
              padding-top: 10px;
              padding-right: 10px;
              padding-bottom: 10px;
              padding-left: 10px;
              width: 100%;
              height: 0;
              height: auto!important;
              min-height: 75px;
            }
            .panel{
              padding-right : 30px!important;
              padding-left : 30px!important;
            }
          ';
echo '</style>';
?>
    </div>

    <?php
$filcustomcode = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/img-cache.php?path='.rawurlencode($localFolder.'/customcode.css');
$varDt = date('YmdHis');
echo '<div id="filcustomcode" style="display:none;" >'.$filcustomcode.'&v='.$varDt.'</div>';
?>

    <img id="jscssedit" src="jscss/edit.png" />

    <link href="dist/css/grapes.min.css?v=<?php echo $version; ?>" rel="stylesheet" />
    <link href="dist/grapesjs-preset-webpage.min.css?v=<?php echo $version; ?>" rel="stylesheet" />
    <link href="jscss/oel-teachdoc.css?v=<?php echo $version; ?>" rel="stylesheet" />
    <link href="templates/styles/classic-ux.css?v=<?php echo $version; ?>" rel="stylesheet"/>

    <script src="dist/js/filestack-0.1.10.js?v=<?php echo $version; ?>"></script>
    <script src="dist/js/grapesludi.js?v=<?php echo $version; ?>"></script>
    <script src="dist/grapesjs-preset-webpage.min.js?v=<?php echo $version; ?>"></script>
    <script src="jscss/jquery.js?v=<?php echo $version; ?>"></script>
    <script src="jscss/amplify.min.js?v=<?php echo $version; ?>"></script>

    <script src="../vendor/tinymce/js/tinymce/tinymce.min.js?v=<?php echo $version; ?>" defer ></script>
    <script src="../vendor/tinymce/js/tinymce/jquery.tinymce.min.js?v=<?php echo $version; ?>" defer></script>
    <script src="jscss/oel-teachdoc-x.js?v=<?php echo $version; ?>"></script>
    <script src="../resources/js/cstudio-i18n.js?v=<?php echo $version; ?>"></script>
    <script src="jscss/oel-teachdoc.js?v=<?php echo $version; ?>"></script>
    <script>correctPositionsEditor();</script>

    <?php

  /*if (file_exists(__DIR__.'/../vendor/elfinder/elfinder.php')) {
      require_once __DIR__.'/../vendor/elfinder/elfinder.php';
  } else {
      echo "<script>console.log('Vendor elfinder not find !');</script>";
  }*/

echo "<script>
        var _p = {
          web_path : '".$VDB->w_get_path(WEB_PATH)."',
          web_plugin : '".$VDB->w_get_path(WEB_PLUGIN_PATH)."',
          web_editor : '".$VDB->w_get_path(WEB_PLUGIN_PATH)."CStudio/editor',
          web_render_cache : '".$VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/editor/sco_cache/'.$localFolder."/'
        };
      </script>";

if (isset($_GET['pty'])) {
    echo '<script>';
    echo 'setTimeout(function(){';
    echo "if (typeof saveSourceFrame === 'function') { ";
    echo 'saveSourceFrame(false,false,0);';
    echo '}';
    echo '},600);';
    echo '</script>';
}

?>

    <script src="../resources/interfaces/xapi/base64.js"></script>
    <form method="POST" action="index.php">
    <input type="hidden" id="cotk" name="csrf_oel_token" value="<?php echo savedCSRFToken($VDB->w_api_get_user_id()); ?>">
    </form>
    <?php
    if (false != strpos($base_html, 'txtmathjax')) {
        ?>
      <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
      <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <?php
    }
?>

  </body>
</html>
<?php
ob_end_flush();
?>
