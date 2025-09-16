<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Repository\CLpRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class LpReorderController
{
    public function __construct(
        private CLpRepository $lpRepo
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return $this->reorder($request);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '[]', true);
        $courseId = isset($data['courseId']) ? (int) $data['courseId'] : null;
        $sid = \array_key_exists('sid', $data) ? (null !== $data['sid'] ? (int) $data['sid'] : null) : null;
        $order = $data['order'] ?? $data['ids'] ?? null;
        $categoryId = \array_key_exists('categoryId', $data) ? (null !== $data['categoryId'] ? (int) $data['categoryId'] : null) : null;

        if (!$courseId || !\is_array($order)) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        $this->lpRepo->reorderByIds($courseId, $sid, array_map('intval', $order), $categoryId);

        return new JsonResponse(null, 204);
    }
}
