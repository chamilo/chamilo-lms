<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookMyStudentsLpTrackingEventInterface;
use Chamilo\CoreBundle\Hook\Interfaces\HookMyStudentsLpTrackingObserverInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class HookMyStudentsLpTracking.
 */
class HookMyStudentsLpTracking extends HookEvent implements HookMyStudentsLpTrackingEventInterface
{
    /**
     * HookMyStudentsLpTracking constructor.
     */
    protected function __construct(EntityManager $entityManager)
    {
        parent::__construct('HookMyStudentsLpTracking', $entityManager);
    }

    public function notifyTrackingHeader(): array
    {
        $results = [];

        /** @var HookMyStudentsLpTrackingObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $results[] = $observer->trackingHeader($this);
        }

        return $results;
    }

    /**
     * @param int $lpId
     * @param int $studentId
     */
    public function notifyTrackingContent($lpId, $studentId): array
    {
        $this->eventData['lp_id'] = $lpId;
        $this->eventData['student_id'] = $studentId;

        $results = [];

        /** @var HookMyStudentsLpTrackingObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $results[] = $observer->trackingContent($this);
        }

        return $results;
    }
}
