<?php
/* For licensing terms, see /license.txt */

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
$app['temp.paths']->folders[] = $app['db.orm.proxies_dir'] = $app['temp.path'].'Proxies';

// Symfony2 Web profiler.
$app['temp.paths']->folders[] = $app['profiler.cache_dir'] = $app['temp.path'].'profiler';

// HTMLPurifier.
$app['temp.paths']->folders[] = $app['htmlpurifier.serializer'] = $app['temp.path'].'serializer';

// PCLZIP temp dir.
define('PCLZIP_TEMPORARY_DIR', $app['temp.path'].'pclzip');
$app['temp.paths']->folders[] = $app['temp.path'].'pclzip';

// MPDF temp libs.
define("_MPDF_TEMP_PATH", $app['temp.path'].'mpdf');
define("_JPGRAPH_PATH", $app['temp.path'].'mpdf');
define("_MPDF_TTFONTDATAPATH", $app['temp.path'].'mpdf');

$app['temp.paths']->folders[] = $app['temp.path'].'mpdf';

// QR code.
define('QR_CACHE_DIR', $app['temp.path'].'qr');
define('QR_LOG_DIR', $app['temp.path'].'qr');

$app['temp.paths']->folders[] = $app['temp.path'].'qr';

// Chamilo Temp class @todo fix this
$app['temp.paths']->folders[] = $app['temp.path'].'temp';

// Assetic.

if ($app['assetic.enabled']) {
    $jsFolder = api_get_path(SYS_LIBRARY_JS_PATH);
    // Assetic cache folder.
    $app['assetic.path_to_cache'] = $app['temp.path'].'assetic';

    $app['temp.paths']->folders[] = $app['assetic.path_to_cache'];

    // Location where to dump all generated files
    $app['assetic.path_to_web'] = api_get_path(SYS_PATH).'web';

    // web/chamilo
    $app['assetic.input.path_to_assets'] = $app['assetic.path_to_web'];
    $css_path = api_get_path(SYS_CSS_PATH);

    $app['assetic.input.path_to_css'] = array(
        $jsFolder.'bootstrap/css/bootstrap.css',
        $css_path.'base.css',
        $css_path.'base_chamilo.css',
        $css_path.$app['app.theme'].'/default.css',
        $css_path.'responsive.css',
        $css_path.$app['app.theme'].'/learnpath.css',
        $css_path.$app['app.theme'].'/scorm.css',
        $jsFolder.'chat/css/chat.css',
        $jsFolder.'jquery-ui/'.$app['jquery_ui_theme'].'/jquery-ui-custom.css',
        $jsFolder.'jquery-ui/default.css',
        //api_get_path(LIBRARY_PATH).'javascript/bxslider/bx_styles/bx_styles.css',
    );

    $app['assetic.output.path_to_css'] = 'css/'.$app['app.theme'].'/style.css';

    $app['assetic.input.path_to_js'] = array(
        $jsFolder.'javascript/modernizr.js',
        $jsFolder.'javascript/jquery.js',
        //api_get_path(LIBRARY_PATH).'javascript/chosen/chosen.jquery.min.js',
        $jsFolder.'javascript/jquery-ui/css/'.$app['jquery_ui_theme'].'/jquery-ui-custom.min.js',
        //api_get_path(LIBRARY_PATH).'javascript/thickbox.js',
        $jsFolder.'javascript/bootstrap/bootstrap.js',
        //api_get_path(LIBRARY_PATH).'javascript/bxslider/jquery.bxSlider.min.js',
    );

    $app['assetic.output.path_to_js'] = 'js/script.js';
    $app['assetic.filter.yui_compressor.path'] = '/usr/share/yui-compressor/yui-compressor.jar';

    //if ($app['assetic.auto_dump_assets']) {

        // Create directories.
        $app['temp.paths']->folders[] = $app['assetic.path_to_web'];
        $app['temp.paths']->folders[] = $app['assetic.path_to_web'].'/css';
        $app['temp.paths']->folders[] = $app['assetic.path_to_web'].'/css/'.$app['app.theme'];
        $app['temp.paths']->folders[] = $app['assetic.path_to_web'].'/js';

        $app['temp.paths']->copyFolders = array(
            $app['root_sys'].'main/css/'.$app['app.theme'] => $app['assetic.path_to_web'].'/css/'.$app['app.theme']
        );
    //}
}

// Monolog log file
$app['chamilo.log'] = $app['sys_log_path'].'/chamilo.log';

// If the chamilo.lig is not writable try to delete it
if (is_file($app['chamilo.log']) && !is_writable($app['chamilo.log'])) {
    unlink($app['chamilo.log']);
}
