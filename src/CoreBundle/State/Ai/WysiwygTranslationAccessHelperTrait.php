<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Ai;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait WysiwygTranslationAccessHelperTrait
{
    private function resolveCourseAndAssertAccess(Security $security): ?Course
    {
        $courseId = (int) ($this->cidReqHelper->getCourseId() ?? 0);
        if ($courseId <= 0) {
            if (!$security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException('Only administrators may use AI WYSIWYG translation outside a course.');
            }

            return null;
        }

        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('The course was not found.');
        }
        if (!$security->isGranted(CourseVoter::EDIT, $course)
            && !$security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        ) {
            throw new AccessDeniedHttpException('You are not allowed to translate content in this course.');
        }

        return $course;
    }
}
