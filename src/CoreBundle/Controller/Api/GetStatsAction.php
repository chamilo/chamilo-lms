<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\TrackingStatsHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final class GetStatsAction
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly CourseRepository $courseRepo,
        private readonly SessionRepository $sessionRepo,
        private readonly TrackingStatsHelper $statsHelper,
        private readonly UserHelper $userHelper,
    ) {}

    public function __invoke(int $id, int $courseId, string $metric, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->userRepo->find($id);
        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }

        /** @var Course $course */
        $course = $this->courseRepo->find($courseId);
        if (!$course) {
            throw new NotFoundHttpException('Course not found.');
        }

        $session = null;
        $sessionId = $request->query->getInt('sessionId') ?: null;
        if ($sessionId) {
            $session = $this->sessionRepo->find($sessionId);
            if (!$session) {
                throw new NotFoundHttpException('Session not found.');
            }
        }

        $this->denyUnlessGranted($user, $course, $session);

        $payload = match ($metric) {
            'avg-lp-progress' => $this->statsHelper->getUserAvgLpProgress($user, $course, $session),
            'certificates' => $this->statsHelper->getUserCertificates($user, $course, $session),
            'gradebook-global' => $this->statsHelper->getUserGradebookGlobal($user, $course, $session),
            default => throw new NotFoundHttpException('Metric not supported.'),
        };

        return new JsonResponse($payload, 200);
    }

    private function denyUnlessGranted(User $targetUser, Course $course, ?Session $session): void
    {
        $currentUser = $this->userHelper->getCurrent();

        if (!$currentUser) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        if ($currentUser->isAdmin()) {
            return;
        }

        // Users can view their own stats
        if ($currentUser->getId() === $targetUser->getId()) {
            return;
        }

        if ($session) {
            // Session general coach can view stats of users in their sessions
            if ($session->hasUserAsGeneralCoach($currentUser)) {
                return;
            }

            // Course coach in session can view stats of users in their course
            if ($session->hasCourseCoachInCourse($currentUser, $course)) {
                return;
            }
        } elseif ($course->hasUserAsTeacher($currentUser)) {
            // Course teacher can view stats of students in their course
            return;
        }

        throw new AccessDeniedHttpException('Access denied.');
    }
}
