<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpItemViewRepository;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Whether an unpublished exercise is still reachable because the request comes from a learning
 * path that legitimately contains it.
 *
 * An exercise hidden in the course tool stays playable inside a lesson, so every runtime
 * endpoint has to answer this before rejecting an unpublished quiz. The check was copied
 * verbatim into all seven of them; this is the single copy.
 *
 * Only the reading of the request and the composition of the answer live here — the two
 * questions it asks the database belong to CLpItemRepository and CLpItemViewRepository.
 */
readonly class ExerciseLearnpathVisibilityHelper
{
    public function __construct(
        private RequestStack $requestStack,
        private UserHelper $userHelper,
        private CLpItemRepository $lpItemRepository,
        private CLpItemViewRepository $lpItemViewRepository,
    ) {}

    /**
     * @param bool $readRequestBody also look for the learning path ids in the request body, for
     *                              the endpoints that receive them in a multipart POST rather
     *                              than in the query string
     */
    public function isVisibleThroughLearnpath(
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        bool $readRequestBody = false
    ): bool {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        // The query wins over the body, which is only read where the ids arrive in a multipart
        // POST. Both spellings of every id are still emitted, by Vue and by the legacy pages
        // alike, hence the ?: pairs; anything unusable reads as 0 and the guards below reject it.
        $params = new InputBag($readRequestBody
            ? $request->query->all() + $request->request->all()
            : $request->query->all());

        $learnpathId = (int) ($params->get('learnpath_id') ?: $params->get('lp_id'));
        $learnpathItemId = (int) ($params->get('learnpath_item_id') ?: $params->get('lp_item_id'));
        $learnpathItemViewId = (int) $params->get('learnpath_item_view_id');
        $origin = strtolower(trim((string) $params->get('origin', '')));
        $hasLearnpathContext = 'learnpath' === $origin
            || $params->has('lp_init')
            || $learnpathId > 0
            || $learnpathItemId > 0
            || $learnpathItemViewId > 0;

        if (!$hasLearnpathContext || $learnpathId <= 0 || $learnpathItemId <= 0) {
            return false;
        }

        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            return false;
        }

        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if ($exerciseId <= 0) {
            return false;
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : null;

        if (!$this->lpItemRepository->hasPublishedQuizItem($learnpathItemId, $learnpathId, $exerciseId, $courseId, $sessionId)) {
            return false;
        }

        if ($learnpathItemViewId <= 0) {
            return true;
        }

        return $this->lpItemViewRepository->belongsToUserLearnpathView(
            $learnpathItemViewId,
            $learnpathItemId,
            $learnpathId,
            $courseId,
            $sessionId,
            (int) $user->getId()
        );
    }
}
