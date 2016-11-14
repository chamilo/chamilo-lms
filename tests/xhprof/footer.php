<?php
/**
 * @package chamilo.profiling
 */
/**
 * Init
 */
if (extension_loaded('tideways')) {
    $profiler_namespace = 'chamilolms';  // namespace for your application
    $xhprof_data = tideways_disable();
    //$xhprof_runs = new XHProfRuns_Default();
    //$run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
    $run_id = uniqid();
    file_put_contents(sys_get_temp_dir() . '/' . $run_id . '.' . $profiler_namespace . '.xhprof', serialize($xhprof_data));
    // url to the XHProf UI libraries (change the host name and path)
    $subDir = substr(__DIR__, strlen(trim($_SERVER['DOCUMENT_ROOT'])));
    $profiler_url = sprintf($subDir.'/xhprof_html/index.php?run=%s&source=%s', $run_id, $profiler_namespace);
    echo '<a style="z-index:99; position: absolute;" href="'. $profiler_url .'" target="_blank">Profiler output</a>';
}
