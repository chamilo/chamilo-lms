<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Toolbox;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait ToolboxAccessHelperTrait
{
    private function isToolboxEnabled(SettingsManager $settingsManager): bool
    {
        $master = strtolower(trim((string) $settingsManager->getSetting('ai_helpers.enable_ai_helpers', true)));
        $feature = strtolower(trim((string) $settingsManager->getSetting('ai_helpers.toolbox', true)));

        return \in_array($master, ['1', 'true', 'yes', 'on'], true)
            && \in_array($feature, ['1', 'true', 'yes', 'on'], true);
    }

    private function canManageToolbox(Security $security, StudentViewHelper $studentViewHelper): bool
    {
        if ($studentViewHelper->isActive()) {
            return false;
        }

        return $security->isGranted('ROLE_ADMIN')
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function assertToolboxTeacher(Security $security): void
    {
        if ($security->isGranted('ROLE_ADMIN')
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        ) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage Toolbox items in this context.');
    }

    private function canWriteToolboxContext(
        Security $security,
        StudentViewHelper $studentViewHelper,
        ?Session $session,
    ): bool {
        if ($studentViewHelper->isActive()) {
            return false;
        }

        if ($session instanceof Session && Session::READ_ONLY === $session->getVisibility()) {
            return false;
        }

        return $security->isGranted('ROLE_ADMIN')
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function assertToolboxSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session does not contain the current course.');
    }

    private function assertToolboxGroupBelongsToContext(?CGroup $group, Course $course, ?Session $session): void
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

    private function sanitizeToolboxTitle(string $title): string
    {
        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new BadRequestHttpException('Title is required.');
        }

        if (mb_strlen($title) > 255) {
            throw new BadRequestHttpException('Title is too long.');
        }

        return $title;
    }

    private function sanitizeToolboxDescription(string $description): string
    {
        $description = trim(strip_tags($description));
        if (mb_strlen($description) > 10000) {
            throw new BadRequestHttpException('Description is too long.');
        }

        return $description;
    }
}
