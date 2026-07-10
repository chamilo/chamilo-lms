<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use const PHP_INT_MAX;

trait AnnouncementAccessHelperTrait
{
    private function assertAnnouncementToolEnabled(EntityManagerInterface $entityManager, Course $course): void
    {
        if ($this->isAnnouncementToolEnabled($entityManager, $course)) {
            return;
        }

        throw new AccessDeniedHttpException('The Announcements tool is disabled for this course.');
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

    private function canReadAnnouncementContext(
        Security $security,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $security->getUser();
        if (!$user instanceof User) {
            return null === $session
                && null === $group
                && $course->isPublic()
                && '' === trim((string) $course->getRegistrationCode());
        }

        $isCourseTeacher = $course->hasUserAsTeacher($user)
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if ($session instanceof Session) {
            $canReadCourse = $isCourseTeacher
                || $session->hasUserAsGeneralCoach($user)
                || $session->hasCourseCoachInCourse($user, $course)
                || $session->hasUserInCourse($user, $course)
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                || ($security->isGranted('ROLE_HR') && $this->isSettingEnabled(
                    $settingsManager->getSetting('session.drh_can_access_all_session_content', true),
                ))
                || ($user->isSessionAdmin() && $this->isSettingEnabled(
                    $settingsManager->getSetting('session.session_admins_access_all_content', true),
                ));
        } else {
            $canReadCourse = $isCourseTeacher
                || $security->isGranted(CourseVoter::VIEW, $course)
                || $course->hasSubscriptionByUser($user)
                || $course->isPublic();
        }

        if (!$canReadCourse) {
            return false;
        }

        if (!$group instanceof CGroup) {
            return true;
        }

        if ($course->hasUserAsTeacher($user)
            || $group->hasTutor($user)
            || $security->isGranted('ROLE_CURRENT_COURSE_GROUP_TEACHER')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        ) {
            return true;
        }

        return match ($group->getAnnouncementsState()) {
            CGroup::TOOL_NOT_AVAILABLE => false,
            CGroup::TOOL_PUBLIC => true,
            CGroup::TOOL_PRIVATE,
            CGroup::TOOL_PRIVATE_BETWEEN_USERS => $group->hasMember($user),
            default => false,
        };
    }

    private function canManageAnnouncements(
        EntityManagerInterface $entityManager,
        Security $security,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($session instanceof Session && (
            Session::READ_ONLY === $session->getVisibility()
            || $this->isCourseLockedInsideSessions($entityManager, $settingsManager, $course)
        )) {
            return false;
        }

        if ($course->hasUserAsTeacher($user)) {
            return true;
        }

        if ($group instanceof CGroup) {
            if ($group->hasTutor($user) || $security->isGranted('ROLE_CURRENT_COURSE_GROUP_TEACHER')) {
                return true;
            }

            if (CGroup::TOOL_PRIVATE_BETWEEN_USERS === $group->getAnnouncementsState()
                && $group->hasMember($user)
            ) {
                return true;
            }
        }

        if ($this->isCourseAnnouncementSettingEnabled(
            $entityManager,
            $course,
            'allow_user_edit_announcement',
            false,
        ) && $this->canReadAnnouncementContext($security, $settingsManager, $course, $session, $group)
        ) {
            return true;
        }

        if ($session instanceof Session) {
            if ($user->isSessionAdmin() && $this->isSettingEnabled(
                $settingsManager->getSetting('session.session_admins_edit_courses_content', true),
            )) {
                return true;
            }

            if ($security->isGranted('ROLE_HR') && $this->isSettingEnabled(
                $settingsManager->getSetting('session.drh_can_access_all_session_content', true),
            )) {
                return true;
            }

            if ($this->isSettingEnabled(
                $settingsManager->getSetting('announcement.allow_coach_to_edit_announcements', true),
            ) && (
                $session->hasUserAsGeneralCoach($user)
                || $session->hasCourseCoachInCourse($user, $course)
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            )) {
                return true;
            }
        }

        return false;
    }

    private function canDeleteAllAnnouncements(
        Security $security,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): bool {
        if ($group instanceof CGroup) {
            return false;
        }

        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($course->hasUserAsTeacher($user)) {
            return true;
        }

        if (!$session instanceof Session) {
            return false;
        }

        if ($user->isSessionAdmin() && $this->isSettingEnabled(
            $settingsManager->getSetting('session.session_admins_edit_courses_content', true),
        )) {
            return true;
        }

        if ($security->isGranted('ROLE_HR') && $this->isSettingEnabled(
            $settingsManager->getSetting('session.drh_can_access_all_session_content', true),
        )) {
            return true;
        }

        return $this->isSettingEnabled(
            $settingsManager->getSetting('announcement.allow_coach_to_edit_announcements', true),
        ) && (
            $session->hasUserAsGeneralCoach($user)
            || $session->hasCourseCoachInCourse($user, $course)
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        );
    }

    private function canEditAnnouncement(
        EntityManagerInterface $entityManager,
        Security $security,
        SettingsManager $settingsManager,
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): bool {
        if (!$this->canManageAnnouncements(
            $entityManager,
            $security,
            $settingsManager,
            $course,
            $session,
            $group,
        )) {
            return false;
        }

        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $resourceNode = $announcement->getResourceNode();
        if (null !== $resourceNode && $security->isGranted('EDIT', $resourceNode)) {
            return true;
        }

        $user = $security->getUser();
        $creator = $resourceNode?->getCreator();

        return $user instanceof User
            && $creator instanceof User
            && $creator->getId() === $user->getId();
    }

    /**
     * @return array<int, ResourceLink>
     */
    private function getAnnouncementContextLinks(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $resourceNode = $announcement->getResourceNode();
        if (null === $resourceNode) {
            return [];
        }

        $matches = [];
        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink || null !== $link->getDeletedAt()) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $linkGroup = $link->getGroup();

            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();
            $sameGroup = null === $group
                || (null !== $linkGroup && $linkGroup->getIid() === $group->getIid());

            if ($sameCourse && $sameSession && $sameGroup) {
                $matches[] = $link;
            }
        }

