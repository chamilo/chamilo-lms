<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Event;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait ForumStateHelperTrait
{
    private function assertForumMemberAccess(Security $security, string $message): void
    {
        if ($security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
            || $security->isGranted('ROLE_ADMIN')
        ) {
            return;
        }

        throw new AccessDeniedHttpException($message);
    }

    private function isTeacher(Security $security): bool
    {
        return $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || $security->isGranted('ROLE_ADMIN');
    }

    private function isStudentView(Request $request): bool
    {
        return 'studentview' === $request->getSession()->get('studentview');
    }

    private function canManageForumsInCurrentView(Security $security, Request $request): bool
    {
        return $this->isTeacher($security) && !$this->isStudentView($request);
    }

    private function getCourse(EntityManagerInterface $entityManager, Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('Missing course id.');
        }

        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('Course not found.');
        }

        return $course;
    }

    private function getSession(EntityManagerInterface $entityManager, Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new NotFoundHttpException('Session not found.');
        }

        $courseId = $request->query->getInt('cid');
        if ($courseId > 0) {
            $course = $entityManager->getRepository(Course::class)->find($courseId);
            if (!$course instanceof Course) {
                throw new NotFoundHttpException('Course not found.');
            }

            $sessionCourse = $entityManager->getRepository(SessionRelCourse::class)->findOneBy([
                'course' => $course,
                'session' => $session,
            ]);

            if (!$sessionCourse instanceof SessionRelCourse) {
                throw new AccessDeniedHttpException('The requested session is not linked to this course.');
            }
        }

        return $session;
    }

    private function getGroup(EntityManagerInterface $entityManager, Request $request): ?CGroup
    {
        $groupId = $request->query->getInt('gid');
        if ($groupId <= 0) {
            return null;
        }

        $group = $entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            throw new NotFoundHttpException('Group not found.');
        }

        return $group;
    }

    private function getParentNode(EntityManagerInterface $entityManager, Request $request): ResourceNode
    {
        $parentNodeId = $request->query->getInt('resourceNode_parent', $request->query->getInt('resourceNode.parent'));
        if ($parentNodeId <= 0) {
            throw new BadRequestHttpException('Missing resource node parent.');
        }

        $parentNode = $entityManager->getRepository(ResourceNode::class)->find($parentNodeId);
        if (!$parentNode instanceof ResourceNode) {
            throw new NotFoundHttpException('Resource node parent not found.');
        }

        return $parentNode;
    }

    private function parseApiId(mixed $value): int
    {
        if (\is_int($value)) {
            return $value;
        }

        $value = trim((string) $value);
        if ('' === $value) {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $parts = explode('/', $value);

        return (int) end($parts);
    }

    private function isForumResourceVisible(AbstractResource $resource, Course $course, ?Session $session): bool
    {
        return $resource->isVisible($course, $session);
    }

    private function getForumAvailabilityStatus(CForum $forum): string
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $startTime = $forum->getStartTime();
        if ($startTime instanceof DateTimeInterface && $startTime > $now) {
            return 'not_started';
        }

        $endTime = $forum->getEndTime();
        if ($endTime instanceof DateTimeInterface && $endTime < $now) {
            return 'closed';
        }

        return 'open';
    }

    private function isForumOpenForParticipation(CForum $forum): bool
    {
        return 'open' === $this->getForumAvailabilityStatus($forum);
    }

    private function assertForumOpenForParticipation(CForum $forum): void
    {
        $status = $this->getForumAvailabilityStatus($forum);
        if ('not_started' === $status) {
            throw new AccessDeniedHttpException('The forum is not open yet.');
        }

        if ('closed' === $status) {
            throw new AccessDeniedHttpException('The forum is closed.');
        }
    }

    private function canListForumWithCurrentSettings(CForum $forum, Request $request, bool $displayGroupForums): bool
    {
        if ($displayGroupForums || $request->query->getInt('gid') > 0) {
            return true;
        }

        return 0 === (int) $forum->getForumOfGroup();
    }

    private function registerForumEventLog(string $action, string $details = '', string $info = ''): void
    {
        if (!class_exists('Event')) {
            return;
        }

        $logInfo = [
            'tool' => \defined('TOOL_FORUM') ? \constant('TOOL_FORUM') : 'forum',
            'action' => $action,
            'action_details' => $details,
        ];

        if ('' !== $info) {
            $logInfo['info'] = $info;
        }

        try {
            Event::registerLog($logInfo);
        } catch (Throwable) {
            // Tracking must never break forum actions.
        }
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        if (null === $date) {
            return null;
        }

        return $date->format(DateTimeInterface::ATOM);
    }
}
