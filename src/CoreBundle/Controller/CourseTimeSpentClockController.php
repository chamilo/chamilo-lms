<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseAccessActivityHelper;
use Chamilo\CoreBundle\Helpers\CourseTimeSpentClockHelper;
use Chamilo\CoreBundle\Helpers\CourseTimeSpentHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The time a learner spent in the current course, for the clock shown on every course page.
 * It reads the native course tracking (track_e_course_access), so the clock shows what the
 * reporting shows. The course comes from the cid/sid query parameters, which CidReqListener
 * resolves and checks the access to.
 */
#[IsGranted('ROLE_USER')]
final class CourseTimeSpentClockController extends AbstractController
{
    public const string ROUTE = 'chamilo_core_course_time_spent_clock';

    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly UserHelper $userHelper,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
        private readonly CourseTimeSpentClockHelper $courseTimeSpentClockHelper,
        private readonly CourseAccessActivityHelper $courseAccessActivityHelper,
        private readonly CourseTimeSpentHelper $courseTimeSpentHelper,
    ) {}

    #[Route('/course/time-spent-clock-data', name: self::ROUTE, methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $disabled = new JsonResponse(['enabled' => false]);

        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        $user = $this->userHelper->getCurrent();
        if (null === $course || !$user instanceof User || (int) $user->getId() <= 0) {
            return $disabled;
        }

        // Learners only: teachers, tutors, coaches and administrators never see the clock, even
        // in the student view.
        if ($this->isAllowedToEditHelper->check(tutor: true, coach: true, sessionCoach: true, checkStudentView: false)) {
            return $disabled;
        }

        if (!$this->courseTimeSpentClockHelper->isEnabled($course)) {
            return $disabled;
        }

        $courseId = (int) $course->getId();
        $sessionId = (int) ($this->cidReqHelper->getDoctrineSessionEntity()?->getId() ?? 0);
        $httpSession = $request->hasSession() ? $request->getSession() : null;

        return new JsonResponse([
            'enabled' => true,
            'seconds' => $this->courseTimeSpentHelper->getTimeSpentSeconds($user, $courseId, $sessionId, $httpSession),
            // Past the allowed inactivity the tracking stops counting, and so does the clock.
            'counting' => !$this->courseAccessActivityHelper->isInactive($httpSession, $courseId, $sessionId),
        ]);
    }
}
