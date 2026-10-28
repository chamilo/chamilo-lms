<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Component\Badge\OpenBadge2;
use Chamilo\CoreBundle\Entity\Skill;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/../inc/global.inc.php';

$id = filter_var($_GET['id'] ?? null, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$skill = false !== $id ? Database::getManager()->find(Skill::class, $id) : null;
if (!$skill instanceof Skill || $skill->getAccessUrlId() !== api_get_current_access_url_id()) {
    (new JsonResponse(['error' => 'Badge not found.'], 404))->send();

    exit;
}

try {
    (new Response(OpenBadge2::image($skill), 200, [
        'Content-Type' => 'image/png',
        'X-Content-Type-Options' => 'nosniff',
        'Cache-Control' => 'no-store',
    ]))->send();
} catch (Throwable $exception) {
    error_log('[OpenBadges] Image unavailable: '.$exception->getMessage());
    (new JsonResponse(['error' => 'The badge PNG image is unavailable.'], 503))->send();
}

exit;
