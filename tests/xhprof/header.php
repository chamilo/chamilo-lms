<?php
if (extension_loaded('xhprof')) {
    include_once dirname(__FILE__).'/xhprof_lib/utils/xhprof_lib.php';
    include_once dirname(__FILE__).'/xhprof_lib/utils/xhprof_runs.php';
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}
