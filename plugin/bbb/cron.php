<?php

require __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/config.vm.php')) {

    require_once dirname(__FILE__) . '/config.php';

    require __DIR__ . '/lib/vm/AbstractVM.php';
    require __DIR__ . '/lib/vm/VMInterface.php';
    require __DIR__ . '/lib/vm/DigitalOceanVM.php';
    require __DIR__ . '/lib/VM.php';

    $config = require __DIR__ . '/config.vm.php';

    $vm = new VM($config);

    if ($vm->isEnabled()) {
        $bbb = new bbb();
        if ($bbb->pluginEnabled) {
            $activeSessions = $bbb->getActiveSessionsCount();
            if (empty($activeSessions)) {
                $vm->runCron();
            } else {
                echo "Can't run cron active sessions found: " . $activeSessions;
            }
        }
    }
}
