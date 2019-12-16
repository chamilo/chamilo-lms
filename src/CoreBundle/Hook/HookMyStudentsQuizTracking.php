<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookMyStudentsQuizTrackingEventInterface;
use Chamilo\CoreBundle\Hook\Interfaces\HookMyStudentsQuizTrackingObserverInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class HookMyStudentsQuizTracking.
 */
class HookMyStudentsQuizTracking extends HookEvent implements HookMyStudentsQuizTrackingEventInterface
{
    /**
     * HookMyStudentsQuizTracking constructor.
     */
    protected function __construct(EntityManager $entityManager)
    {
        parent::__construct('HookMyStudentsQuizTracking', $entityManager);
    }

    public function notifyTrackingHeader(): array
    {
        $results = [];

        /** @var HookMyStudentsQuizTrackingObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $results[] = $observer->trackingHeader($this);
        }

        return $results;
    }

    /**
     * @param int $quizId
     * @param int $studentId
     */
    public function notifyTrackingContent($quizId, $studentId): array
    {
        $this->eventData['quiz_id'] = $quizId;
        $this->eventData['student_id'] = $studentId;

        $results = [];

        /** @var HookMyStudentsQuizTrackingObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $results[] = $observer->trackingContent($this);
        }

        return $results;
    }
}
