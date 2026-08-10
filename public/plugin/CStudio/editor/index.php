<?php

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
echo "var cstudioUploadMaxFileSize = '".addslashes((string) ini_get('upload_max_filesize'))."';";
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
$cstudioAiCsrfToken = '';
$cstudioLegacyCsrfToken = '';
$chamiloThemeColorCss = '';
$cstudioMdiCssFiles = [];
$cstudioChamiloCssVersion = (string) (@filemtime(__DIR__.'/jscss/cstudio-chamilo.css') ?: '1');

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

    // Authorization gate: deny anonymous users outright. CStudio editor URLs are
    // opened without a cid parameter after the project link redirects to
    // editor/index.php, so the strict course-context edit check can fail even for
    // teachers. Use the same fallback as oel_tools_teachdoc_link.php.
    $isAllowedToEdit = false;

    if (!$VDB->w_api_is_anonymous()) {
        $isAllowedToEdit = (bool) $VDB->w_api_is_allowed_to_edit();

        if (!$isAllowedToEdit && function_exists('api_is_allowed_to_edit')) {
            $isAllowedToEdit = (bool) api_is_allowed_to_edit(null, true, false, false);
        }
    }

    if (!$isAllowedToEdit) {
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function () {';
        echo 'document.body.innerHTML = "<p style=\"padding:20px;color:#b91c1c;font-family:sans-serif;\">Context token is not valid or has expired. User rejected.</p>";';
        echo '});';
        echo '</script>';

        exit;
    }

    $user = $VDB->w_api_get_user_info();
    echo "userStatusCS = '".(int) $user['status']."';";
    if (isset($_SESSION['idsessionedition'])) {
        echo "listPagesCS = '".(string) $_SESSION['idsessionedition']."';";
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
    $cstudioLegacyCsrfToken = savedCSRFToken($VDB->w_api_get_user_id());

    /** @var CsrfTokenManagerInterface $csrfTokenManager */
    $csrfTokenManager = Container::$container->get(CsrfTokenManagerInterface::class);
    $cstudioAiCsrfToken = $csrfTokenManager
        ->getToken('cstudio_ai_'.$idPage)
        ->getValue()
    ;

    // Read only the active Chamilo theme color variables. The theme file is
    // generated as :root custom properties; do not load the full app stylesheet
    // because CStudio/GrapesJS owns this standalone editor layout.
    try {
        /** @var ThemeHelper $themeHelper */
        $themeHelper = Container::$container->get(ThemeHelper::class);
        $themeCss = $themeHelper->getAssetContents('colors.css');
        $themeVariables = [];

        if (preg_match_all('/(--color-[a-z0-9-]+)\s*:\s*(-?\d+(?:\.\d+)?(?:\s+-?\d+(?:\.\d+)?){2})\s*;/i', $themeCss, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $themeVariables[$match[1]] = trim($match[2]);
            }
        }

        if (!empty($themeVariables)) {
            $declarations = [];
            foreach ($themeVariables as $name => $value) {
                $declarations[] = '  '.$name.': '.$value.';';
            }
            $chamiloThemeColorCss = ":root {\n".implode("\n", $declarations)."\n}";
        }
    } catch (\Throwable) {
        $chamiloThemeColorCss = '';
    }

    // CStudio is a standalone legacy editor, so it does not mount the Vue app.
    // Reuse only the CSS files emitted for Chamilo's Vue entry in order to make
    // the same Material Design Icons font available without loading Vue JS.
    $publicDir = dirname(__DIR__, 3);
    $entrypointsFile = $publicDir.'/build/entrypoints.json';
    if (is_readable($entrypointsFile)) {
        $entrypoints = json_decode((string) file_get_contents($entrypointsFile), true);
        $vueCssFiles = $entrypoints['entrypoints']['vue']['css'] ?? [];

        if (is_array($vueCssFiles)) {
            foreach ($vueCssFiles as $cssFile) {
                if (!is_string($cssFile) || !preg_match('#^/build/[A-Za-z0-9._/-]+\.css(?:\?.*)?$#', $cssFile)) {
                    continue;
                }

                $cssPath = $publicDir.strtok($cssFile, '?');
                if (is_readable($cssPath)) {
                    $cstudioMdiCssFiles[] = $cssFile;
                }
            }
            $cstudioMdiCssFiles = array_values(array_unique($cstudioMdiCssFiles));
        }
    }

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
        // Validate it against an iso-locale allowlist before passing it to apply_cstudio_template_lang(),
        // which performs a require() on a path built from this value.
        $cstudioCookieLocale = !empty($_COOKIE['cstudio_lang']) ? (string) $_COOKIE['cstudio_lang'] : '';
        if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $cstudioCookieLocale)) {
            $cstudioCookieLocale = '';
        }
        $cstudioInterfaceLocale = ('' !== $cstudioCookieLocale ? $cstudioCookieLocale : null)
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

        echo '<script>var baseMyCollImgs = [];</script>';
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
    <link href="jscss/cstudio-ai.css?v=<?php echo $version; ?>" rel="stylesheet" />
    <link href="templates/styles/classic-ux.css?v=<?php echo $version; ?>" rel="stylesheet"/>
