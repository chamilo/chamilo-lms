<?php
/**
 * Comes at the end of every script if loaded from php config
 * @package chamilo.profiling
 */
/**
 * Init
 */
if (extension_loaded('xhprof')) {
    $profiler_namespace = 'chamilolms';  // namespace for your application
    $xhprof_data = xhprof_disable();
    $xhprof_runs = new XHProfRuns_Default();
    $run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);

    // url to the XHProf UI libraries (change the host name and path)
    require_once __DIR__.'/../../main/inc/lib/main_api.lib.php';
    $url = api_get_path(WEB_PATH);
    $profiler_url = sprintf($url.'tests/xhprof/xhprof_html/index.php?run=%s&source=%s', $run_id, $profiler_namespace);
    $xhprof =  '<br /><a class="btn btn-primary" href="'. $profiler_url .'" target="_blank">xhprof profiler output</a><br /><br />';
    echo $xhprof;
    error_log("xhprof runid: $run_id");
}
