<?php
/**
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
    $profiler_url = sprintf('http://my.chamilo.net/tests/xhprof/xhprof_html/index.php?run=%s&source=%s', $run_id, $profiler_namespace);
    $xhprof =  '<a href="'. $profiler_url .'" target="_blank">Profiler output</a>';
    error_log($run_id);
}
