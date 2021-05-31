<?php

/* For licensing terms, see /license.txt */

use Chamilo\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    // Chamilo
    $isInstalled = $context['APP_INSTALLED'] ?? null;
    if (1 !== (int) $isInstalled) {
        // Does not support subdirectories for now
        header('Location: /main/install/index.php');
        exit;
    }

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
