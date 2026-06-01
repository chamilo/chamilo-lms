<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class LpReorderController
{
    public function __construct(
        private CLpRepository $lpRepo,
        private CidReqHelper $cidReqHelper,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return $this->reorder($request);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '[]', true);
        if (!\is_array($data)) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        $order = $data['order'] ?? $data['ids'] ?? null;
        $categoryId = \array_key_exists('categoryId', $data) ? (null !== $data['categoryId'] ? (int) $data['categoryId'] : null) : null;

        if (!\is_array($order)) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        // The course/session come from the session context resolved by CidReqListener from
        // the cid/sid query params, which is the same context that gated this operation's
        // contextual teacher role. The request body is never trusted for the course id, so
        // a teacher cannot reorder another course's learning paths (IDOR).
        $course = $this->cidReqHelper->getCourseEntity();
        if (!$course instanceof Course) {
            return new JsonResponse(['error' => 'Missing course context'], 400);
        }

        $sid = $this->cidReqHelper->getSessionId();

        $this->lpRepo->reorderByIds((int) $course->getId(), $sid, array_map('intval', $order), $categoryId);

        return new JsonResponse(null, 204);
    }
}
