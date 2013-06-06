<?php
/* For licensing terms, see /license.txt */

$app['debug'] = false;

$app['app.title'] = '';
$app['jquery_ui_theme'] = 'smoothness';

// Main temp folder.
$app['temp.path'] = $app['sys_temp_path'];

// temp.paths obj
$app['temp.paths'] = new stdClass();

$app['temp.paths']->folders[] = $app['sys_data_path'];

// Monolog.
$app['temp.paths']->folders[] = $app['sys_log_path'];

// Twig cache.
$app['temp.paths']->folders[] = $app['twig.cache.path'] = $app['temp.path'].'twig';

// Http cache
$app['temp.paths']->folders[] = $app['http_cache.cache_dir'] = $app['temp.path'].'http';

// Doctrine ORM.
$app['temp.paths']->folders[] = $app['db.orm.proxies_dir'] = $app['temp.path'].'proxies_dir';

// Symfony2 Web profiler.
$app['temp.paths']->folders[] = $app['profiler.cache_dir'] = $app['temp.path'].'profiler';

// HTMLPurifier.
$app['temp.paths']->folders[] = $app['htmlpurifier.serializer'] = $app['temp.path'].'serializer';

// PCLZIP temp dir.
define('PCLZIP_TEMPORARY_DIR', $app['temp.path'].'pclzip');
$app['temp.paths']->folders[] = PCLZIP_TEMPORARY_DIR;

// MPDF temp libs.
define("_MPDF_TEMP_PATH", $app['temp.path'].'/mpdf');
define("_JPGRAPH_PATH", $app['temp.path'].'/mpdf');
define("_MPDF_TTFONTDATAPATH", $app['temp.path'].'/mpdf');

$app['temp.paths']->folders[] = _MPDF_TEMP_PATH;

// QR code.
define('QR_CACHE_DIR', $app['temp.path'].'qr');
define('QR_LOG_DIR', $app['temp.path'].'qr');

$app['temp.paths']->folders[] = QR_CACHE_DIR;

// Chamilo Temp class @todo fix this
$app['temp.paths']->folders[] = $app['temp.path'].'temp';

// Assetic.

$app['assetic.enabled'] = false;

if ($app['assetic.enabled']) {

    $app['assetic.path_to_cache'] = $app['temp.path'] . DIRECTORY_SEPARATOR . 'assetic';

    $app['temp.paths']->folders[] = $app['assetic.path_to_cache'];

    // Location where to dump all generated files

    $app['assetic.path_to_web'] = api_get_path(SYS_PATH).'web';
    $app['assetic.input.path_to_assets'] = $app['assetic.path_to_web'].$app['app.theme'];
    $css_path = api_get_path(SYS_CSS_PATH);

    $app['assetic.input.path_to_css'] = array(
        $css_path.'bootstrap.css',
        $css_path.'base.css',
        $css_path.'base_chamilo.css',
        $css_path.$app['app.theme'].'/default.css',
        $css_path.'bootstrap-responsive.css',
        $css_path.'responsive.css',
    //  api_get_path(LIBRARY_PATH).'javascript/thickbox.css',
    //  api_get_path(LIBRARY_PATH).'javascript/chosen/chosen.css',
        $css_path.$app['app.theme'].'/learnpath.css',
        $css_path.$app['app.theme'].'/scorm.css',
        api_get_path(LIBRARY_PATH).'javascript/chat/css/chat.css',
        api_get_path(LIBRARY_PATH).'javascript/jquery-ui/'.$app['jquery_ui_theme'].'/jquery-ui-custom.css',
        api_get_path(LIBRARY_PATH).'javascript/jquery-ui/default.css',
        //api_get_path(LIBRARY_PATH).'javascript/bxslider/bx_styles/bx_styles.css',
    );

    $app['assetic.output.path_to_css'] = 'css/'.$app['app.theme'].'/style.css';

    $app['assetic.input.path_to_js'] = array(
        api_get_path(LIBRARY_PATH).'javascript/modernizr.js',
        api_get_path(LIBRARY_PATH).'javascript/jquery.min.js',
        //api_get_path(LIBRARY_PATH).'javascript/chosen/chosen.jquery.min.js',
        api_get_path(LIBRARY_PATH).'javascript/jquery-ui/'.$app['jquery_ui_theme'].'/jquery-ui-custom.min.js',
        //api_get_path(LIBRARY_PATH).'javascript/thickbox.js',
        api_get_path(LIBRARY_PATH).'javascript/bootstrap/bootstrap.js',
        //api_get_path(LIBRARY_PATH).'javascript/bxslider/jquery.bxSlider.min.js',
    );

    $app['assetic.output.path_to_js'] = 'js/script.js';
    $app['assetic.filter.yui_compressor.path'] = '/usr/share/yui-compressor/yui-compressor.jar';

    // Create directories.
    if (!is_dir($app['assetic.path_to_web'])) {
        mkdir($app['assetic.path_to_web'], api_get_permissions_for_new_directories());
    }

    if (!is_dir($app['assetic.path_to_web'].'/css')) {
        mkdir($app['assetic.path_to_web'].'/css', api_get_permissions_for_new_directories());
    }

    if (!is_dir($app['assetic.path_to_web'].'/css/'.$app['app.theme'])) {
        mkdir($app['assetic.path_to_web'].'/css/'.$app['app.theme'], api_get_permissions_for_new_directories());
    }

    if (!is_dir($app['assetic.path_to_web'].'/js')) {
        mkdir($app['assetic.path_to_web'].'/js', api_get_permissions_for_new_directories());
    }
}

// Loop in the folder array and create temp folders

foreach ($app['temp.paths']->folders as $folder) {
    if (!is_dir($folder)) {
        @mkdir($folder, api_get_permissions_for_new_directories());
    }
}

// Monolog log file
$app['chamilo.log'] = $app['sys_log_path'].'/chamilo.log';

if (is_file($app['chamilo.log']) && !is_writable($app['chamilo.log'])) {
    unlink($app['chamilo.log']);
}
