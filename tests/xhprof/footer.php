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
    $subDir = substr(__DIR__, strlen(trim($_SERVER['DOCUMENT_ROOT'])));
    $profiler_url = sprintf($subDir.'/xhprof_html/index.php?run=%s&source=%s', $run_id, $profiler_namespace);
    echo '<a href="'. $profiler_url .'" target="_blank">Profiler output</a>';
}
