<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\TrackingStatsHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final class GetCourseStatsAction
{
    public function __construct(
        private readonly CourseRepository $courseRepo,
        private readonly SessionRepository $sessionRepo,
        private readonly TrackingStatsHelper $statsHelper,
    ) {}

    public function __invoke(int $id, string $metric, Request $request): JsonResponse
    {
        /** @var Course $course */
        $course = $this->courseRepo->find($id);
        if (!$course) {
            throw new NotFoundHttpException('Course not found.');
        }

        // Optional session
        $session = null;
        $sessionId = $request->query->getInt('sessionId') ?: null;
        if ($sessionId) {
            $session = $this->sessionRepo->find($sessionId);
            if (!$session) {
                throw new NotFoundHttpException('Session not found.');
            }
        }

        // Switch by metric
        $payload = match ($metric) {
            'course-avg-score' => $this->statsHelper->getCourseAverageScore($course, $session),
            'course-avg-progress' => $this->statsHelper->getCourseAverageProgress($course, $session),
            default => throw new NotFoundHttpException('Metric not supported.'),
        };

        return new JsonResponse($payload, 200);
    }
}
