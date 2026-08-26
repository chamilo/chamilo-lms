<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
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

    /**
     * CidReqListener already resolved and validated the course, so a missing entity here
     * can only mean the request carried no course context at all.
     */
    private function getCourse(CidReqHelper $cidReqHelper): Course
    {
        return $cidReqHelper->getDoctrineCourseEntity()
            ?? throw new BadRequestHttpException('Missing course id.');
    }

    /**
     * SessionVoter proves the course/session pairing for students and course coaches, but not
     * for general coaches or admins. Assert it before persisting a resource link, so an
     * unrelated pair can never be written.
     */
    private function assertSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session is not linked to this course.');
    }

    private function getGroup(EntityManagerInterface $entityManager, CidReqHelper $cidReqHelper): ?CGroup
    {
        return $cidReqHelper->getDoctrineGroupEntity();
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

    private function canListForumWithCurrentSettings(CForum $forum, CidReqHelper $cidReqHelper, bool $displayGroupForums): bool
    {
        if ($displayGroupForums || (int) ($cidReqHelper->getGroupId() ?? 0) > 0) {
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
