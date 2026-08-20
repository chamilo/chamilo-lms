<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Notebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Repository\CNotebookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Event;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait NotebookAccessHelperTrait
{
    private function assertNotebookSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session does not contain the current course.');
    }

    private function getNotebookUser(UserHelper $userHelper): User
    {
        $user = $userHelper->getCurrent();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        return $user;
    }

    private function canReadNotebook(
        Security $security,
        UserHelper $userHelper,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $userHelper->getCurrent();
        if (!$user instanceof User) {
            return false;
        }

        if ($security->isGranted('ROLE_HR')) {
            return true;
        }

        if ($user->isSessionAdmin()) {
            return $this->resolveNotebookBoolean(
                $settingsManager->getSetting('session.session_admins_access_all_content', true),
            );
        }

        $isCourseTeacher = $course->hasUserAsTeacher($user)
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if ($session instanceof Session) {
            return $isCourseTeacher
                || $session->hasUserAsGeneralCoach($user)
                || $session->hasCourseCoachInCourse($user, $course)
                || $session->hasUserInCourse($user, $course)
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
        }

        return $isCourseTeacher
            || $security->isGranted(CourseVoter::VIEW, $course)
            || $security->isGranted('ROLE_CURRENT_COURSE_STUDENT');
    }

    private function canWriteNotebook(
        EntityManagerInterface $entityManager,
        Security $security,
        UserHelper $userHelper,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
        bool $studentView,
    ): bool {
        if ($studentView) {
            return false;
        }

        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $userHelper->getCurrent();
        if (!$user instanceof User
            || !$this->canReadNotebook($security, $userHelper, $settingsManager, $course, $session)
        ) {
            return false;
        }

        if ($user->isSessionAdmin()
            && $this->resolveNotebookBoolean(
                $settingsManager->getSetting('session.session_admins_edit_courses_content', true),
            )
        ) {
            return true;
        }

        if ($session instanceof Session
            && $this->isNotebookCourseLockedInsideSessions($entityManager, $settingsManager, $course)
        ) {
            return false;
        }

        $isCourseTeacher = $course->hasUserAsTeacher($user)
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER');

        if (!$session instanceof Session) {
            return true;
        }

        if (Session::READ_ONLY === $session->getVisibility()) {
            return false;
        }

        if ($isCourseTeacher) {
            return true;
        }

        $isSessionCoach = $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || $session->hasUserAsGeneralCoach($user)
            || $session->hasCourseCoachInCourse($user, $course);

        if ($isSessionCoach) {
            return Session::READ_ONLY !== $session->getVisibility()
                && $this->resolveNotebookBoolean(
                    $settingsManager->getSetting('session.allow_coach_to_edit_course_session', true),
                );
        }

        if (!$security->isGranted('ROLE_HR')
            && !$security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
        ) {
            return false;
        }

        $visibility = $session->setAccessVisibilityByUser($user);

        return \in_array($visibility, [Session::VISIBLE, Session::AVAILABLE], true);
    }

    private function canUseFullNotebookEditor(
        EntityManagerInterface $entityManager,
        Security $security,
        UserHelper $userHelper,
        SettingsManager $settingsManager,
        Course $course,
        ?Session $session,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $userHelper->getCurrent();
        if (!$user instanceof User) {
            return false;
        }

        if ($user->isSessionAdmin()
            && $this->resolveNotebookBoolean(
                $settingsManager->getSetting('session.session_admins_edit_courses_content', true),
            )
        ) {
            return true;
        }

        if ($course->hasUserAsTeacher($user) || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$session instanceof Session
            || $this->isNotebookCourseLockedInsideSessions($entityManager, $settingsManager, $course)
            || Session::READ_ONLY === $session->getVisibility()
        ) {
            return false;
        }

        $isSessionCoach = $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || $session->hasUserAsGeneralCoach($user)
            || $session->hasCourseCoachInCourse($user, $course);

        return $isSessionCoach && $this->resolveNotebookBoolean(
            $settingsManager->getSetting('session.allow_coach_to_edit_course_session', true),
        );
    }

    private function isNotebookCourseLockedInsideSessions(
        EntityManagerInterface $entityManager,
        SettingsManager $settingsManager,
        Course $course,
    ): bool {
        if (!$this->resolveNotebookBoolean(
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

    private function resolveNotebookBoolean(mixed $value): bool
    {
        if (null === $value || '' === trim((string) $value)) {
            return false;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function findOwnedNotebookInContext(
        CNotebookRepository $notebookRepository,
        User $user,
        Course $course,
        ?Session $session,
        int $noteId,
    ): CNotebook {
        if ($noteId <= 0) {
            throw new BadRequestHttpException('A valid notebook id is required.');
        }

        $notes = $notebookRepository->findByUser($user, $course, $session);
        foreach ($notes as $note) {
            if ($note instanceof CNotebook && $note->getIid() === $noteId) {
                return $note;
            }
        }

        throw new NotFoundHttpException('The requested note was not found.');
    }

    private function registerNotebookToolAccess(): void
    {
        if (!class_exists(Event::class) || !\defined('TOOL_NOTEBOOK')) {
            return;
        }

        try {
            Event::event_access_tool((string) \constant('TOOL_NOTEBOOK'));
        } catch (Throwable) {
            // Tracking must never break Notebook rendering.
        }
    }

    private function registerNotebookAction(
        string $action,
        Course $course,
        ?Session $session = null,
        int $noteId = 0,
    ): void {
        if (!class_exists(Event::class)) {
            return;
        }

        $logInfo = [
            'tool' => \defined('TOOL_NOTEBOOK') ? \constant('TOOL_NOTEBOOK') : 'notebook',
            'tool_id' => max(0, $noteId),
            'tool_id_detail' => 0,
            'action' => mb_substr(trim($action), 0, 255),
            'action_details' => '',
            'c_id' => (int) $course->getId(),
            'session_id' => (int) ($session?->getId() ?? 0),
        ];

        try {
            Event::registerLog($logInfo);
        } catch (Throwable) {
            // Tracking must never break Notebook actions.
        }
    }
}
