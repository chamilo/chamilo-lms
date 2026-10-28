<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Component\Badge\OpenBadge2;
use Chamilo\CoreBundle\Entity\Skill;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once __DIR__.'/../inc/global.inc.php';

$id = filter_var($_GET['id'] ?? null, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$skill = false !== $id ? Database::getManager()->find(Skill::class, $id) : null;
if (!$skill instanceof Skill || $skill->getAccessUrlId() !== api_get_current_access_url_id()) {
    (new JsonResponse(['error' => 'Badge not found.'], 404))->send();

    exit;
}

try {
    $response = new JsonResponse(OpenBadge2::badge($skill));
    $response->headers->set('Cache-Control', 'no-store');
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->send();
} catch (Throwable $exception) {
    error_log('[OpenBadges] Badge class unavailable: '.$exception->getMessage());
    (new JsonResponse(['error' => 'The badge metadata is incomplete or unavailable.'], 503))->send();
}

exit;
