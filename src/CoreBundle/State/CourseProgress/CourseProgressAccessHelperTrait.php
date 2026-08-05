<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CThematic;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait CourseProgressAccessHelperTrait
{
    private function getCourseProgressCourse(Request $request, EntityManagerInterface $entityManager): Course
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

    private function getCourseProgressSession(Request $request, EntityManagerInterface $entityManager): ?Session
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

    private function assertCourseProgressToolEnabled(EntityManagerInterface $entityManager, Course $course): void
    {
        if ($this->isCourseProgressToolEnabled($entityManager, $course)) {
            return;
        }

        throw new AccessDeniedHttpException('The Course Progress tool is disabled for this course.');
    }

    private function assertSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session does not contain the current course.');
    }

    private function isCourseProgressToolEnabled(EntityManagerInterface $entityManager, Course $course): bool
    {
        $settings = $entityManager->getRepository(CCourseSetting::class)->findBy(
            [
                'cId' => (int) $course->getId(),
                'variable' => 'enabled',
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

            if ('course_progress' === $category) {
                return $this->resolveCourseProgressEnabledValue($value);
            }

            if ('' === $category && null === $legacyValue) {
                $legacyValue = $value;
            }
        }

        if (null === $legacyValue || '' === trim((string) $legacyValue)) {
            return true;
        }

        return $this->resolveCourseProgressEnabledValue($legacyValue);
    }

    private function canManageCourseProgress(
        EntityManagerInterface $entityManager,
        Security $security,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $isCourseTeacher = $course->hasUserAsTeacher($user)
            || $this->hasDirectCourseTeacherRelation($entityManager, $user, $course);

        if (!$session instanceof Session) {
            return $isCourseTeacher;
        }

        if ($this->isSessionAdminEditingAllowed($user, $settingsManager)) {
            return true;
        }

        if (Session::READ_ONLY === $session->getVisibility()
            || $this->isCourseLockedInsideSessions($entityManager, $settingsManager, $course)
        ) {
            return false;
        }

        $studentViewEnabled = $this->resolveCourseProgressEnabledValue(
            $settingsManager->getSetting('course.student_view_enabled', true),
        );

        if (!$studentViewEnabled && $isCourseTeacher) {
            return true;
        }

        if (!$this->resolveCourseProgressEnabledValue(
            $settingsManager->getSetting('session.allow_coach_to_edit_course_session', true),
        )) {
            return false;
        }

        return $session->hasUserAsGeneralCoach($user)
            || $session->hasCourseCoachInCourse($user, $course)
            || $this->hasDirectSessionCourseCoachRelation($entityManager, $user, $course, $session)
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function canReadCourseProgress(
        Security $security,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $security->getUser();

        if (!$user instanceof User) {
            return null === $session
                && $course->isPublic()
                && '' === trim((string) $course->getRegistrationCode());
        }

        if ($security->isGranted('ROLE_HR') || $security->isGranted(CourseVoter::VIEW, $course)) {
            return true;
        }

        return $session instanceof Session && $this->isSessionAdminReadingAllowed($user, $settingsManager);
    }

    private function isCourseProgressStudentView(Request $request, int $courseId): bool
    {
        if ($request->query->has('isStudentView') && $request->query->getBoolean('isStudentView')) {
            return true;
        }

        if (!$request->hasSession()) {
            return false;
        }

        $session = $request->getSession();
        $legacyCourseStudentView = $session->get('student_view_course_'.$courseId);

        if (null !== $legacyCourseStudentView) {
            return (bool) $legacyCourseStudentView;
        }

        return 'studentview' === $session->get('studentview');
    }

    private function thematicBelongsToExactContext(
        CThematic $thematic,
        Course $course,
        ?Session $session,
    ): bool {
        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();

            if ($sameCourse && $sameSession) {
                return true;
            }
        }

        return false;
    }

    private function isSessionAdminReadingAllowed(User $user, SettingsManager $settingsManager): bool
    {
        return $user->isSessionAdmin()
            && $this->resolveCourseProgressEnabledValue(
                $settingsManager->getSetting('session.session_admins_access_all_content', true),
            );
    }

    private function isSessionAdminEditingAllowed(User $user, SettingsManager $settingsManager): bool
    {
        return $user->isSessionAdmin()
            && $this->resolveCourseProgressEnabledValue(
                $settingsManager->getSetting('session.session_admins_edit_courses_content', true),
            );
    }

    private function isCourseLockedInsideSessions(
        EntityManagerInterface $entityManager,
        SettingsManager $settingsManager,
        Course $course,
    ): bool {
        if (!$this->resolveCourseProgressEnabledValue(
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

    private function hasDirectCourseTeacherRelation(
        EntityManagerInterface $entityManager,
        User $user,
        Course $course,
    ): bool {
        $relation = $entityManager->getRepository(CourseRelUser::class)->findOneBy([
            'user' => $user,
            'course' => $course,
            'status' => CourseRelUser::TEACHER,
        ]);

        return $relation instanceof CourseRelUser;
    }

    private function hasDirectSessionCourseCoachRelation(
        EntityManagerInterface $entityManager,
        User $user,
        Course $course,
        Session $session,
    ): bool {
        $relation = $entityManager->getRepository(SessionRelCourseRelUser::class)->findOneBy([
            'user' => $user,
            'course' => $course,
            'session' => $session,
            'status' => Session::COURSE_COACH,
        ]);

        return $relation instanceof SessionRelCourseRelUser;
    }

    private function resolveCourseProgressEnabledValue(mixed $value): bool
    {
        if (null === $value || '' === trim((string) $value)) {
            return true;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