        return $matches;
    }

    private function hasMultipleAnnouncementGroupTargets(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
    ): bool {
        $groupIds = [];
        foreach ($this->getAnnouncementContextLinks($announcement, $course, $session, null) as $link) {
            $linkedGroup = $link->getGroup();
            if (!$linkedGroup instanceof CGroup || null === $linkedGroup->getIid()) {
                continue;
            }

            $groupIds[(int) $linkedGroup->getIid()] = true;
        }

        return \count($groupIds) > 1;
    }

    /**
     * @param array<int, ResourceLink> $contextLinks
     */
    private function canReadAnnouncement(
        CAnnouncement $announcement,
        array $contextLinks,
        Security $security,
        bool $canManage,
        bool $studentView,
    ): bool {
        if ([] === $contextLinks) {
            return false;
        }

        if ($canManage && !$studentView) {
            return true;
        }

        $user = $security->getUser();
        $creator = $announcement->getResourceNode()?->getCreator();

        foreach ($contextLinks as $link) {
            if (!$link->isPublished()) {
                continue;
            }

            $linkedUser = $link->getUser();
            $linkedGroup = $link->getGroup();

            if ($linkedUser instanceof User) {
                if ($user instanceof User && $linkedUser->getId() === $user->getId()) {
                    return true;
                }

                continue;
            }

            if ($linkedGroup instanceof CGroup) {
                if ($user instanceof User && (
                    $linkedGroup->hasMember($user)
                    || $linkedGroup->hasTutor($user)
                )) {
                    return true;
                }

                continue;
            }

            return true;
        }

        if (!$user instanceof User
            || !$creator instanceof User
            || $creator->getId() !== $user->getId()
        ) {
            return false;
        }

        foreach ($contextLinks as $link) {
            if ($link->isPublished()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, ResourceLink> $contextLinks
     */
    private function getAnnouncementDisplayOrder(array $contextLinks): int
    {
        if ([] === $contextLinks) {
            return PHP_INT_MAX;
        }

        return min(array_map(
            static fn (ResourceLink $link): int => $link->getDisplayOrder(),
            $contextLinks,
        ));
    }

    /**
     * @param array<int, ResourceLink> $contextLinks
     */
    private function getAnnouncementVisibility(array $contextLinks): int
    {
        foreach ($contextLinks as $link) {
            if ($link->isPublished()) {
                return ResourceLink::VISIBILITY_PUBLISHED;
            }
        }

        return $contextLinks[0]->getVisibility() ?? ResourceLink::VISIBILITY_DRAFT;
    }

    private function isCourseLockedInsideSessions(
        EntityManagerInterface $entityManager,
        SettingsManager $settingsManager,
        Course $course,
    ): bool {
        if (!$this->isSettingEnabled(
            $settingsManager->getSetting('session.session_courses_read_only_mode', true),
        )) {
            return false;
        }

        $repository = $entityManager->getRepository(ExtraFieldValues::class);
        if (!$repository instanceof ExtraFieldValuesRepository) {
            return false;
        }

        $extraFieldValue = $repository->getValueByVariableAndItem(
            'session_courses_read_only_mode',
            (int) $course->getId(),
            ExtraField::COURSE_FIELD_TYPE,
        );

        return $extraFieldValue instanceof ExtraFieldValues && !empty($extraFieldValue->getFieldValue());
    }

    private function isAnnouncementToolEnabled(EntityManagerInterface $entityManager, Course $course): bool
    {
        return $this->isCourseAnnouncementSettingEnabled($entityManager, $course, 'enabled', true);
    }

    private function isCourseAnnouncementSettingEnabled(
        EntityManagerInterface $entityManager,
        Course $course,
        string $variable,
        bool $default,
    ): bool {
        $settings = $entityManager->getRepository(CCourseSetting::class)->findBy(
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

            if ('announcement' === $category) {
                return $this->isSettingEnabled($value);
            }

            if ('' === $category && null === $legacyValue) {
                $legacyValue = $value;
            }
        }

        if (null === $legacyValue || '' === trim((string) $legacyValue)) {
            return $default;
        }

        return $this->isSettingEnabled($legacyValue);
    }

    private function isSettingEnabled(mixed $value): bool
    {
        return true === $value
            || \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function isStudentView(Request $request): bool
    {
        if ($request->query->has('isStudentView')) {
            return $request->query->getBoolean('isStudentView');
        }

        if (!$request->hasSession()) {
            return false;
        }

        return 'studentview' === $request->getSession()->get('studentview');
    }
}
