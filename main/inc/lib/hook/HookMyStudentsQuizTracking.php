<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookMyStudentsQuizTracking.
 */
class HookMyStudentsQuizTracking extends HookEvent implements HookMyStudentsQuizTrackingEventInterface
{
    /**
     * HookMyStudentsQuizTracking constructor.
     *
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct('HookMyStudentsQuizTracking');
    }

    /**
     * @return array
     */
    public function notifyTrackingHeader()
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
     *
     * @return array
     */
    public function notifyTrackingContent($quizId, $studentId)
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
