<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CWiki;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait WikiAccessHelperTrait
{
    private function getWikiCourse(EntityManagerInterface $entityManager, Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getWikiSession(EntityManagerInterface $entityManager, Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function getWikiGroup(EntityManagerInterface $entityManager, Request $request): ?CGroup
    {
        $groupId = $request->query->getInt('gid');
        if ($groupId <= 0) {
            return null;
        }

        $group = $entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            throw new BadRequestHttpException('The requested group was not found.');
        }

        return $group;
    }

    private function assertWikiToolEnabled(EntityManagerInterface $entityManager, Course $course): void
    {
        if ($this->isWikiToolEnabled($entityManager, $course)) {
            return;
        }

        throw new AccessDeniedHttpException('The Wiki tool is disabled for this course.');
    }

    private function assertWikiSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session does not contain the current course.');
    }

    private function assertWikiGroupBelongsToContext(?CGroup $group, Course $course, ?Session $session): void
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

    private function canReadWikiContext(
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

        $isCourseTeacher = $course->hasUserAsTeacher($user)
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if ($session instanceof Session) {
            $canReadCourse = $isCourseTeacher
                || $session->hasUserAsGeneralCoach($user)
                || $session->hasCourseCoachInCourse($user, $course)
                || $session->hasUserInCourse($user, $course)
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                || ($security->isGranted('ROLE_HR') && $this->resolveWikiBoolean(
                    $settingsManager->getSetting('session.drh_can_access_all_session_content', true),
                ))
                || ($user->isSessionAdmin() && $this->resolveWikiBoolean(
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

        if ($isCourseTeacher
            || $group->hasTutor($user)
            || $security->isGranted('ROLE_CURRENT_COURSE_GROUP_TEACHER')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
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

    private function canManageWikiContext(
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
            || $this->isWikiCourseLockedInsideSessions($entityManager, $settingsManager, $course)
        )) {
            return false;
        }

        if ($course->hasUserAsTeacher($user) || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if ($group instanceof CGroup && (
            $group->hasTutor($user)
            || $security->isGranted('ROLE_CURRENT_COURSE_GROUP_TEACHER')
        )) {
            return true;
        }

        if (!$session instanceof Session) {
            return false;
        }

        if ($user->isSessionAdmin() && $this->resolveWikiBoolean(
            $settingsManager->getSetting('session.session_admins_edit_courses_content', true),
        )) {
            return true;
        }

        if ($security->isGranted('ROLE_HR') && $this->resolveWikiBoolean(
            $settingsManager->getSetting('session.drh_can_access_all_session_content', true),
        )) {
            return true;
        }

        if (!$this->resolveWikiBoolean(
            $settingsManager->getSetting('session.allow_coach_to_edit_course_session', true),
        )) {
            return false;
        }

        return $session->hasUserAsGeneralCoach($user)
            || $session->hasCourseCoachInCourse($user, $course)
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function canCreateWikiPage(
        EntityManagerInterface $entityManager,
        Security $security,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        string $reflink,
        int $addLock,
    ): bool {
        if ($this->canManageWikiContext($entityManager, $security, $settingsManager, $course, $session, $group)) {
            return true;
        }

        if ($session instanceof Session || 0 === $addLock) {
            return false;
        }

        $user = $security->getUser();
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
            || $security->isGranted('ROLE_CURRENT_COURSE_STUDENT');
    }

    private function canEditWikiPage(
        EntityManagerInterface $entityManager,
        Security $security,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        CWiki $wiki,
    ): bool {
        if ($this->canManageWikiContext($entityManager, $security, $settingsManager, $course, $session, $group)) {
            return true;
        }

        if ($session instanceof Session || 1 === $wiki->getEditlock()) {
            return false;
        }

        $user = $security->getUser();
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
            || $security->isGranted('ROLE_CURRENT_COURSE_STUDENT');
    }

    private function assertWikiPageVisible(Security $security, CWiki $wiki, bool $canManage): void
    {
        if (1 === $wiki->getVisibility() || $canManage) {
            return;
        }

        $user = $security->getUser();
        if (2 === $wiki->getAssignment()
            && 0 === $wiki->getVisibility()
            && $user instanceof User
            && $wiki->getUserId() === (int) $user->getId()
        ) {
            return;
        }

        throw new AccessDeniedHttpException('This Wiki page is not visible in the current context.');
    }

    private function assertWikiRouteNode(Course $course, Request $request): int
    {
        $nodeId = $request->query->getInt('node');
        $courseNodeId = $course->getResourceNode()?->getId();

        if ($nodeId <= 0 || null === $courseNodeId || $nodeId !== (int) $courseNodeId) {
            throw new AccessDeniedHttpException('The Wiki route does not belong to the current course.');
        }

        return $nodeId;
    }

    private function isWikiStudentView(Request $request): bool
    {
        if ($request->query->has('isStudentView')) {
            return $request->query->getBoolean('isStudentView');
        }

        if (!$request->hasSession()) {
            return false;
        }

        return 'studentview' === $request->getSession()->get('studentview');
    }

    private function isWikiCourseSettingEnabled(
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

            if ('wiki' === $category) {
                return $this->resolveWikiBoolean($value, $default);
            }

            if ('' === $category && null === $legacyValue) {
                $legacyValue = $value;
            }
        }

        if (null === $legacyValue || '' === trim((string) $legacyValue)) {
            return $default;
        }

        return $this->resolveWikiBoolean($legacyValue, $default);
    }

    private function isWikiToolEnabled(EntityManagerInterface $entityManager, Course $course): bool
    {
        return $this->isWikiCourseSettingEnabled($entityManager, $course, 'enabled', true);
    }

    private function isWikiCourseLockedInsideSessions(
        EntityManagerInterface $entityManager,
        SettingsManager $settingsManager,
        Course $course,
    ): bool {
        if (!$this->resolveWikiBoolean(
            $settingsManager->getSetting('session.session_courses_read_only_mode', true),
            false,
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

    private function resolveWikiBoolean(mixed $value, bool $default = true): bool
    {
        if (null === $value || '' === trim((string) $value)) {
            return $default;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
