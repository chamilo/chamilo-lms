<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseDescription;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait CourseDescriptionAccessHelperTrait
{
    private function assertCourseDescriptionToolEnabled(EntityManagerInterface $entityManager, Course $course): void
    {
        if ($this->isCourseDescriptionToolEnabled($entityManager, $course)) {
            return;
        }

        throw new AccessDeniedHttpException('The Course Description tool is disabled for this course.');
    }

    private function isCourseDescriptionToolEnabled(EntityManagerInterface $entityManager, Course $course): bool
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

            if ('course_description' === $category) {
                return $this->resolveCourseDescriptionEnabledValue($value);
            }

            if ('' === $category && null === $legacyValue) {
                $legacyValue = $value;
            }
        }

        if (null === $legacyValue || '' === trim((string) $legacyValue)) {
            return true;
        }

        return $this->resolveCourseDescriptionEnabledValue($legacyValue);
    }

    private function canManageCourseDescriptions(
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

        if (!$this->resolveCourseDescriptionEnabledValue(
            $settingsManager->getSetting('session.allow_coach_to_edit_course_session', true),
        )) {
            return false;
        }

        return $isCourseTeacher
            || $session->hasUserAsGeneralCoach($user)
            || $session->hasCourseCoachInCourse($user, $course)
            || $this->hasDirectSessionCourseCoachRelation($entityManager, $user, $course, $session)
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
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

    private function resolveCourseDescriptionEnabledValue(mixed $value): bool
    {
        if (null === $value || '' === trim((string) $value)) {
            return true;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
