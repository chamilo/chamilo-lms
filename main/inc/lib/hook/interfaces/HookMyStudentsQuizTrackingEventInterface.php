<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookMyStudentsQuizTrackingEventInterface.
 */
interface HookMyStudentsQuizTrackingEventInterface extends HookEventInterface
{
    /**
     * @return array
     */
    public function notifyTrackingHeader();

    /**
     * @param int $quizId
     * @param int $studentId
     *
     * @return array
     */
    public function notifyTrackingContent($quizId, $studentId);
}
