<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookMyStudentsLpTracking.
 */
class HookMyStudentsLpTracking extends HookEvent implements HookMyStudentsLpTrackingEventInterface
{
    /**
     * HookMyStudentsLpTracking constructor.
     *
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct('HookMyStudentsLpTracking');
    }

    /**
     * @return array
     */
    public function notifyTrackingHeader()
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
     *
     * @return array
     */
    public function notifyTrackingContent($lpId, $studentId)
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
