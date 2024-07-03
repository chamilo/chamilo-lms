<?php
/* For license terms, see /license.txt */
/**
 * @package chamilo.profiling
 */
/**
 * Init. Xhprof has been replaced by The Tideways profiler as Xhprof is not
 * maintained for PHP anymore (Facebook moved to HHVM).
 * See https://tideways.io/profiler/
 * To use, you should load header.php and footer.php through an append_file
 * in your php config also disable the .htaccess line about the tests/
 * directory.
 */
use Tideways\Profiler;

$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if (extension_loaded('tideways_xhprof') && !$isAjaxRequest) {
    //include_once __DIR__.'/xhprof_lib/utils/xhprof_lib.php';
    //include_once __DIR__.'/xhprof_lib/utils/xhprof_runs.php';
    tideways_xhprof_enable();
}