<?php foreach ($cstudioMdiCssFiles as $cstudioMdiCssFile) { ?>
    <link href="<?php echo htmlspecialchars($cstudioMdiCssFile, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet" />
<?php } ?>
<?php if (!empty($cstudioMdiCssFiles)) { ?>
    <script>document.documentElement.classList.add('cstudio-mdi-ready');</script>
<?php } ?>
<?php if ('' !== $chamiloThemeColorCss) { ?>
    <style id="cstudio-chamilo-theme-colors">
<?php echo $chamiloThemeColorCss; ?>
    </style>
<?php } ?>
    <link href="jscss/cstudio-chamilo.css?v=<?php echo rawurlencode($cstudioChamiloCssVersion); ?>" rel="stylesheet" />

    <script src="dist/js/filestack-0.1.10.js?v=<?php echo $version; ?>"></script>
    <script src="dist/js/grapes.js?v=<?php echo $version; ?>"></script>
    <script src="dist/js/grapesludi.js?v=<?php echo $version; ?>"></script>
    <script src="dist/grapesjs-preset-webpage.min.js?v=<?php echo $version; ?>"></script>
    <script src="jscss/jquery.js?v=<?php echo $version; ?>"></script>
    <script src="jscss/amplify.min.js?v=<?php echo $version; ?>"></script>

    <script src="../vendor/tinymce/js/tinymce/tinymce.min.js?v=<?php echo $version; ?>" defer ></script>
    <script src="../vendor/tinymce/js/tinymce/jquery.tinymce.min.js?v=<?php echo $version; ?>" defer></script>
    <script src="jscss/oel-teachdoc-x.js?v=<?php echo $version; ?>"></script>
    <script src="../resources/js/cstudio-i18n.js?v=<?php echo $version; ?>"></script>
    <?php
$cstudioProjectId = $id_parent > 0 ? (int) $id_parent : (int) $idPage;
$cstudioLpId = 0;
$cstudioCourseId = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
$cstudioSessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;
$cstudioGroupId = isset($_GET['gid']) ? (int) $_GET['gid'] : 0;

if ($cstudioProjectId > 0) {
    $cstudioLpId = (int) $VDB->get_value_by_query(
        'SELECT lp_id FROM plugin_oel_tools_teachdoc WHERE id = '.(int) $cstudioProjectId,
        'lp_id'
    );
}

if ($cstudioLpId > 0) {
    $cstudioLp = Container::getLpRepository()->find($cstudioLpId);
    $cstudioResourceLink = $cstudioLp?->getFirstResourceLink();

    if ($cstudioCourseId <= 0) {
        $cstudioCourseId = (int) ($cstudioResourceLink?->getCourse()?->getId() ?? 0);
    }
    if (!isset($_GET['sid'])) {
        $cstudioSessionId = (int) ($cstudioResourceLink?->getSession()?->getId() ?? 0);
    }
    if (!isset($_GET['gid'])) {
        $cstudioGroupId = (int) ($cstudioResourceLink?->getGroup()?->getIid() ?? 0);
    }
}
?>
    <script>
      window.cstudioChamiloContentConfig = <?php echo json_encode([
          'enabled' => $cstudioLpId > 0 && $cstudioCourseId > 0,
          'lpId' => $cstudioLpId,
          'cid' => $cstudioCourseId,
          'sid' => $cstudioSessionId,
          'gid' => $cstudioGroupId,
          'apiBase' => api_get_path(WEB_PATH).'api',
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="jscss/oel-teachdoc.js?v=<?php echo $version; ?>"></script>
    <script>
      window.cstudioAiConfig = <?php echo json_encode([
          'endpoint' => api_get_path(WEB_PATH).'ai/cstudio',
          'pageId' => (int) $idPage,
          'csrfToken' => $cstudioAiCsrfToken,
          'locale' => $cstudioInterfaceLocale,
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="jscss/cstudio-ai.js?v=<?php echo $version; ?>"></script>
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
    <input type="hidden" id="cotk" name="csrf_oel_token" value="<?php echo htmlspecialchars($cstudioLegacyCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
