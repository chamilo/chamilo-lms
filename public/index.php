<?php

/* For licensing terms, see /license.txt */

use Chamilo\Kernel;
use Symfony\Component\HttpFoundation\RedirectResponse as HttpRedirectResponse;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    // Chamilo
    $isInstalled = $context['APP_INSTALLED'] ?? null;
    if (1 !== (int) $isInstalled) {
        return new HttpRedirectResponse('./main/install/index.php');
    }

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
