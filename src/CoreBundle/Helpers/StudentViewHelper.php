<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\EventListener\LegacyListener;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Reads the "student view" toggle, which teachers use to preview a course as a learner would see it.
 *
 * The session is the normalized source: LegacyListener (kernel.request, priority 7) turns the
 * incoming isStudentView query parameter into the session value, and only does so when the
 * course.student_view_enabled setting is on. Reading the query parameter here instead would
 * bypass that setting, so consumers must always go through this helper.
 *
 * @see LegacyListener::onKernelRequest()
 */
readonly class StudentViewHelper
{
    private const string SESSION_KEY = 'studentview';
    private const string ACTIVE = 'studentview';

    public function __construct(
        private RequestStack $requestStack,
    ) {}

    public function isStudentView(): bool
    {
        return self::ACTIVE === $this->getRawValue();
    }

    /**
     * Same as isStudentView(), but honouring the per-course override that the legacy course
     * progress page writes (public/main/course_progress/index.php). When that key is absent the
     * platform-wide toggle applies.
     */
    public function isStudentViewForCourse(int $courseId): bool
    {
        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return false;
        }

        $perCourse = $session->get('student_view_course_'.$courseId);

        return null !== $perCourse ? (bool) $perCourse : $this->isStudentView();
    }

    /**
     * Whether the current user may act as a teacher right now: the capability must be granted
     * and the student view preview must be off.
     */
    public function canManageInCurrentView(bool $isAllowedToEdit): bool
    {
        return $isAllowedToEdit && !$this->isStudentView();
    }

    private function getRawValue(): ?string
    {
        try {
            $value = $this->requestStack->getSession()->get(self::SESSION_KEY);
        } catch (SessionNotFoundException) {
            return null;
        }

        return \is_string($value) ? $value : null;
    }
}
