<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Component\Badge\OpenBadge2;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once __DIR__.'/../inc/global.inc.php';

$repository = Database::getManager()->getRepository(SkillRelUser::class);
$issue = null;
if (isset($_GET['issue'])) {
    $id = filter_var($_GET['issue'], \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (false !== $id) {
        $issue = $repository->find($id);
    }
} else {
    // Preserve old baked URLs only when they identify one issue unambiguously.
    $userId = filter_var($_GET['user'] ?? null, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $skillId = filter_var($_GET['skill'] ?? null, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $courseId = filter_var($_GET['course'] ?? 0, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $sessionId = filter_var($_GET['session'] ?? 0, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if (false !== $userId && false !== $skillId && false !== $courseId && false !== $sessionId) {
        $skill = Database::getManager()->find(Skill::class, $skillId);
        if (!$skill instanceof Skill || $skill->getAccessUrlId() !== api_get_current_access_url_id()) {
            (new JsonResponse(['error' => 'Badge issue not found.'], 404))->send();

            exit;
        }
        $criteria = ['user' => $userId, 'skill' => $skillId];
        if ($courseId > 0) {
            $criteria['course'] = $courseId;
            $criteria['session'] = $sessionId > 0 ? $sessionId : null;
        }
        $issues = $repository->findBy($criteria, ['id' => 'ASC'], 2);
        if (count($issues) > 1) {
            (new JsonResponse(['error' => 'This legacy URL matches multiple issues. Download the specific badge again.'], 409))->send();

            exit;
        }
        $issue = $issues[0] ?? null;
    }
}

if (!$issue instanceof SkillRelUser
    || null === $issue->getSkill()
    || $issue->getSkill()->getAccessUrlId() !== api_get_current_access_url_id()
) {
    (new JsonResponse(['error' => 'Badge issue not found.'], 404))->send();

    exit;
}

if (!isset($_GET['issue'])) {
    header('Cache-Control: no-store');
    header('Location: '.OpenBadge2::assertionUrl($issue), true, 302);

    exit;
}

try {
    $response = new JsonResponse(OpenBadge2::assertion($issue));
    $response->headers->set('Cache-Control', 'no-store');
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->send();
} catch (Throwable $exception) {
    error_log('[OpenBadges] Assertion unavailable: '.$exception->getMessage());
    (new JsonResponse(['error' => 'The badge metadata is incomplete or unavailable.'], 503))->send();
}

exit;
