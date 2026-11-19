<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Component\Exercise\FinalExamAccessRule;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Evaluates the access conditions of a test (FinalExamAccessRule) for the API, counting the time
 * spent in the course like the time spent clock does, so that both show the same time.
 */
final readonly class FinalExamAccessHelper
{
    public function __construct(
        private CourseTimeSpentHelper $courseTimeSpentHelper,
        private RequestStack $requestStack,
    ) {}

    /**
     * @return array{
     *     applies: bool,
     *     allowed: bool,
     *     time_requirement_met: bool,
     *     required_minutes: int,
     *     spent_minutes: int,
     *     remaining_minutes: int,
     *     user_identifier_present: bool,
     *     user_identifier_label: string,
     *     allow_user_identifier_opt_out: bool,
     *     user_identifier_opt_out_prompt: string
     * }
     */
    public function evaluate(CQuiz $quiz, User $user, Course $course, ?Session $session): array
    {
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);

        return FinalExamAccessRule::evaluate(
            (int) $user->getId(),
            $courseId,
            $sessionId,
            (int) $quiz->getIid(),
            function () use ($user, $courseId, $sessionId): int {
                $request = $this->requestStack->getCurrentRequest();
                $httpSession = null !== $request && $request->hasSession() ? $request->getSession() : null;

                return $this->courseTimeSpentHelper->getTimeSpentSeconds($user, $courseId, $sessionId, $httpSession);
            },
        );
    }
}
