<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Component\Badge\OpenBadge2;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once __DIR__.'/../inc/global.inc.php';

try {
    $response = new JsonResponse(OpenBadge2::issuer());
    $response->headers->set('Cache-Control', 'no-store');
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->send();
} catch (Throwable $exception) {
    error_log('[OpenBadges] Issuer unavailable: '.$exception->getMessage());
    (new JsonResponse(['error' => 'The issuer metadata is incomplete or unavailable.'], 503))->send();
}

exit;
