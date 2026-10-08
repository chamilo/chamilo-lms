<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Conference;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Security\CourseAccessResolver;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class TeamsMeetingAccessHelper
{
    public const string SCOPE_COURSE = 'course';
    public const string SCOPE_PERSONAL = 'personal';
    public const string SCOPE_GLOBAL = 'global';

    public function __construct(
        private Security $security,
        private CidReqHelper $cidReqHelper,
        private AccessUrlHelper $accessUrlHelper,
        private StudentViewHelper $studentViewHelper,
        private UserHelper $userHelper,
        private TeamsPluginConfigurationInterface $pluginConfiguration,
        private CourseAccessResolver $courseAccessResolver,
    ) {}

    public function normalizeScope(string $scope): string
    {
        $scope = strtolower(trim($scope));
        if (!\in_array($scope, [self::SCOPE_COURSE, self::SCOPE_PERSONAL, self::SCOPE_GLOBAL], true)) {
            throw new BadRequestHttpException('The Teams meeting scope must be course, personal or global.');
        }

        return $scope;
    }

    public function getAuthenticatedUser(): User
    {
        $user = $this->userHelper->getCurrent();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        return $user;
    }

    public function getCurrentAccessUrl(): AccessUrl
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        return $accessUrl;
    }

    /**
     * @return array{course: Course, session: ?Session, group: ?CGroup}
     */
    public function getCourseContext(): array
    {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->cidReqHelper->getDoctrineGroupEntity();

        $this->assertSessionBelongsToCourse($session, $course);
        $this->assertGroupBelongsToContext($group, $course, $session);

        return [
            'course' => $course,
            'session' => $session,
            'group' => $group,
        ];
    }

    public function assertCanReadScope(string $scope): void
    {
        $this->assertPluginEnabled();
        $scope = $this->normalizeScope($scope);
        $this->assertScopeEnabled($scope);
        $this->getAuthenticatedUser();
        $this->getCurrentAccessUrl();

        if (self::SCOPE_COURSE !== $scope) {
            return;
        }

        $this->getCourseContext();
        if (!$this->canReadCourseContext()) {
            throw new AccessDeniedHttpException('You are not allowed to view Teams meetings in this course context.');
        }
    }

    public function assertCanCreateScope(string $scope): void
    {
        $this->assertPluginEnabled();
        $scope = $this->normalizeScope($scope);
        $this->assertScopeEnabled($scope);
        $this->getAuthenticatedUser();
        $this->getCurrentAccessUrl();

        if (self::SCOPE_PERSONAL === $scope) {
            return;
        }

        if (self::SCOPE_GLOBAL === $scope) {
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException('Only platform administrators can create global Teams meetings.');
            }

            return;
        }

        $context = $this->getCourseContext();
        if (!$this->canManageCourseContext($context['session'])) {
            throw new AccessDeniedHttpException('You are not allowed to create Teams meetings in this course context.');
        }
    }

    public function canCreateScope(string $scope): bool
    {
        try {
            $this->assertCanCreateScope($scope);

            return true;
        } catch (AccessDeniedHttpException|BadRequestHttpException) {
            return false;
        }
    }

    public function detectScope(ConferenceMeeting $meeting): string
    {
        if ($meeting->getCourse() instanceof Course) {
            return self::SCOPE_COURSE;
        }

        if ($meeting->getUser() instanceof User) {
            return self::SCOPE_PERSONAL;
        }

        return self::SCOPE_GLOBAL;
    }

    public function assertCanReadMeeting(ConferenceMeeting $meeting): void
    {
        $this->assertPluginEnabled();
        $user = $this->getAuthenticatedUser();
        $this->assertSameAccessUrl($meeting);

        $scope = $this->detectScope($meeting);
        $this->assertScopeEnabled($scope);
        if (self::SCOPE_GLOBAL === $scope) {
            return;
        }

        if (self::SCOPE_PERSONAL === $scope) {
            if ($meeting->getUser()?->getId() !== $user->getId()) {
                throw new AccessDeniedHttpException('You are not allowed to view this personal Teams meeting.');
            }

            return;
        }

        $context = $this->getCourseContext();
        $this->assertMeetingMatchesCourseContext($meeting, $context['course'], $context['session'], $context['group']);

        if (!$this->canReadCourseContext()) {
            throw new AccessDeniedHttpException('You are not allowed to view this Teams meeting.');
        }
    }

    public function assertCanManageMeeting(ConferenceMeeting $meeting): void
    {
        $this->assertPluginEnabled();
        $user = $this->getAuthenticatedUser();
        $this->assertSameAccessUrl($meeting);

        $scope = $this->detectScope($meeting);
        $this->assertScopeEnabled($scope);
        if (self::SCOPE_GLOBAL === $scope) {
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException('Only platform administrators can manage global Teams meetings.');
            }

            return;
        }

        if (self::SCOPE_PERSONAL === $scope) {
            if ($meeting->getUser()?->getId() !== $user->getId()) {
                throw new AccessDeniedHttpException('You are not allowed to manage this personal Teams meeting.');
            }

            return;
        }

        $context = $this->getCourseContext();
        $this->assertMeetingMatchesCourseContext($meeting, $context['course'], $context['session'], $context['group']);

        if (!$this->canManageCourseContext($context['session'])) {
            throw new AccessDeniedHttpException('You are not allowed to manage this Teams meeting.');
        }
    }

    public function canManageMeeting(ConferenceMeeting $meeting): bool
    {
        try {
            $this->assertCanManageMeeting($meeting);

            return true;
        } catch (AccessDeniedHttpException|BadRequestHttpException) {
            return false;
        }
    }

    /**
     * Authorizes a join flow without relying on cid/sid/gid from the current request.
     * This is used by the Microsoft OAuth round-trip, whose callback intentionally has
     * no course context in the URL.
     */
    public function assertCanJoinMeetingDirect(ConferenceMeeting $meeting): void
    {
        $this->assertPluginEnabled();
        $user = $this->getAuthenticatedUser();
        $this->assertSameAccessUrl($meeting);

        $scope = $this->detectScope($meeting);
        $this->assertScopeEnabled($scope);

        if (!$meeting->isOpen()) {
            throw new ConflictHttpException('The Teams meeting has been cancelled.');
        }

        $endAt = $meeting->getEndAt();
        if (null !== $endAt && $endAt < new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
            throw new ConflictHttpException('Past Teams meetings cannot be joined.');
        }

        $this->getSafeJoinUrl($meeting);

        if (self::SCOPE_GLOBAL === $scope) {
            return;
        }

        if (self::SCOPE_PERSONAL === $scope) {
            if ($meeting->getUser()?->getId() !== $user->getId()) {
                throw new AccessDeniedHttpException('You are not allowed to join this personal Teams meeting.');
            }

            return;
        }

        $course = $meeting->getCourse();
        if (!$course instanceof Course) {
            throw new AccessDeniedHttpException('The Teams meeting course context is missing.');
        }

        $session = $meeting->getSession();
        $group = $meeting->getGroup();
        $this->assertSessionBelongsToCourse($session, $course);
        $this->assertGroupBelongsToContext($group, $course, $session);

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $courseRoles = $this->courseAccessResolver->resolveCourseRoles($user, $course, $session);
        if ([] === $courseRoles) {
            throw new AccessDeniedHttpException('You are not allowed to join this Teams meeting in the course context.');
        }

        if ($group instanceof CGroup) {
            $groupRoles = $this->courseAccessResolver->resolveGroupRoles($user, $course, $group);
            if ([] === $groupRoles) {
                throw new AccessDeniedHttpException('You are not allowed to join this Teams meeting in the group context.');
            }
        }
    }

    public function getSafeJoinUrl(ConferenceMeeting $meeting): string
    {
        $joinUrl = trim((string) $meeting->getJoinUrl());
        if ('' === $joinUrl) {
            throw new ConflictHttpException('The Teams meeting join link is missing.');
        }

        $parts = parse_url($joinUrl);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $allowedHosts = [
            'teams.microsoft.com',
            'teams.cloud.microsoft',
            'teams.live.com',
        ];

        if ('https' !== $scheme || !\in_array($host, $allowedHosts, true)) {
            throw new ConflictHttpException('The Teams meeting join link is invalid.');
        }

        return $joinUrl;
    }

    private function assertPluginEnabled(): void
    {
        if (!$this->pluginConfiguration->isEnabled()) {
            throw new AccessDeniedHttpException('The Microsoft Teams plugin is not enabled.');
        }
    }

    private function assertScopeEnabled(string $scope): void
    {
        if (self::SCOPE_PERSONAL === $scope && !$this->pluginConfiguration->isPersonalConferenceEnabled()) {
            throw new AccessDeniedHttpException('Personal Microsoft Teams conferences are disabled.');
        }

        if (self::SCOPE_GLOBAL === $scope && !$this->pluginConfiguration->isGlobalConferenceEnabled()) {
            throw new AccessDeniedHttpException('Global Microsoft Teams conferences are disabled.');
        }
    }

    private function canReadCourseContext(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_GROUP_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_GROUP_STUDENT');
    }

    private function canManageCourseContext(?Session $session): bool
    {
        if ($this->studentViewHelper->isActive()) {
            return false;
        }

        if ($session instanceof Session && Session::READ_ONLY === $session->getVisibility()) {
            return false;
        }

        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_GROUP_TEACHER');
    }

    private function assertSameAccessUrl(ConferenceMeeting $meeting): void
    {
        $current = $this->getCurrentAccessUrl();
        $meetingUrl = $meeting->getAccessUrl();

        if (!$meetingUrl instanceof AccessUrl || $meetingUrl->getId() !== $current->getId()) {
            throw new AccessDeniedHttpException('The requested Teams meeting belongs to another access URL.');
        }
    }

    private function assertMeetingMatchesCourseContext(
        ConferenceMeeting $meeting,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): void {
        if ($meeting->getCourse()?->getId() !== $course->getId()) {
            throw new AccessDeniedHttpException('The requested Teams meeting belongs to another course.');
        }

        $meetingSessionId = $meeting->getSession()?->getId();
        $contextSessionId = $session?->getId();
        if ($meetingSessionId !== $contextSessionId) {
            throw new AccessDeniedHttpException('The requested Teams meeting belongs to another session context.');
        }

        $meetingGroupId = $meeting->getGroup()?->getIid();
        $contextGroupId = $group?->getIid();
        if ($meetingGroupId !== $contextGroupId) {
            throw new AccessDeniedHttpException('The requested Teams meeting belongs to another group context.');
        }
    }

    private function assertSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session does not contain the current course.');
    }

    private function assertGroupBelongsToContext(?CGroup $group, Course $course, ?Session $session): void
    {
        if (!$group instanceof CGroup) {
            return;
        }

        $resourceNode = $group->getResourceNode();
        if (null === $resourceNode) {
            throw new AccessDeniedHttpException('The requested group does not belong to the current course context.');
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink || null !== $link->getDeletedAt()) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();

            if ($sameCourse && $sameSession) {
                return;
            }
        }

        throw new AccessDeniedHttpException('The requested group does not belong to the current course context.');
    }
}
