<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractTrackingStateProvider
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly CourseRepository $courseRepository,
        protected readonly SessionRepository $sessionRepository,
        protected readonly RequestStack $requestStack,
        protected readonly Security $security,
    ) {}

    protected function getCurrentRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new BadRequestHttpException('Request not available.');
        }

        return $request;
    }

    protected function getRequiredIntQueryParameter(string $name): int
    {
        $value = $this->getCurrentRequest()->query->getInt($name);

        if ($value <= 0) {
            throw new BadRequestHttpException(\sprintf('Missing or invalid %s.', $name));
        }

        return $value;
    }

    protected function getOptionalIntQueryParameter(string $name): ?int
    {
        $value = $this->getCurrentRequest()->query->getInt($name);

        return $value > 0 ? $value : null;
    }

    protected function getUserFromQuery(string $parameterName = 'userId'): User
    {
        $userId = $this->getRequiredIntQueryParameter($parameterName);
        $user = $this->entityManager->find(User::class, $userId);

        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found.');
        }

        return $user;
    }

    protected function getCourseFromQuery(string $parameterName = 'courseId'): Course
    {
        $courseId = $this->getRequiredIntQueryParameter($parameterName);
        $course = $this->courseRepository->find($courseId);

        if (!$course instanceof Course) {
            throw new NotFoundHttpException('Course not found.');
        }

        return $course;
    }

    protected function getSessionFromQuery(string $parameterName = 'sessionId'): ?Session
    {
        $sessionId = $this->getOptionalIntQueryParameter($parameterName);
        if (null === $sessionId) {
            return null;
        }

        $session = $this->sessionRepository->find($sessionId);

        if (!$session instanceof Session) {
            throw new NotFoundHttpException('Session not found.');
        }

        return $session;
    }

    protected function denyUnlessCanViewCourse(Course $course): void
    {
        if (!$this->security->isGranted('VIEW', $course)) {
            throw new AccessDeniedHttpException('Not allowed.');
        }
    }

    protected function denyUnlessCanReadUserTracking(User $targetUser, Course $course, ?Session $session): void
    {
        $this->denyUnlessCanViewCourse($course);

        $currentUser = $this->security->getUser();

        $isSameUser = $currentUser instanceof User
            && $currentUser->getId() === $targetUser->getId();

        $isCourseTeacher = $currentUser instanceof User && (
            $course->hasUserAsTeacher($currentUser)
                || ($session && $session->hasCoachInCourseList($currentUser))
        );

        $isPrivileged = api_is_platform_admin()
            || $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_SESSION_MANAGER')
            || $this->security->isGranted('ROLE_HR')
            || $this->security->isGranted('ROLE_STUDENT_BOSS')
            || $isCourseTeacher;

        if (!$isSameUser && !$isPrivileged) {
            throw new AccessDeniedHttpException('Not allowed.');
        }
    }
}
