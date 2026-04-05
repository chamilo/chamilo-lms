<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/../../../vendor/autoload.php';

function bbb_is_local_cron_request(): bool
{
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

    return in_array($remoteAddr, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true);
}

function bbb_can_run_vm_cron(BbbPlugin $plugin): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }

    if (api_is_platform_admin() || bbb_is_local_cron_request()) {
        return true;
    }

    $requestToken = $_GET['token'] ?? $_POST['token'] ?? '';
    $salt = (string) $plugin->get('salt');
    if (!is_string($requestToken) || $requestToken === '' || $salt === '') {
        return false;
    }

    $expectedToken = hash_hmac('sha256', 'bbb_cron|vm', $salt);

    return hash_equals($expectedToken, $requestToken);
}

if (file_exists(__DIR__.'/config.vm.php')) {
    require_once __DIR__.'/config.php';

    $plugin = BbbPlugin::create();
    if (!bbb_can_run_vm_cron($plugin)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }

    require __DIR__.'/lib/vm/AbstractVM.php';
    require __DIR__.'/lib/vm/VMInterface.php';
    require __DIR__.'/lib/vm/DigitalOceanVM.php';
    require __DIR__.'/lib/VM.php';

    $config = require __DIR__.'/config.vm.php';

    $vm = new VM($config);

    if ($vm->isEnabled()) {
        $bbb = new Bbb();
        if ($bbb->pluginEnabled) {
            $activeSessions = $bbb->getActiveSessionsCount();
            if (empty($activeSessions)) {
                $vm->runCron();
            } else {
                echo "Can't run cron active sessions found: ".$activeSessions;
            }
        }
    }
}
