<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CWiki;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The Wiki access rules that IsAllowedToEditHelper cannot answer.
 *
 * Managing a wiki is broader than editing course content: a group tutor manages the group's
 * wiki, an HR manager reaches session content through its own setting, and a plain student
 * may create or edit an unlocked page. Those terms have no equivalent in the shared helper,
 * so they live here and are composed on top of it.
 */
readonly class WikiHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private UserHelper $userHelper,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private StudentViewHelper $studentViewHelper,
    ) {}

    public function assertToolEnabled(Course $course): void
    {
        if ($this->isCourseSettingEnabled($course, 'enabled', true)) {
            return;
        }

        throw new AccessDeniedHttpException('The Wiki tool is disabled for this course.');
    }

    public function assertSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session does not contain the current course.');
    }

    public function assertGroupBelongsToContext(?CGroup $group, Course $course, ?Session $session): void
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

    public function assertRouteNode(Course $course, Request $request): int
    {
        $nodeId = $request->query->getInt('node');
        $courseNodeId = $course->getResourceNode()?->getId();

        if ($nodeId <= 0 || null === $courseNodeId || $nodeId !== (int) $courseNodeId) {
            throw new AccessDeniedHttpException('The Wiki route does not belong to the current course.');
        }

        return $nodeId;
    }

    public function assertPageVisible(CWiki $wiki, bool $canManage): void
    {
        if (1 === $wiki->getVisibility() || $canManage) {
            return;
        }

        $user = $this->userHelper->getCurrent();
        if (2 === $wiki->getAssignment()
            && 0 === $wiki->getVisibility()
            && $user instanceof User
            && $wiki->getUserId() === (int) $user->getId()
        ) {
            return;
        }

        throw new AccessDeniedHttpException('This Wiki page is not visible in the current context.');
    }

    public function canRead(Course $course, ?Session $session, ?CGroup $group): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->userHelper->getCurrent();
        if (!$user instanceof User) {
            return false;
        }

        $isCourseTeacher = $course->hasUserAsTeacher($user)
            || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if ($session instanceof Session) {
            $canReadCourse = $isCourseTeacher
                || $session->hasUserAsGeneralCoach($user)
                || $session->hasCourseCoachInCourse($user, $course)
                || $session->hasUserInCourse($user, $course)
                || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                || ($this->security->isGranted('ROLE_HR')
                    && $this->isSettingEnabled('session.drh_can_access_all_session_content'))
                || ($user->isSessionAdmin()
                    && $this->isSettingEnabled('session.session_admins_access_all_content'));
        } else {
            $canReadCourse = $isCourseTeacher
                || $this->security->isGranted(CourseVoter::VIEW, $course)
                || $course->hasSubscriptionByUser($user)
                || $course->isPublic();
        }

        if (!$canReadCourse) {
            return false;
        }

        if (!$group instanceof CGroup) {
            return true;
        }

        if ($isCourseTeacher
            || $group->hasTutor($user)
            || $this->security->isGranted('ROLE_CURRENT_COURSE_GROUP_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        ) {
            return true;
        }

        return match ($group->getWikiState()) {
            CGroup::TOOL_NOT_AVAILABLE => false,
            CGroup::TOOL_PUBLIC => true,
            CGroup::TOOL_PRIVATE,
            CGroup::TOOL_PRIVATE_BETWEEN_USERS => $group->hasMember($user),
            default => false,
        };
    }

    /**
     * Whether the current user may manage the wiki here.
     *
     * The shared helper covers the course-wide rules; the group tutor and the HR manager are
     * this tool's own additions, so they are checked separately and then gated once by the
     * student view, which the helper reports through its own answer.
     */
    public function canManage(Course $course, ?Session $session, ?CGroup $group): bool
    {
        if ($this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)) {
            return true;
        }

        // The terms below are this tool's own, so they must not revive an access the student
        // view just denied.
        if ($this->studentViewHelper->isActive()) {
            return false;
        }

        $user = $this->userHelper->getCurrent();
        if (!$user instanceof User) {
            return false;
        }

        if ($session instanceof Session
            && (Session::READ_ONLY === $session->getVisibility() || $this->isCourseLockedInsideSessions($course))
        ) {
            return false;
        }

        if ($group instanceof CGroup
            && ($group->hasTutor($user) || $this->security->isGranted('ROLE_CURRENT_COURSE_GROUP_TEACHER'))
        ) {
            return true;
        }

        return $session instanceof Session
            && $this->security->isGranted('ROLE_HR')
            && $this->isSettingEnabled('session.drh_can_access_all_session_content');
    }

    public function canManageCourseSettings(Course $course): bool
    {
        return $this->isAllowedToEditHelper->check(course: $course);
    }

    public function canCreatePage(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        string $reflink,
        int $addLock,
    ): bool {
        if ($this->canManage($course, $session, $group)) {
            return true;
        }

        if ($session instanceof Session || 0 === $addLock) {
            return false;
        }

        $user = $this->userHelper->getCurrent();
        if (!$user instanceof User) {
            return false;
        }

        if ('index' === $reflink && !($group instanceof CGroup)) {
            return false;
        }

        if ($group instanceof CGroup) {
            return $group->hasMember($user);
        }

        return $course->hasSubscriptionByUser($user)
            || $this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT');
    }

    public function canEditPage(Course $course, ?Session $session, ?CGroup $group, CWiki $wiki): bool
    {
        if ($this->canManage($course, $session, $group)) {
            return true;
        }

        if ($session instanceof Session || 1 === $wiki->getEditlock()) {
            return false;
        }

        $user = $this->userHelper->getCurrent();
        if (!$user instanceof User) {
            return false;
        }

        if (1 === $wiki->getAssignment()) {
            return false;
        }

        if (2 === $wiki->getAssignment()) {
            return $wiki->getUserId() === (int) $user->getId();
        }

        if ('index' === $wiki->getReflink() && !($group instanceof CGroup)) {
            return false;
        }

        if ($group instanceof CGroup) {
            return $group->hasMember($user);
        }

        return $course->hasSubscriptionByUser($user)
            || $this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT');
    }

    public function isCourseSettingEnabled(Course $course, string $variable, bool $default): bool
    {
        $settings = $this->entityManager->getRepository(CCourseSetting::class)->findBy(
            [
                'cId' => (int) $course->getId(),
                'variable' => $variable,
            ],
            ['iid' => 'ASC'],
        );

        $legacyValue = null;

        foreach ($settings as $setting) {
            if (!$setting instanceof CCourseSetting) {
                continue;
            }

            $category = trim((string) $setting->getCategory());
            $value = $setting->getValue();

            if ('wiki' === $category) {
                return $this->resolveBoolean($value, $default);
            }

            if ('' === $category && null === $legacyValue) {
                $legacyValue = $value;
            }
        }

        if (null === $legacyValue || '' === trim((string) $legacyValue)) {
            return $default;
        }

        return $this->resolveBoolean($legacyValue, $default);
    }

    public function isCourseLockedInsideSessions(Course $course): bool
    {
        return $this->isAllowedToEditHelper->isCourseLockedInsideSessions($course);
    }

    /**
     * Reads a platform setting the way the wiki always has: the four truthy literals, and an
     * explicit default for a setting that has no row yet.
     */
    public function isPlatformSettingEnabled(string $name, bool $default = true): bool
    {
        return $this->resolveBoolean($this->settingsManager->getSetting($name, true), $default);
    }

    private function isSettingEnabled(string $name): bool
    {
        return $this->isPlatformSettingEnabled($name);
    }

    private function resolveBoolean(mixed $value, bool $default = true): bool
    {
        if (null === $value || '' === trim((string) $value)) {
            return $default;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
