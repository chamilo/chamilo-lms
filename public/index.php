<?php

/* For licensing terms, see /license.txt */

use Chamilo\Kernel;
use Symfony\Component\HttpFoundation\RedirectResponse as HttpRedirectResponse;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    // Do NOT rely on $context for custom env vars (Runtime may not include them).
    $installed = (string) (
        $_SERVER['APP_INSTALLED']
        ?? $_ENV['APP_INSTALLED']
        ?? getenv('APP_INSTALLED')
        ?? '0'
    );

    if ($installed !== '1') {
        return new HttpRedirectResponse('./main/install/index.php');
    }

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
